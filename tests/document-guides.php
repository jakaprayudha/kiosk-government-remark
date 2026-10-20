<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/document-guides.php';
function check_guide(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function rejects_guide(callable $operation): void
{
    try {
        $operation();
    } catch (DomainException $error) {
        return;
    }
    throw new RuntimeException('Isian tidak valid harus ditolak.');
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys = ON');
run_migrations($pdo);
run_migrations($pdo);
check_guide(public_document_guides($pdo) === [], 'Master awal harus kosong.');
$seed = file_get_contents(dirname(__DIR__) . '/database/seeds/demo-document-guides.sql');
if ($seed === false) throw new RuntimeException('Seed hilang.');
$pdo->exec($seed);
$pdo->exec($seed);
check_guide(count(public_document_guides($pdo)) === 4, 'Seed harus idempoten.');
$pdo->exec("UPDATE document_guides SET fee_note = 'Perubahan admin yang harus dipertahankan' WHERE slug = 'demo-sergai-kk'");
$pdo->exec($seed);
check_guide($pdo->query("SELECT fee_note FROM document_guides WHERE slug = 'demo-sergai-kk'")->fetchColumn() === 'Perubahan admin yang harus dipertahankan', 'Seed tidak boleh menimpa data DEMO yang diedit admin.');
check_guide((int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn() === 0, 'Master referensi tidak boleh mengubah katalog transaksi.');
$pdo->exec("INSERT INTO users (id, name, username, password_hash) VALUES (1, 'Test', 'test', 'test')");
$input = ['name' => 'Dokumen A', 'region' => 'Kabupaten Serdang Bedagai', 'requirements_text' => "Syarat A\r\n\nSyarat B",
    'fee_amount' => '10000', 'fee_note' => 'Nominal simulasi', 'legal_basis' => '', 'source_url' => '',
    'is_demo' => '1', 'is_published' => '1', 'sort_order' => '8'];
check_guide(document_guide_lines($input['requirements_text']) === ['Syarat A', 'Syarat B'], 'Baris syarat harus dinormalisasi.');
foreach (['-1', '1.5', '1000000001', '1e3', '10.000'] as $fee) rejects_guide(fn() => validate_document_guide([...$input, 'fee_amount' => $fee]));
rejects_guide(fn() => validate_document_guide([...$input, 'name' => []]));
rejects_guide(fn() => validate_document_guide([...$input, 'requirements_text' => '']));
rejects_guide(fn() => validate_document_guide([...$input, 'source_url' => 'javascript:alert(1)']));
rejects_guide(fn() => validate_document_guide([...$input, 'fee_verified' => '1']));
$official = $input;
unset($official['is_demo']);
rejects_guide(fn() => validate_document_guide($official));
rejects_guide(fn() => validate_document_guide([...$official, 'fee_verified' => '1']));
$official = [...$official, 'fee_verified' => '1', 'fee_amount' => '0',
    'legal_basis' => 'Referensi hanya untuk pengujian', 'source_url' => 'https://example.go.id/aturan'];
check_guide(validate_document_guide($official)['fee_amount'] === 0, 'Biaya nol harus berbeda dari belum diketahui.');
check_guide(validate_document_guide([...$input, 'fee_amount' => ''])['fee_amount'] === null, 'Kosong harus disimpan NULL.');
$id = save_document_guide($pdo, $input, 0, 0, 1);
$input['name'] = '<script>nama</script>';
save_document_guide($pdo, $input, $id, 1, 1);
rejects_guide(fn() => save_document_guide($pdo, $input, $id, 1, 1));
check_guide((int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn() === 2, 'Konflik tidak boleh menambah audit.');
$pdo->exec($seed);
check_guide($pdo->query('SELECT name FROM document_guides WHERE id = ' . $id)->fetchColumn() === $input['name'], 'Seed tidak boleh menimpa perubahan.');
function kiosk_escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function kiosk_icon(string $name): string { return ''; }
$documentGuides = public_document_guides($pdo);
$guidesError = '';
ob_start();
require dirname(__DIR__) . '/app/views/public-document-guides.php';
$html = ob_get_clean();
check_guide(str_contains($html, '&lt;script&gt;nama&lt;/script&gt;') && !str_contains($html, '<script>nama'), 'Isi tabel harus di-escape.');
foreach (['Pengurusan dokumen', 'Persyaratan', 'Biaya', 'Dasar hukum / Perda', 'Rp10.000', 'SIMULASI DEMO', 'Kabupaten Serdang Bedagai'] as $text) {
    check_guide(str_contains($html, $text), 'Kolom atau data hilang: ' . $text);
}
foreach (['<details', 'menu=pengajuan', 'menu=status', 'data-preparation-check'] as $markup) check_guide(!str_contains($html, $markup), 'Referensi tidak boleh menjadi alur transaksi.');
$documentGuides = [];
ob_start();
require dirname(__DIR__) . '/app/views/public-document-guides.php';
$html = ob_get_clean();
check_guide(str_contains($html, 'Belum ada panduan dipublikasikan') && !str_contains($html, '<table>'), 'Master kosong harus menampilkan pesan yang jelas.');
$guidesError = 'Uji database gagal';
ob_start();
require dirname(__DIR__) . '/app/views/public-document-guides.php';
$html = ob_get_clean();
check_guide(str_contains($html, 'role="alert"') && str_contains($html, $guidesError), 'Kegagalan database harus ditampilkan secara eksplisit.');
unset($input['is_published']);
save_document_guide($pdo, $input, $id, 2, 1);
check_guide(count(public_document_guides($pdo)) === 4, 'Draf harus disembunyikan dari publik.');
try {
    save_document_guide($pdo, $input, 0, 0, 999);
    throw new RuntimeException('Audit invalid harus menggagalkan transaksi.');
} catch (PDOException $error) {
    check_guide((int) $pdo->query('SELECT COUNT(*) FROM document_guides')->fetchColumn() === 5, 'Kegagalan harus di-rollback.');
}
fwrite(STDOUT, "PASS: migrasi, seed idempoten, data terpisah, rupiah, validasi hukum, publikasi, CRUD, konflik, audit, rollback, tabel, dan escaping.\n");
