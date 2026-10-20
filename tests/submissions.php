<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/service-catalog.php';
require_once dirname(__DIR__) . '/app/submissions.php';
require_once dirname(__DIR__) . '/app/submission-photos.php';

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
change_submission_status($pdo, (int) $row['id'], 'in_progress', 'completed', 'Selesai', '', 1);
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
for ($i = 0; $i < 10; $i++) public_attempt($pdo, 'lookup', 'test-device');
rejects(fn() => public_attempt($pdo, 'lookup', 'test-device'), 'Pembatasan cek tiket tidak bekerja.');
rejects(fn() => store_submission_photos(['error' => [1 => UPLOAD_ERR_OK]], [], false), 'Kamera nonaktif harus menolak lampiran.');
rejects(fn() => store_submission_photos(['error' => [999 => UPLOAD_ERR_OK]], [], true), 'Dokumen dari layanan lain harus ditolak.');
fwrite(STDOUT, "PASS: validasi, persetujuan, idempotensi, hash PIN, snapshot DEMO, transisi, riwayat, konflik, penutupan, rollback, retensi, pembatasan percobaan, dan kontrol lampiran.\n");
