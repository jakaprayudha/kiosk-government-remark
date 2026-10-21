<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/submissions.php';
require_once dirname(__DIR__) . '/app/complaints.php';

function check_complaint(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function reject_complaint(callable $operation, string $message): void
{
    try { $operation(); } catch (DomainException | InvalidArgumentException $error) { return; }
    throw new RuntimeException($message);
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON');
run_migrations($pdo);
run_migrations($pdo);
$pdo->exec("INSERT INTO users(id,name,username,password_hash) VALUES(1,'Test','test','test')");
$input = ['kind' => 'complaint', 'category' => 'service', 'title' => 'Laporan contoh', 'location' => 'Lokasi contoh',
    'description' => 'Uraian contoh', 'name' => '', 'contact' => '', 'consent' => '1', 'confirmed' => '1'];
foreach (['kind' => 'bad', 'category' => 'bad', 'title' => ' ', 'description' => '', 'name' => [], 'consent' => '0', 'confirmed' => '0'] as $field => $value) {
    reject_complaint(fn() => validate_complaint([...$input, $field => $value]), 'Input buruk diterima: ' . $field);
}
foreach (['title' => 160, 'location' => 240, 'name' => 120, 'contact' => 120, 'description' => 3000] as $field => $limit) {
    check_complaint(strlen(validate_complaint([...$input, $field => str_repeat('a', $limit)])[$field]) === $limit, 'Batas valid ditolak.');
    reject_complaint(fn() => validate_complaint([...$input, $field => str_repeat('a', $limit + 1)]), 'Batas panjang tidak diperiksa.');
}
$key = bin2hex(random_bytes(24));
$code = create_complaint($pdo, $input, $key, '123456');
check_complaint((bool) preg_match('/^AD-[A-F0-9]{20}$/', $code), 'Format kode salah.');
check_complaint(create_complaint($pdo, $input, $key, '123456') === $code, 'Pengiriman ulang membuat duplikat.');
check_complaint((int) $pdo->query('SELECT COUNT(*) FROM complaint_events')->fetchColumn() === 1, 'Event duplikat.');
$stored = $pdo->query('SELECT * FROM complaints')->fetch();
$id = (int) $stored['id'];
check_complaint($stored['name'] === '' && $stored['contact'] === '', 'Laporan tanpa identitas gagal.');
check_complaint($stored['pin_hash'] !== '123456' && password_verify('123456', $stored['pin_hash']), 'PIN tidak di-hash.');
check_complaint(abs(strtotime($stored['expires_at'] . ' UTC') - time() - 180 * 86400) < 5, 'Retensi bukan 180 hari.');
reject_complaint(fn() => lookup_complaint($pdo, $code, '000000'), 'PIN salah diterima.');
reject_complaint(fn() => lookup_complaint($pdo, 'AD-' . str_repeat('0', 20), '123456'), 'Kode asing diterima.');
$public = lookup_complaint($pdo, strtolower($code), '123456');
foreach (['name', 'contact', 'title', 'description', 'location', 'pin_hash', 'request_key'] as $field) {
    check_complaint(!isset($public[$field]), 'Data privat bocor: ' . $field);
}
reject_complaint(fn() => update_complaint($pdo, $id, 1, 'new', 'completed', 'Selesai', '', 1), 'Lompat status diterima.');
reject_complaint(fn() => update_complaint($pdo, $id, 1, 'new', 'reviewing', '', '', 1), 'Tanggapan kosong diterima.');
update_complaint($pdo, $id, 1, 'new', 'reviewing', '<script>Publik</script>', 'RAHASIA', 1);
reject_complaint(fn() => update_complaint($pdo, $id, 1, 'new', 'reviewing', 'Konflik', '', 1), 'Konflik tidak dicegah.');
update_complaint($pdo, $id, 2, 'reviewing', 'reviewing', 'Pembaruan tinjauan', '', 1);
reject_complaint(fn() => update_complaint($pdo, $id, 2, 'reviewing', 'in_progress', 'Stale', '', 1), 'Konflik status sama tidak dicegah.');
update_complaint($pdo, $id, 3, 'reviewing', 'in_progress', 'Sedang ditangani', '', 1);
try {
    update_complaint($pdo, $id, 4, 'in_progress', 'completed', 'Gagal transaksi', '', 999);
    throw new RuntimeException('Foreign key tidak diperiksa.');
} catch (PDOException $error) {
    check_complaint((int) $pdo->query('SELECT version FROM complaints')->fetchColumn() === 4, 'Rollback gagal.');
}
update_complaint($pdo, $id, 4, 'in_progress', 'completed', 'Selesai ditangani', '', 1);
reject_complaint(fn() => update_complaint($pdo, $id, 5, 'completed', 'reviewing', 'Buka lagi', '', 1), 'Status akhir berubah.');
$public = lookup_complaint($pdo, $code, '123456');
check_complaint(count($public['events']) === 5 && !str_contains(json_encode($public), 'RAHASIA'), 'Riwayat publik salah.');
$aspiration = create_complaint($pdo, [...$input, 'kind' => 'aspiration', 'name' => 'Nama contoh', 'contact' => 'kontak contoh'], bin2hex(random_bytes(24)), '234567');
$aspirationId = (int) $pdo->query('SELECT MAX(id) FROM complaints')->fetchColumn();
update_complaint($pdo, $aspirationId, 1, 'new', 'rejected', 'Di luar kewenangan, hubungi petugas terkait.', '', 1);
check_complaint(lookup_complaint($pdo, $aspiration, '234567')['status'] === 'rejected', 'Aspirasi/alasan penolakan gagal.');
$pdo->exec("UPDATE complaints SET expires_at=datetime('now','-1 day') WHERE id=" . $id);
reject_complaint(fn() => lookup_complaint($pdo, $code, '123456'), 'Laporan kedaluwarsa dapat diakses.');
check_complaint(purge_expired_complaints($pdo) === 1, 'Pembersihan retensi gagal.');
check_complaint((int) $pdo->query('SELECT COUNT(*) FROM complaint_events WHERE complaint_id=' . $id)->fetchColumn() === 0, 'Riwayat tidak terhapus cascade.');

function kiosk_escape(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$_SESSION = ['csrf' => 'test'];
$complaintError = '';
$complaintReceipt = null;
$complaintResult = null;
$complaintInput = $input;
$complaintKey = $key;
$complaintTab = 'laporan';
ob_start();
require dirname(__DIR__) . '/app/views/public-complaints.php';
$html = ob_get_clean();
check_complaint(str_contains($html, 'id="complaint-form"') && !str_contains($html, 'id="complaint-lookup"'), 'Tab tulis laporan harus terpisah dari cek tanggapan.');
check_complaint(str_contains($html, '180 hari') && str_contains($html, 'keyboard-toggle'), 'Privasi/keyboard hilang.');
$complaintTab = 'tanggapan';
$complaintResult = $public;
ob_start();
require dirname(__DIR__) . '/app/views/public-complaints.php';
$html = ob_get_clean();
foreach (['Tulis laporan', 'Cek tanggapan', 'keyboard-toggle', 'aria-current="step"', '&lt;script&gt;Publik&lt;/script&gt;'] as $text) check_complaint(str_contains($html, $text), 'UI tidak lengkap: ' . $text);
check_complaint(!str_contains($html, 'id="complaint-form"') && str_contains($html, 'id="complaint-lookup"'), 'Tab tanggapan tidak terpisah.');
check_complaint(!str_contains($html, '<script>Publik</script>') && !str_contains($html, 'RAHASIA'), 'UI tidak aman.');
$complaintResult = null;
$complaintReceipt = [...$public, 'pin' => '123456'];
$complaintTab = 'laporan';
ob_start();
require dirname(__DIR__) . '/app/views/public-complaints.php';
$html = ob_get_clean();
check_complaint(str_contains($html, 'Cetak bukti laporan') && str_contains($html, '123456') && !str_contains($html, 'id="complaint-form"'), 'Bukti laporan salah.');
$seed = file_get_contents(dirname(__DIR__) . '/database/seeds/demo-complaints.sql');
if ($seed === false) throw new RuntimeException('Seed pengaduan tidak tersedia.');
$pdo->exec($seed);
$pdo->exec($seed);
$demoRows = $pdo->query("SELECT * FROM complaints WHERE public_code IN ('AD-DE000000000000000001','AD-DE000000000000000002','AD-DE000000000000000003') ORDER BY public_code")->fetchAll();
check_complaint(count($demoRows) === 3, 'Seed harus idempoten menghasilkan 3 laporan.');
foreach ($demoRows as $demo) {
    check_complaint(password_verify('123456', $demo['pin_hash']) && strlen($demo['request_key']) === 48, 'PIN/key dummy harus valid.');
    check_complaint(count(lookup_complaint($pdo, $demo['public_code'], '123456')['events']) === 1, 'Seed event harus idempoten.');
}
$demoId = (int) $demoRows[0]['id'];
update_complaint($pdo, $demoId, 1, 'new', 'reviewing', 'DEMO - ditinjau', 'Catatan uji', 1);
$pdo->exec($seed);
check_complaint(lookup_complaint($pdo, $demoRows[0]['public_code'], '123456')['status'] === 'reviewing', 'Seed ulang tidak boleh menimpa proses admin.');
check_complaint(count(lookup_complaint($pdo, $demoRows[0]['public_code'], '123456')['events']) === 2, 'Riwayat tidak boleh ditimpa/ditambah seed ulang.');
function escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$complaintAdminError = '';
$complaintRecord = null;
$complaintId = 0;
$complaintTotal = 3;
$complaintPage = 1;
$complaintFilter = '';
$complaintRecords = $demoRows;
$complaintRecords[0]['name'] = '<script>nama</script>';
$complaintRecords[0]['contact'] = '<img src=x>';
$complaintRecords[0]['location'] = '<script>lokasi</script>';
ob_start();
require dirname(__DIR__) . '/app/views/admin-complaints.php';
$html = ob_get_clean();
foreach (['Pengadu / kontak', 'Anonim (nama tidak diisi)', 'Kontak tidak diisi', 'Lokasi:', 'data-auto-submit', '<noscript><button', 'submission-status-new', '&lt;script&gt;nama&lt;/script&gt;'] as $text) {
    check_complaint(str_contains($html, $text), 'Tabel admin tidak lengkap: ' . $text);
}
check_complaint(substr_count($html, 'class="button button-primary"') === 3, 'Periksa harus berupa tombol biru.');
check_complaint(!str_contains($html, '<script>nama') && !str_contains($html, '<img src=x>') && !str_contains($html, '<script>lokasi'), 'Identitas/lokasi admin harus di-escape.');
echo "PASS: migrasi, anonim, aspirasi, validasi batas, persetujuan, idempotensi, hash PIN, privasi lookup, transisi, tanggapan, konflik versi, rollback, retensi/cascade, seed DEMO idempoten, tabel pengadu, filter, badge, UI dan escaping.\n";
