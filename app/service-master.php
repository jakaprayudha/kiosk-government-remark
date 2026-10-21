<?php
declare(strict_types=1);
require_once __DIR__ . '/service-catalog.php';

function service_master_flags(): array
{
    return ['is_published' => 'Aktif / tampil di publik', 'is_demo' => 'Layanan simulasi DEMO',
        'accepts_submissions' => 'Terima pengajuan di KIOSK', 'requires_name' => 'Nama wajib',
        'requires_contact' => 'Kontak wajib', 'requires_nik' => 'NIK wajib (16 digit)',
        'requires_birth_date' => 'Tanggal lahir wajib', 'requires_face' => 'Foto wajah wajib (tanpa pengenalan wajah)',
        'camera_enabled' => 'Izinkan foto dokumen persyaratan (opsional)'];
}

function service_master_text_fields(): array
{
    return ['name' => 180, 'slug' => 120, 'summary' => 500, 'description' => 10000,
        'eligibility' => 3000, 'fee_text' => 1000, 'estimated_time' => 500,
        'office_unit' => 300, 'service_location' => 1000, 'official_url' => 2000];
}

function save_service_master(PDO $pdo, array $input, int $id, int $version, int $adminId): int
{
    $data = [];
    foreach (service_master_text_fields() as $key => $limit) {
        if (!is_string($input[$key] ?? null)) throw new DomainException('Isian layanan tidak valid.');
        $data[$key] = trim($input[$key]);
        if (strlen($data[$key]) > $limit) throw new DomainException('Isian ' . $key . ' terlalu panjang (maksimal ' . $limit . ' byte).');
    }
    if ($data['name'] === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug'])) throw new DomainException('Nama wajib diisi; slug memakai huruf kecil, angka dan tanda hubung antar kata.');
    if ($data['official_url'] !== '' && service_official_url($data['official_url']) === null) throw new DomainException('URL resmi harus HTTP/HTTPS tanpa kredensial.');
    foreach (['category_id' => [1, PHP_INT_MAX], 'sort_order' => [0, 9999], 'retention_days' => [1, 365]] as $key => [$min, $max]) {
        $value = filter_var($input[$key] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
        if ($value === false) throw new DomainException('Kategori, urutan atau retensi tidak valid.');
        $data[$key] = $value;
    }
    foreach (service_master_flags() as $key => $label) {
        $value = $input[$key] ?? '0';
        if (!in_array($value, ['0', '1', 0, 1], true)) throw new DomainException('Status/pengaturan transaksi tidak valid.');
        $data[$key] = (int) $value;
    }
    foreach (['valid_from', 'valid_until'] as $key) {
        $value = $input[$key] ?? '';
        if (!is_string($value)) throw new DomainException('Tanggal tidak valid.');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($value !== '' && (!$date || $date->format('Y-m-d') !== $value)) throw new DomainException('Tanggal tidak valid.');
        $data[$key] = $value === '' ? null : $value;
    }
    if ($data['valid_from'] && $data['valid_until'] && $data['valid_until'] < $data['valid_from']) throw new DomainException('Tanggal akhir harus sama atau setelah tanggal mulai.');
    $requirements = $input['requirements'] ?? [];
    if (!is_array($requirements) || count($requirements) > 50) throw new DomainException('Maksimal 50 persyaratan per layanan.');
    $clean = [];
    foreach ($requirements as $row) {
        if (!is_array($row) || !is_string($row['name'] ?? null) || !is_string($row['description'] ?? null)) throw new DomainException('Persyaratan tidak valid.');
        $rid = filter_var($row['id'] ?? '0', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $name = trim($row['name']);
        $description = trim($row['description']);
        if ($rid === false || strlen($name) > 300 || strlen($description) > 1500 || !in_array($row['is_required'] ?? '0', ['0', '1', 0, 1], true)) throw new DomainException('Persyaratan tidak valid (nama maksimal 300 byte, penjelasan 1500 byte).');
        if ($name === '') throw new DomainException('Nama persyaratan wajib diisi; hapus baris bila tidak diperlukan.');
        if ($rid > 0 && isset($clean['id-' . $rid])) throw new DomainException('Persyaratan duplikat.');
        $clean[$rid > 0 ? 'id-' . $rid : 'new-' . count($clean)] = [$rid, $name, $description, (int) ($row['is_required'] ?? 0)];
    }
    if ($id < 0 || ($id > 0 && $version < 1)) throw new DomainException('Versi layanan tidak valid.');
    $pdo->beginTransaction();
    try {
        $pdo->exec('UPDATE services SET version=version WHERE 0');
        $q = $pdo->prepare('SELECT id FROM service_categories WHERE id=?');
        $q->execute([$data['category_id']]);
        if (!$q->fetchColumn()) throw new DomainException('Kategori tidak ditemukan.');
        $q = $pdo->prepare('SELECT id FROM services WHERE slug=? AND id<>?');
        $q->execute([$data['slug'], $id]);
        if ($q->fetchColumn()) throw new DomainException('Slug layanan sudah digunakan.');
        if ($id === 0) {
            $keys = array_keys($data);
            $pdo->prepare('INSERT INTO services(' . implode(',', $keys) . ',created_by) VALUES(' . implode(',', array_fill(0, count($keys) + 1, '?')) . ')')->execute([...array_values($data), $adminId]);
            $id = (int) $pdo->lastInsertId();
        } else {
            $q = $pdo->prepare('UPDATE services SET ' . implode(',', array_map(fn($key) => $key . '=?', array_keys($data))) . ',version=version+1,updated_at=CURRENT_TIMESTAMP WHERE id=? AND version=?');
            $q->execute([...array_values($data), $id, $version]);
            if ($q->rowCount() !== 1) throw new DomainException('Layanan berubah atau dihapus. Muat ulang sebelum menyimpan.');
        }
        $q = $pdo->prepare('SELECT id FROM service_requirements WHERE service_id=?');
        $q->execute([$id]);
        $existing = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
        $kept = [];
        foreach (array_values($clean) as $order => [$rid, $name, $description, $required]) {
            if ($rid > 0) {
                if (!in_array($rid, $existing, true)) throw new DomainException('Persyaratan bukan milik layanan ini.');
                $pdo->prepare('UPDATE service_requirements SET name=?,description=?,is_required=?,sort_order=? WHERE id=? AND service_id=?')->execute([$name, $description, $required, $order, $rid, $id]);
                $kept[] = $rid;
            } else {
                $pdo->prepare('INSERT INTO service_requirements(service_id,name,description,is_required,sort_order) VALUES(?,?,?,?,?)')->execute([$id, $name, $description, $required, $order]);
            }
        }
        foreach (array_diff($existing, $kept) as $rid) $pdo->prepare('DELETE FROM service_requirements WHERE id=? AND service_id=?')->execute([$rid, $id]);
        $pdo->prepare("INSERT INTO audit_logs(user_id,action,entity_type,entity_id,summary) VALUES(?,'service_saved','service',?,'Master dan transaksi layanan disimpan.')")->execute([$adminId, $id]);
        $pdo->commit();
        return $id;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function change_service_master(PDO $pdo, int $id, int $version, int $adminId, ?int $active): void
{
    if ($id < 1 || $version < 1 || ($active !== null && !in_array($active, [0, 1], true))) throw new DomainException('Layanan, versi atau status tidak valid.');
    $pdo->beginTransaction();
    try {
        $pdo->exec('UPDATE services SET version=version WHERE 0');
        if ($active === null) {
            $q = $pdo->prepare('SELECT (SELECT COUNT(*) FROM submissions WHERE service_id=?)+(SELECT COUNT(*) FROM service_pickup_sequences WHERE service_id=?)');
            $q->execute([$id, $id]);
            if ((int) $q->fetchColumn() > 0) throw new DomainException('Layanan memiliki pengajuan/riwayat nomor pengambilan. Nonaktifkan, bukan hapus.');
            $q = $pdo->prepare('DELETE FROM services WHERE id=? AND version=?');
            $q->execute([$id, $version]);
        } else {
            $q = $pdo->prepare('UPDATE services SET is_published=?,version=version+1,updated_at=CURRENT_TIMESTAMP WHERE id=? AND version=?');
            $q->execute([$active, $id, $version]);
        }
        if ($q->rowCount() !== 1) throw new DomainException('Layanan berubah atau dihapus. Muat ulang terlebih dahulu.');
        $pdo->prepare('INSERT INTO audit_logs(user_id,action,entity_type,entity_id,summary) VALUES(?,?,?,?,?)')->execute([$adminId, $active === null ? 'service_deleted' : 'service_status', 'service', $id, $active === null ? 'Master layanan kosong dihapus.' : 'Status publik layanan diperbarui.']);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}
