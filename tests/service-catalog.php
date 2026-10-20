<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/service-catalog.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec('PRAGMA foreign_keys = ON');
run_migrations($pdo);
run_migrations($pdo);
check(public_service_catalog($pdo, '2026-10-20') === [], 'Katalog awal harus kosong.');
$pdo->exec("INSERT INTO service_categories (id, name, slug, is_active) VALUES
    (1, 'Umum', 'umum', 1), (2, 'Arsip', 'arsip', 0), (3, 'Konsultasi', 'konsultasi', 1)");
$insert = $pdo->prepare(
    'INSERT INTO services (id, category_id, name, slug, is_published, valid_from, valid_until, sort_order)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
foreach ([
    [1, 1, 'Terbit', 'terbit', 1, null, null, 2],
    [2, 1, 'Draf', 'draf', 0, null, null, 0],
    [3, 2, 'Kategori nonaktif', 'nonaktif', 1, null, null, 0],
    [4, 1, 'Mendatang', 'mendatang', 1, '2026-10-21', null, 0],
    [5, 1, 'Kedaluwarsa', 'kedaluwarsa', 1, null, '2026-10-19', 0],
    [6, 1, 'Batas tanggal', 'batas', 1, '2026-10-20', '2026-10-20', 1],
    [7, 3, 'Konsultasi', 'konsultasi', 1, null, null, 0],
] as $row) {
    $insert->execute($row);
}
$published = public_service_catalog($pdo, '2026-10-20');
check(array_column($published, 'id') === [7, 6, 1], 'Publikasi, kategori aktif, tanggal inklusif, dan urutan harus dipatuhi.');
check(count(public_service_catalog($pdo, '2026-10-20', 'umum')) === 2, 'Filter kategori harus bekerja.');
check(public_service_catalog($pdo, '2026-10-20', "' OR 1=1 --") === [], 'Filter kategori harus menggunakan parameter.');
foreach ([2, 3, 4, 5, 999] as $id) {
    check(public_service_catalog($pdo, '2026-10-20', '', $id) === [], 'Detail privat/tidak berlaku harus disembunyikan.');
}
$pdo->exec("INSERT INTO service_requirements (service_id, name, is_required, sort_order) VALUES
    (1, 'Opsional', 0, 2), (1, 'Wajib', 1, 1)");
$requirements = public_service_requirements($pdo, 1);
check(array_column($requirements, 'name') === ['Wajib', 'Opsional'], 'Urutan persyaratan harus benar.');
check(array_column($requirements, 'is_required') === [1, 0], 'Status wajib dan opsional harus dipertahankan.');
check(service_official_url('https://example.go.id/layanan') !== null, 'URL HTTPS valid harus diterima.');
foreach (['javascript:alert(1)', 'data:text/html,test', '//example.go.id', 'https://user:pass@example.go.id'] as $url) {
    check(service_official_url($url) === null, 'URL berbahaya/tidak valid harus ditolak.');
}

function kiosk_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function kiosk_icon(string $name): string
{
    return '<svg aria-hidden="true"></svg>';
}

$serviceId = 1;
$selected = 'layanan';
$menus = ['layanan' => ['title' => 'Layanan administrasi']];
$catalogError = '';
$service = public_service_catalog($pdo, '2026-10-20', '', 1)[0];
$service['name'] = '<script>alert(1)</script>';
$service['description'] = "Deskripsi\nbaris kedua";
$service['official_url'] = 'javascript:alert(1)';
ob_start();
require dirname(__DIR__) . '/app/views/service-catalog.php';
$html = ob_get_clean();
check(str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;'), 'Nama layanan harus di-escape.');
check(!str_contains($html, 'href="javascript:'), 'URL tidak aman tidak boleh dirender sebagai tautan.');
check(str_contains($html, '>Wajib</span>') && str_contains($html, '>Opsional</span>'), 'Detail harus menampilkan status persyaratan.');
check(str_contains($html, 'Belum diinformasikan.'), 'Biaya kosong tidak boleh dianggap gratis.');
$serviceId = null;
$service = null;
$category = '';
$catalog = $published;
$categories = ['umum' => 'Umum', 'konsultasi' => 'Konsultasi'];
ob_start();
require dirname(__DIR__) . '/app/views/service-catalog.php';
$html = ob_get_clean();
check(substr_count($html, 'class="menu-card"') === 3, 'Daftar harus merender seluruh layanan publik.');
check(str_contains($html, 'data-menu-voice="Terbit"'), 'Kartu layanan harus terhubung ke suara menu.');

try {
    $insert->execute([8, 1, 'Tanggal salah', 'salah', 1, '2026-02-30', null, 0]);
    throw new RuntimeException('Tanggal tidak valid diterima.');
} catch (PDOException $error) {
    check($error->getCode() === '23000', 'Tanggal harus ditolak oleh constraint.');
}
$pdo->exec('DELETE FROM services WHERE id = 1');
check(public_service_requirements($pdo, 1) === [], 'Persyaratan harus ikut dihapus saat layanan dihapus.');
fwrite(STDOUT, "PASS: migrasi, katalog kosong, publikasi, kategori, tanggal, urutan, filter, detail, persyaratan, URL, rendering, escaping, suara menu, dan foreign key.\n");
