<?php
declare(strict_types=1);
require_once __DIR__ . '/service-categories.php';
$categoryError = '';
$categoryRecords = [];
$categoryId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($categoryId === false) { http_response_code(400); exit('ID kategori tidak valid.'); }
$categoryForm = ['name' => '', 'slug' => '', 'description' => '', 'icon' => 'document', 'sort_order' => 0, 'is_active' => 1, 'version' => 0];
try {
    if ($categoryId > 0) {
        $query = db()->prepare('SELECT * FROM service_categories WHERE id=?');
        $query->execute([$categoryId]);
        $row = $query->fetch();
        if (!$row) { http_response_code(404); throw new DomainException('Kategori tidak ditemukan.'); }
        $categoryForm = $row;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token()) throw new DomainException('Sesi formulir berakhir. Muat ulang halaman.');
        $version = filter_var($_POST['version'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($version === false) throw new DomainException('Versi formulir tidak valid.');
        $input = $_POST + ['is_active' => '0'];
        if (($_POST['action'] ?? '') === 'toggle') {
            if ($categoryId < 1) throw new DomainException('ID kategori tidak valid.');
            save_service_category(db(), [...$categoryForm, 'is_active' => $input['is_active']], $categoryId, $version, (int) $admin['id']);
            redirect_to('/admin/dashboard.php?module=categories&saved=1');
        }
        if (($_POST['action'] ?? '') === 'delete') {
            if ($categoryId < 1) throw new DomainException('ID kategori tidak valid.');
            delete_service_category(db(), $categoryId, $version, (int) $admin['id']);
            redirect_to('/admin/dashboard.php?module=categories&deleted=1');
        }
        if (($_POST['action'] ?? '') !== 'save') throw new DomainException('Aksi kategori tidak valid.');
        foreach (['name','slug','description','icon','sort_order','is_active'] as $key) {
            if (is_string($input[$key] ?? null)) $categoryForm[$key] = $input[$key];
        }
        // Preserve the submitted version after conflicts; never overwrite newer data on retry.
        $categoryForm['version'] = $version;
        save_service_category(db(), $input, $categoryId, $version, (int) $admin['id']);
        redirect_to('/admin/dashboard.php?module=categories&saved=1');
    }
} catch (DomainException $error) {
    if (http_response_code() !== 404) http_response_code(422);
    $categoryError = $error->getMessage();
} catch (RuntimeException $error) {
    error_log('Pengelolaan kategori gagal: ' . get_class($error));
    http_response_code(503);
    $categoryError = 'Kategori belum dapat diproses. Muat ulang dan hubungi pengelola jika masalah berlanjut.';
}
try {
    $categoryRecords = db()->query('SELECT c.*,COUNT(s.id) AS service_count FROM service_categories c LEFT JOIN services s ON s.category_id=c.id GROUP BY c.id ORDER BY c.sort_order,c.name,c.id')->fetchAll();
} catch (RuntimeException $error) {
    error_log('Daftar kategori gagal dimuat: ' . get_class($error));
    http_response_code(503);
    $categoryError = 'Daftar kategori tidak dapat dimuat dari database.';
}
