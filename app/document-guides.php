<?php
declare(strict_types=1);
require_once __DIR__ . '/service-catalog.php';

function public_document_guides(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM document_guides WHERE is_published = 1 ORDER BY sort_order, name, id')->fetchAll();
}

function document_guide_lines(string $text): array
{
    return array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", '', $text))), static fn(string $line): bool => $line !== ''));
}

function validate_document_guide(array $input): array
{
    $data = [];
    foreach (['name' => 180, 'region' => 180, 'requirements_text' => 6000, 'fee_note' => 1500, 'legal_basis' => 1500, 'source_url' => 1000] as $key => $limit) {
        if (!is_string($input[$key] ?? null) || strlen(trim($input[$key])) > $limit) {
            throw new DomainException('Isian tidak valid atau melebihi batas panjang: ' . $key);
        }
        $data[$key] = trim($input[$key]);
    }
    if ($data['name'] === '' || $data['region'] === '' || document_guide_lines($data['requirements_text']) === []) {
        throw new DomainException('Nama pengurusan, wilayah, dan minimal satu persyaratan wajib diisi.');
    }
    if (count(document_guide_lines($data['requirements_text'])) > 30) throw new DomainException('Maksimal 30 baris persyaratan.');
    foreach (['is_demo', 'is_published', 'fee_verified'] as $key) {
        if (isset($input[$key]) && $input[$key] !== '1') throw new DomainException('Pilihan formulir tidak valid.');
        $data[$key] = isset($input[$key]) ? 1 : 0;
    }
    if (!is_string($input['fee_amount'] ?? null)) throw new DomainException('Nominal biaya tidak valid.');
    $fee = trim($input['fee_amount']);
    if ($fee !== '' && (!preg_match('/^(0|[1-9][0-9]{0,9})$/', $fee) || (int) $fee > 1000000000)) {
        throw new DomainException('Biaya harus rupiah bulat antara 0 dan 1.000.000.000, atau kosong jika belum diketahui.');
    }
    $data['fee_amount'] = $fee === '' ? null : (int) $fee;
    $order = filter_var($input['sort_order'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9999]]);
    if ($order === false) throw new DomainException('Urutan harus antara 0 dan 9999.');
    $data['sort_order'] = $order;
    if ($data['source_url'] !== '' && service_official_url($data['source_url']) === null) throw new DomainException('URL sumber harus HTTP/HTTPS yang valid tanpa kredensial.');
    if ($data['is_demo'] === 1 && $data['fee_verified'] === 1) throw new DomainException('Data DEMO tidak boleh ditandai sebagai tarif terverifikasi.');
    if ($data['fee_verified'] === 1 && ($data['fee_amount'] === null || $data['legal_basis'] === '' || $data['source_url'] === '')) {
        throw new DomainException('Verifikasi tarif memerlukan nominal biaya, dasar hukum (nomor/tahun/pasal), dan URL sumber resmi.');
    }
    if ($data['is_published'] === 1 && $data['is_demo'] === 0 && $data['fee_verified'] === 0) {
        throw new DomainException('Data non-DEMO hanya boleh dipublikasikan setelah tarif dan dasar hukum diverifikasi pengelola.');
    }
    return $data;
}

function save_document_guide(PDO $pdo, array $input, int $id, int $version, int $adminId): int
{
    $data = validate_document_guide($input);
    $pdo->beginTransaction();
    try {
        if ($id === 0) {
            $columns = array_keys($data);
            $insert = $pdo->prepare('INSERT INTO document_guides (slug, ' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns) + 1, '?')) . ')');
            $insert->execute(['guide-' . bin2hex(random_bytes(12)), ...array_values($data)]);
            $id = (int) $pdo->lastInsertId();
        } else {
            $assignments = array_map(static fn(string $key): string => $key . ' = ?', array_keys($data));
            $update = $pdo->prepare('UPDATE document_guides SET ' . implode(', ', $assignments) . ', version = version + 1, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND version = ?');
            $update->execute([...array_values($data), $id, $version]);
            if ($update->rowCount() !== 1) throw new DomainException('Data telah berubah atau tidak ditemukan. Muat ulang sebelum menyimpan.');
        }
        $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, summary) VALUES (?, 'document_guide_saved', 'document_guide', ?, 'Persyaratan, biaya, dan dasar hukum pengurusan diperbarui.')")->execute([$adminId, $id]);
        $pdo->commit();
        return $id;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}
