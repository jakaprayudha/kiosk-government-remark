<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/service-catalog.php';
require_once dirname(__DIR__) . '/app/submissions.php';
require_once dirname(__DIR__) . '/app/submission-photos.php';
require_once dirname(__DIR__) . '/app/submission-letters.php';

function check_submission(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function rejects(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (DomainException | InvalidArgumentException $error) {
        return;
    }
    throw new RuntimeException($message);
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys = ON');
run_migrations($pdo);
run_migrations($pdo);
$pdo->exec("INSERT INTO users (id, name, username, password_hash) VALUES (1, 'Test', 'test', 'test')");
$pdo->exec("INSERT INTO service_categories (id, name, slug) VALUES (1, 'Test', 'test')");
$pdo->exec("INSERT INTO services (id, category_id, name, slug, is_published, accepts_submissions, is_demo) VALUES (1, 1, 'DEMO Test', 'test', 1, 1, 1)");
$service = public_service_catalog($pdo, '2026-10-20')[0];
$input = ['name' => '', 'contact' => '', 'description' => 'Permintaan simulasi', 'consent' => '1', 'confirmed' => '1'];
rejects(fn() => validate_submission($service, [...$input, 'consent' => '0']), 'Privasi tidak diperiksa.');
rejects(fn() => validate_submission($service, [...$input, 'confirmed' => '0']), 'Konfirmasi tidak diperiksa.');
rejects(fn() => validate_submission([...$service, 'requires_name' => 1], $input), 'Nama wajib tidak diperiksa.');
rejects(fn() => validate_submission([...$service, 'requires_contact' => 1], $input), 'Kontak wajib tidak diperiksa.');
rejects(fn() => validate_submission($service, [...$input, 'description' => '']), 'Uraian wajib tidak diperiksa.');
$identityService = [...$service, 'requires_nik' => 1, 'requires_birth_date' => 1, 'requires_face' => 1];
$identityInput = [...$input, 'nik' => '0000000000000000', 'birth_date' => '2000-02-29'];
check_submission(validate_submission($identityService, $identityInput)['birth_date'] === '2000-02-29', 'Tanggal kabisat valid harus diterima.');
foreach (['123', '12345678901234567', '000000000000000x'] as $nik) rejects(fn() => validate_submission($identityService, [...$identityInput, 'nik' => $nik]), 'Format NIK salah harus ditolak.');
foreach (['2001-02-29', '2100-01-01', '1899-12-31', '', '2000-2-29'] as $birth) rejects(fn() => validate_submission($identityService, [...$identityInput, 'birth_date' => $birth]), 'Tanggal lahir salah harus ditolak.');
rejects(fn() => validate_submission($service, [...$input, 'nik' => '0000000000000000']), 'NIK yang tidak diminta tidak boleh disimpan.');
rejects(fn() => validate_submission($service, [...$input, 'birth_date' => '2000-01-01']), 'Tanggal lahir yang tidak diminta tidak boleh disimpan.');
rejects(fn() => create_submission($pdo, $identityService, $identityInput, 'missing-face', '123456', []), 'Foto wajah wajib harus diperiksa server.');
rejects(fn() => store_submission_photos([], [], false, true), 'Foto wajib kosong harus ditolak.');
rejects(fn() => store_submission_letter(['error' => UPLOAD_ERR_INI_SIZE]), 'PDF terlalu besar harus ditolak.');
$code = create_submission($pdo, $service, $input, 'request-test', '123456', []);
check_submission((bool) preg_match('/^[A-F0-9]{20}$/', $code), 'Format tiket salah.');
check_submission(create_submission($pdo, $service, $input, 'request-test', '123456', []) === $code, 'Kirim ulang harus mengembalikan tiket lama.');
check_submission((int) $pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn() === 1, 'Pengajuan terduplikasi.');
$row = $pdo->query('SELECT * FROM submissions')->fetch();
check_submission(password_verify('123456', $row['pin_hash']) && !password_verify('000000', $row['pin_hash']), 'PIN harus berupa hash yang benar.');
check_submission($row['status'] === 'new' && (int) $row['is_demo'] === 1, 'Status/snapshot DEMO salah.');
change_submission_status($pdo, (int) $row['id'], 'new', 'reviewing', 'Sedang diperiksa', 'RAHASIA INTERNAL', 1);
rejects(fn() => change_submission_status($pdo, (int) $row['id'], 'new', 'reviewing', '', '', 1), 'Update basi harus ditolak.');
rejects(fn() => change_submission_status($pdo, (int) $row['id'], 'reviewing', 'completed', '', '', 1), 'Lompatan status harus ditolak.');
rejects(fn() => change_submission_status($pdo, (int) $row['id'], 'reviewing', 'needs_info', '', '', 1), 'Alasan publik wajib.');
change_submission_status($pdo, (int) $row['id'], 'reviewing', 'needs_info', 'Bawa dokumen contoh', 'Internal', 1);
change_submission_status($pdo, (int) $row['id'], 'needs_info', 'in_progress', 'Diproses', '', 1);
rejects(fn() => change_submission_status($pdo, (int) $row['id'], 'in_progress', 'completed', 'Selesai', '', 1), 'Selesai tanpa PDF harus ditolak.');
check_submission(public_submission_letter($pdo, $code) === null, 'Surat/antrean tidak boleh tersedia sebelum selesai.');
$letter = ['stored_name' => str_repeat('a', 48) . '.pdf', 'size_bytes' => 100];
change_submission_status($pdo, (int) $row['id'], 'in_progress', 'completed', 'Selesai', '', 1, $letter);
$publishedLetter = public_submission_letter($pdo, $code);
check_submission($publishedLetter['pickup_number'] === 'L1-000001', 'Nomor antrean pertama salah.');
check_submission($pdo->query('SELECT closed_at FROM submissions')->fetchColumn() !== null, 'Status akhir harus menyimpan waktu tutup.');
check_submission((int) $pdo->query('SELECT COUNT(*) FROM submission_events')->fetchColumn() === 5, 'Riwayat lengkap wajib tersimpan.');
rejects(fn() => change_submission_status($pdo, (int) $row['id'], 'completed', 'reviewing', '', '', 1), 'Status akhir tidak boleh dibuka ulang.');
$before = (int) $pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn();
try {
    create_submission($pdo, $service, $input, 'rollback-request', '123456', [
        ['requirement_id' => 999999, 'document_name' => 'Test', 'stored_name' => 'rollback.jpg', 'size_bytes' => 10],
    ]);
    throw new RuntimeException('Kegagalan lampiran tidak ditolak.');
} catch (PDOException $error) {
    check_submission(!$pdo->inTransaction(), 'Transaksi gagal harus di-rollback.');
    check_submission((int) $pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn() === $before, 'Tiket gagal tidak boleh tertinggal.');
}
$expiredCode = create_submission($pdo, $service, $input, 'expired-request', '123456', [
    ['requirement_id' => null, 'document_name' => 'Test', 'stored_name' => 'expired.jpg', 'size_bytes' => 10],
]);
$pdo->prepare("UPDATE submissions SET expires_at = datetime('now', '-1 day') WHERE public_code = ?")->execute([$expiredCode]);
$expired = (int) $pdo->query("SELECT id FROM submissions WHERE request_key = 'expired-request'")->fetchColumn();
rejects(fn() => change_submission_status($pdo, $expired, 'new', 'reviewing', '', '', 1), 'Pengajuan kedaluwarsa tidak boleh diproses.');
$pdo->prepare('DELETE FROM submissions WHERE id = ? AND expires_at <= CURRENT_TIMESTAMP')->execute([$expired]);
check_submission((int) $pdo->query('SELECT COUNT(*) FROM submission_attachments')->fetchColumn() === 0, 'Lampiran kedaluwarsa harus ikut terhapus.');
check_submission((int) $pdo->query('SELECT COUNT(*) FROM submission_events')->fetchColumn() === 5, 'Riwayat kedaluwarsa harus ikut terhapus.');
$secondCode = create_submission($pdo, $service, $input, 'second-ready', '123456', []);
$secondId = (int) $pdo->query("SELECT id FROM submissions WHERE request_key = 'second-ready'")->fetchColumn();
change_submission_status($pdo, $secondId, 'new', 'reviewing', '', '', 1);
change_submission_status($pdo, $secondId, 'reviewing', 'in_progress', '', '', 1);
try {
    change_submission_status($pdo, $secondId, 'in_progress', 'completed', '', '', 999, ['stored_name' => str_repeat('b', 48) . '.pdf', 'size_bytes' => 100]);
    throw new RuntimeException('Audit/petugas salah harus menggagalkan penerbitan surat.');
} catch (PDOException $error) {
    check_submission(public_submission_letter($pdo, $secondCode) === null, 'Surat gagal tidak boleh terbit.');
    check_submission((int) $pdo->query('SELECT last_number FROM service_pickup_sequences')->fetchColumn() === 1, 'Nomor antrean gagal tidak boleh terpakai.');
}
change_submission_status($pdo, $secondId, 'in_progress', 'completed', '', '', 1, ['stored_name' => str_repeat('b', 48) . '.pdf', 'size_bytes' => 100]);
check_submission(public_submission_letter($pdo, $secondCode)['pickup_number'] === 'L1-000002', 'Antrean layanan harus bertambah unik.');
$pdo->prepare("UPDATE submissions SET expires_at = datetime('now', '-1 day') WHERE id = ?")->execute([$secondId]);
check_submission(public_submission_letter($pdo, $secondCode) === null, 'Surat kedaluwarsa tidak boleh diakses.');
$pdo->prepare('DELETE FROM submissions WHERE id = ?')->execute([$secondId]);
check_submission((int) $pdo->query('SELECT COUNT(*) FROM submission_letters')->fetchColumn() === 1, 'Surat harus ikut terhapus dengan pengajuan.');
for ($i = 0; $i < 10; $i++) public_attempt($pdo, 'lookup', 'test-device');
rejects(fn() => public_attempt($pdo, 'lookup', 'test-device'), 'Pembatasan cek tiket tidak bekerja.');
rejects(fn() => store_submission_photos(['error' => [1 => UPLOAD_ERR_OK]], [], false), 'Kamera nonaktif harus menolak lampiran.');
rejects(fn() => store_submission_photos(['error' => [999 => UPLOAD_ERR_OK]], [], true), 'Dokumen dari layanan lain harus ditolak.');
function kiosk_escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$selected = 'pengajuan';
$transactionService = null;
$transactionError = '';
$transactionCatalog = [$service];
ob_start();
require dirname(__DIR__) . '/app/views/public-submissions.php';
$html = ob_get_clean();
check_submission(str_contains($html, 'Pilih layanan yang ingin diajukan') && str_contains($html, 'menu=pengajuan&amp;layanan=1') && !str_contains($html, 'class="menu-card"'), 'Pengajuan harus memakai pilihan layanan tersendiri, bukan katalog informasi.');
$transactionService = $identityService;
$transactionRequirements = [];
$formKey = 'test-key';
$_SESSION = ['csrf' => 'test-token'];
$input = $identityInput;
ob_start();
require dirname(__DIR__) . '/app/views/public-submissions.php';
$html = ob_get_clean();
foreach (['name="nik"', 'name="birth_date"', 'value="face"', 'Keyboard layar sentuh'] as $text) check_submission(str_contains($html, $text), 'Form identitas/wajah/keyboard hilang: ' . $text);
foreach (['id="document-camera-preview"', 'id="face-guide"', 'class="face-guide" hidden aria-hidden="true"'] as $text) check_submission(str_contains($html, $text), 'Panduan foto wajah hilang: ' . $text);
$transactionService = $service;
ob_start();
require dirname(__DIR__) . '/app/views/public-submissions.php';
$html = ob_get_clean();
check_submission(!str_contains($html, 'name="nik"') && !str_contains($html, 'name="birth_date"') && !str_contains($html, 'value="face"'), 'Identitas/wajah yang tidak diminta tidak boleh ditampilkan.');
$selected = 'status';
$ticketStatus = null;
$finalLetter = null;
$ticketEvents = [];
ob_start();
require dirname(__DIR__) . '/app/views/public-submissions.php';
$html = ob_get_clean();
foreach (['Scan QR dengan kamera', 'Ketik nomor pengajuan', 'Nomor pengajuan / kode tiket', 'PIN enam digit'] as $label) check_submission(str_contains($html, $label), 'Opsi cek status hilang: ' . $label);
foreach (['id="qr-scanner-preview" class="qr-scanner-preview" hidden', 'Kamera aktif · Scan QR code', 'Tempatkan QR tiket di dalam kotak', 'class="qr-scanner-line"'] as $text) check_submission(str_contains($html, $text), 'Panduan kamera QR hilang: ' . $text);
check_submission(!str_contains($html, 'download=1'), 'File tidak boleh tersedia sebelum cek PIN.');
$ticketStatus = ['service_name' => '<script>layanan</script>', 'public_code' => $code, 'is_demo' => 1, 'status' => 'completed', 'public_note' => '<img src=x>', 'created_at' => '2026-10-20 03:00:00', 'updated_at' => '2026-10-20 04:00:00'];
$ticketEvents = [
    ['new_status' => 'new', 'public_message' => 'Tersimpan', 'created_at' => '2026-10-20 03:00:00'],
    ['new_status' => 'completed', 'public_message' => '<script>catatan</script>', 'created_at' => '2026-10-20 04:00:00'],
];
$finalLetter = ['pickup_number' => 'L1-000001', 'size_bytes' => 2048];
ob_start();
require dirname(__DIR__) . '/app/views/public-submissions.php';
$html = ob_get_clean();
foreach (['download=1', 'Unduh PDF surat', 'Buka / cetak PDF surat', 'Riwayat progres pengajuan', 'aria-current="step"', '11:00:00 WIB', '2,0 KB', 'L1-000001'] as $text) check_submission(str_contains($html, $text), 'Hasil status tidak lengkap: ' . $text);
check_submission(!str_contains($html, '<script>layanan') && !str_contains($html, '<script>catatan') && !str_contains($html, '<img src=x>'), 'Hasil status harus di-escape.');
$finalLetter = null;
ob_start();
require dirname(__DIR__) . '/app/views/public-submissions.php';
$html = ob_get_clean();
check_submission(!str_contains($html, 'download=1') && str_contains($html, 'Surat final dan nomor antrean belum tersedia'), 'Tanpa surat final harus menampilkan keadaan belum tersedia.');
fwrite(STDOUT, "PASS: pilihan pengajuan terpisah, identitas bersyarat, foto wajah wajib, persetujuan, idempotensi, hash PIN, transisi, PDF final, antrean unik, konflik, rollback, retensi, dan kontrol lampiran.\n");
