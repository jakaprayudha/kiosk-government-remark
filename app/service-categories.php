<?php
declare(strict_types=1);

function service_category_icons(): array
{
    return ['document' => 'Dokumen', 'building' => 'Kantor', 'checklist' => 'Persyaratan',
        'inbox' => 'Pengajuan', 'search' => 'Pencarian', 'chat' => 'Percakapan',
        'calendar' => 'Jadwal', 'help' => 'Bantuan', 'home' => 'Beranda'];
}

function save_service_category(PDO $pdo, array $input, int $id, int $version, int $adminId): int
{
    $data = [];
    foreach (['name' => 120, 'slug' => 120, 'description' => 1500, 'icon' => 30] as $key => $limit) {
        if (!is_string($input[$key] ?? null)) throw new DomainException('Isian kategori tidak valid.');
        $data[$key] = trim($input[$key]);
        if (strlen($data[$key]) > $limit) throw new DomainException('Isian kategori terlalu panjang.');
    }
    if ($data['name'] === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug'])) {
        throw new DomainException('Nama wajib diisi. Slug memakai huruf kecil a-z, angka, dan tanda hubung antar kata.');
    }
    if (!isset(service_category_icons()[$data['icon']])) throw new DomainException('Pilih ikon yang tersedia.');
    $order = filter_var($input['sort_order'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9999]]);
    $active = filter_var($input['is_active'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);
    if ($order === false || $active === false || $id < 0 || ($id > 0 && $version < 1)) throw new DomainException('Urutan, status, atau versi kategori tidak valid.');
    $pdo->beginTransaction();
    try {
        // Serialize slug validation and writes, including concurrent category creation.
        $pdo->exec('UPDATE service_categories SET version=version WHERE 0');
        $check = $pdo->prepare('SELECT id FROM service_categories WHERE slug=? AND id<>?');
        $check->execute([$data['slug'], $id]);
        if ($check->fetchColumn()) throw new DomainException('Slug sudah digunakan kategori lain. Gunakan slug berbeda.');
        $values = [...array_values($data), $order, $active];
        if ($id === 0) {
            $pdo->prepare('INSERT INTO service_categories(name,slug,description,icon,sort_order,is_active) VALUES(?,?,?,?,?,?)')->execute($values);
            $id = (int) $pdo->lastInsertId();
        } else {
            $update = $pdo->prepare('UPDATE service_categories SET name=?,slug=?,description=?,icon=?,sort_order=?,is_active=?,version=version+1,updated_at=CURRENT_TIMESTAMP WHERE id=? AND version=?');
            $update->execute([...$values, $id, $version]);
            if ($update->rowCount() !== 1) throw new DomainException('Kategori berubah atau telah dihapus. Muat ulang sebelum menyimpan.');
        }
        $pdo->prepare("INSERT INTO audit_logs(user_id,action,entity_type,entity_id,summary) VALUES(?,'category_saved','service_category',?,'Kategori layanan disimpan.')")->execute([$adminId, $id]);
        $pdo->commit();
        return $id;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function delete_service_category(PDO $pdo, int $id, int $version, int $adminId): void
{
    $pdo->beginTransaction();
    try {
        $pdo->exec('UPDATE service_categories SET version=version WHERE 0');
        $query = $pdo->prepare('SELECT COUNT(*) FROM services WHERE category_id=?');
        $query->execute([$id]);
        if ((int) $query->fetchColumn() > 0) throw new DomainException('Kategori masih dipakai layanan dan tidak dapat dihapus. Nonaktifkan kategori untuk menyembunyikan layanannya dari publik.');
        $delete = $pdo->prepare('DELETE FROM service_categories WHERE id=? AND version=?');
        $delete->execute([$id, $version]);
        if ($delete->rowCount() !== 1) throw new DomainException('Kategori berubah atau telah dihapus. Muat ulang sebelum menghapus.');
        $pdo->prepare("INSERT INTO audit_logs(user_id,action,entity_type,entity_id,summary) VALUES(?,'category_deleted','service_category',?,'Kategori layanan kosong dihapus.')")->execute([$adminId, $id]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}
