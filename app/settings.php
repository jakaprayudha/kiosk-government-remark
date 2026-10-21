<?php
declare(strict_types=1);

function kiosk_settings(PDO $pdo): array
{
    $row = $pdo->query('SELECT * FROM kiosk_settings WHERE id=1')->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new RuntimeException('Pengaturan KIOSK belum tersedia. Jalankan migrasi database.');
    return $row;
}

function settings_logo(string $bytes): string
{
    if ($bytes === '' || strlen($bytes) > 524288) throw new DomainException('Logo maksimal 512 KB dan tidak boleh kosong.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
    $size = @getimagesizefromstring($bytes);
    if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true) || !$size || ($size['mime'] ?? '') !== $mime
        || $size[0] > 2048 || $size[1] > 2048) {
        throw new DomainException('Logo harus PNG, JPEG, atau WebP yang valid, maksimal 2048 x 2048 piksel.');
    }
    return 'data:' . $mime . ';base64,' . base64_encode($bytes);
}

function save_kiosk_settings(PDO $pdo, int $adminId, array $input, int $version, ?string $logo): void
{
    $values = [];
    foreach (['region_name' => 120, 'kiosk_title' => 60, 'tagline' => 240, 'footer_text' => 240] as $key => $max) {
        $value = $input[$key] ?? null;
        if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || mb_strlen(trim($value)) > $max || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            throw new DomainException('Isian identitas tidak valid atau melebihi batas panjang.');
        }
        $values[$key] = trim($value);
        if ($key !== 'region_name' && $values[$key] === '') throw new DomainException('Judul, tagline, dan footer wajib diisi.');
    }
    // Revalidate decoded image data rather than trusting a caller-supplied data URL.
    if ($logo !== null && $logo !== '') {
        $parts = explode(';base64,', $logo, 2);
        $bytes = isset($parts[1]) ? base64_decode($parts[1], true) : false;
        if ($bytes === false || settings_logo($bytes) !== $logo) throw new DomainException('Data logo tidak valid.');
    }
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $actor = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='admin' AND is_active=1 AND office_id IS NULL");
        $actor->execute([$adminId]);
        if (!$actor->fetchColumn()) throw new DomainException('Hanya admin pusat aktif yang dapat mengubah pengaturan.');
        $query = $pdo->prepare('UPDATE kiosk_settings SET region_name=?,kiosk_title=?,tagline=?,footer_text=?,
            logo_data=COALESCE(?,logo_data),version=version+1,updated_at=CURRENT_TIMESTAMP WHERE id=1 AND version=?');
        $query->execute([...array_values($values), $logo, $version]);
        if ($query->rowCount() !== 1) throw new DomainException('Pengaturan sudah berubah. Muat ulang sebelum menyimpan kembali.');
        $pdo->prepare("INSERT INTO audit_logs(user_id,action,entity_type,entity_id,summary)
            VALUES(?,'kiosk_settings','kiosk_settings',1,'Identitas global KIOSK diperbarui.')")->execute([$adminId]);
        $pdo->exec('COMMIT');
    } catch (Throwable $error) {
        $pdo->exec('ROLLBACK');
        throw $error;
    }
}
