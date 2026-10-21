<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/service-catalog.php';
function category_check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function category_reject(callable $operation, string $message): void
{
    try { $operation(); } catch (DomainException $error) { return; }
    throw new RuntimeException($message);
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON');
run_migrations($pdo);
run_migrations($pdo);
$pdo->exec("INSERT INTO users(id,name,username,password_hash) VALUES(1,'Test','test','test')");
$input = ['name' => 'Kategori contoh', 'slug' => 'kategori-contoh', 'description' => '<script>Deskripsi</script>', 'icon' => 'chat', 'sort_order' => 2, 'is_active' => 1];
foreach (['name' => '', 'slug' => 'Kode Salah', 'description' => [], 'icon' => 'evil', 'sort_order' => -1, 'is_active' => 2] as $field => $value) {
    category_reject(fn() => save_service_category($pdo, [...$input, $field => $value], 0, 0, 1), 'Input buruk diterima: ' . $field);
}
foreach (['name' => 120, 'slug' => 120, 'description' => 1500] as $field => $limit) {
    category_reject(fn() => save_service_category($pdo, [...$input, $field => str_repeat('a', $limit + 1)], 0, 0, 1), 'Batas tidak diperiksa.');
}
category_reject(fn() => save_service_category($pdo, [...$input, 'sort_order' => 10000], 0, 0, 1), 'Batas urutan tidak diperiksa.');
$boundary = save_service_category($pdo, [...$input, 'name' => str_repeat('a', 120), 'slug' => str_repeat('b', 120), 'description' => str_repeat('c', 1500), 'sort_order' => 9999], 0, 0, 1);
delete_service_category($pdo, $boundary, 1, 1);
try {
    save_service_category($pdo, [...$input, 'slug' => 'gagal-audit'], 0, 0, 999);
    throw new RuntimeException('Audit invalid diterima.');
} catch (PDOException $error) {
    category_check((int) $pdo->query("SELECT COUNT(*) FROM service_categories WHERE slug='gagal-audit'")->fetchColumn() === 0, 'Simpan tidak rollback.');
}
$id = save_service_category($pdo, $input, 0, 0, 1);
category_reject(fn() => save_service_category($pdo, $input, 0, 0, 1), 'Slug duplikat diterima.');
category_check(public_service_catalog($pdo, '2026-10-21') === [], 'Kategori kosong membuat layanan publik.');
$pdo->prepare("INSERT INTO services(category_id,name,slug,is_published,accepts_submissions) VALUES(?,'Layanan contoh','contoh',1,1)")->execute([$id]);
$catalog = public_service_catalog($pdo, '2026-10-21');
category_check($catalog[0]['category_icon'] === 'chat' && $catalog[0]['category_description'] === $input['description'], 'Metadata kategori tidak tersinkron.');
category_reject(fn() => delete_service_category($pdo, $id, 1, 1), 'Kategori berisi layanan dihapus.');
save_service_category($pdo, [...$input, 'is_active' => 0], $id, 1, 1);
category_check(public_service_catalog($pdo, '2026-10-21') === [] && public_service_catalog($pdo, '2026-10-21', '', 1) === [], 'Nonaktif tidak menyembunyikan katalog/detail.');
category_check((int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn() === 1, 'Layanan terhapus saat nonaktif.');
category_reject(fn() => save_service_category($pdo, $input, $id, 1, 1), 'Versi stale diterima.');
save_service_category($pdo, [...$input, 'name' => 'Kategori baru', 'slug' => 'kategori-baru', 'icon' => 'calendar'], $id, 2, 1);
category_check(public_service_catalog($pdo, '2026-10-21', 'kategori-contoh') === [], 'Slug lama masih dipakai.');
category_check(public_service_catalog($pdo, '2026-10-21', 'kategori-baru')[0]['category_name'] === 'Kategori baru', 'Perubahan nama/slug gagal.');
$other = save_service_category($pdo, [...$input, 'slug' => 'lain', 'sort_order' => 0], 0, 0, 1);
$pdo->prepare("INSERT INTO services(category_id,name,slug,is_published) VALUES(?,'Layanan kedua','kedua',1)")->execute([$other]);
category_check(public_service_catalog($pdo, '2026-10-21')[0]['category_slug'] === 'lain', 'Urutan kategori tidak sinkron.');
$empty = save_service_category($pdo, [...$input, 'slug' => 'kosong'], 0, 0, 1);
category_reject(fn() => delete_service_category($pdo, $empty, 0, 1), 'Delete versi stale diterima.');
try {
    delete_service_category($pdo, $empty, 1, 999);
    throw new RuntimeException('Audit invalid diterima.');
} catch (PDOException $error) {
    category_check((int) $pdo->query('SELECT COUNT(*) FROM service_categories WHERE id=' . $empty)->fetchColumn() === 1, 'Delete tidak rollback.');
}
delete_service_category($pdo, $empty, 1, 1);
category_check((int) $pdo->query('SELECT COUNT(*) FROM service_categories WHERE id=' . $empty)->fetchColumn() === 0, 'Kategori kosong tidak dihapus.');
category_reject(fn() => delete_service_category($pdo, $empty, 1, 1), 'Delete kedua diterima.');
$draft = save_service_category($pdo, [...$input, 'slug' => 'draft'], 0, 0, 1);
$pdo->prepare("INSERT INTO services(category_id,name,slug) VALUES(?,'Draf','draft')")->execute([$draft]);
category_reject(fn() => delete_service_category($pdo, $draft, 1, 1), 'Kategori berisi draf dihapus.');
category_check((int) $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action='category_deleted'")->fetchColumn() === 2, 'Audit delete salah.');
function escape(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string { return 'test'; }
$categoryError = '';
$categoryId = $id;
$categoryForm = [...$input, 'name' => '<script>Nama</script>', 'version' => 3];
save_service_category($pdo, [...$input, 'slug' => 'uji-tombol-hapus'], 0, 0, 1);
$categoryRecords = $pdo->query('SELECT c.*,COUNT(s.id) AS service_count FROM service_categories c LEFT JOIN services s ON s.category_id=c.id GROUP BY c.id')->fetchAll();
ob_start(); require dirname(__DIR__) . '/app/views/admin-categories.php'; $html = ob_get_clean();
category_check(str_contains($html, '&lt;script&gt;Nama&lt;/script&gt;') && !str_contains($html, '<script>Nama</script>'), 'Escaping formulir salah.');
category_check(str_contains($html, 'Nonaktif') && str_contains($html, 'csrf_token') && str_contains($html, 'name="version"'), 'Kontrol admin hilang.');
category_check(str_contains($html, '<dialog id="category-dialog"') && str_contains($html, 'open data-auto-open'), 'Edit tidak membuka modal.');
category_check(str_contains($html, 'id="category-table"') && str_contains($html, 'id="category-add"'), 'Tabel/tombol tambah hilang.');
category_check(!str_contains($html, 'confirm_delete') && str_contains($html, 'button-danger-outline'), 'Hapus langsung/outline merah tidak tersedia.');
category_check(str_contains($html, 'class="category-dialog-body"') && str_contains($html, 'class="category-dialog-footer"'), 'Struktur scroll modal tidak tersedia.');
category_check(str_contains($html, 'type="submit" form="category-form"'), 'Simpan di footer tidak terhubung ke form.');
$categoryId = 0;
$categoryError = '';
$_GET = [];
$_SERVER['REQUEST_METHOD'] = 'GET';
$categoryRecords = [];
ob_start(); require dirname(__DIR__) . '/app/views/admin-categories.php'; $html = ob_get_clean();
category_check(!str_contains($html, 'open data-auto-open'), 'Form tambah terbuka pada halaman daftar.');
category_check(strpos($html, '</section>') < strpos($html, '<dialog'), 'Form belum dipisahkan dari tabel.');
category_check(!str_contains($html, 'colspan='), 'Placeholder merusak jumlah kolom DataTable kosong.');
$categoryError = 'Slug sudah digunakan.';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'save';
ob_start(); require dirname(__DIR__) . '/app/views/admin-categories.php'; $html = ob_get_clean();
category_check(str_contains($html, 'open data-auto-open') && str_contains($html, 'Slug sudah digunakan.'), 'Error tidak membuka modal kembali.');
echo "PASS: CRUD kategori, validasi, slug unik, versi, audit/rollback, perlindungan kategori terpakai/draf, sinkron nama/slug/ikon/urutan/status publik, dan escaping.\n";
