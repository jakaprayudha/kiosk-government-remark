<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/queues.php';

function queue_test_db(string $dsn): PDO
{
    $pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec('PRAGMA foreign_keys=ON');
    $pdo->exec('PRAGMA busy_timeout=5000');
    return $pdo;
}
if (($argv[1] ?? '') === 'worker') {
    $pdo = queue_test_db('sqlite:' . $argv[2]);
    try {
        echo create_queue_booking($pdo, 1, bin2hex(random_bytes(24)), '123456', true);
    } catch (DomainException $error) {
        echo 'FULL';
    }
    exit;
}
function queue_check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function queue_reject(callable $operation, string $message): void
{
    try { $operation(); } catch (DomainException | InvalidArgumentException $error) { return; }
    throw new RuntimeException($message);
}
$pdo = queue_test_db('sqlite::memory:');
run_migrations($pdo);
run_migrations($pdo);
$pdo->exec("INSERT INTO users(id,name,username,password_hash) VALUES(1,'Test','test','test')");
save_queue_agency($pdo, ['name' => 'DEMO Test', 'kind' => 'dukcapil', 'description' => '', 'address' => '', 'is_active' => 1], 1);
save_queue_service($pdo, ['agency_id' => 1, 'name' => 'DEMO Layanan', 'instructions' => '<script>Contoh</script>', 'is_active' => 1], 1);
save_queue_agency($pdo, ['id' => 1, 'name' => 'DEMO Test', 'kind' => 'dukcapil', 'description' => 'Diperbarui', 'address' => 'Contoh', 'is_active' => 1], 1);
save_queue_service($pdo, ['id' => 1, 'agency_id' => 1, 'name' => 'DEMO Layanan', 'instructions' => '<script>Contoh</script>', 'is_active' => 1], 1);
queue_reject(fn() => save_queue_service($pdo, ['id' => 1, 'agency_id' => 999, 'name' => 'Pindah', 'instructions' => '', 'is_active' => 1], 1), 'Layanan pindah instansi.');
$date = queue_now()->format('Y-m-d');
$tomorrow = queue_now()->modify('+1 day')->format('Y-m-d');
$slot = ['service_id' => 1, 'service_date' => $date, 'start_time' => '00:00', 'end_time' => '23:59', 'capacity' => 2, 'is_open' => 1];
save_queue_slot($pdo, $slot, 1);
foreach ([
    ['service_date' => '2026-02-30'], ['start_time' => '24:00'], ['end_time' => '00:00'],
    ['capacity' => 0], ['capacity' => 501], ['service_date' => queue_now()->modify('+31 days')->format('Y-m-d')],
    ['service_date' => queue_now()->modify('-1 day')->format('Y-m-d')],
] as $bad) queue_reject(fn() => save_queue_slot($pdo, [...$slot, ...$bad], 1), 'Jadwal buruk diterima.');
queue_reject(fn() => save_queue_slot($pdo, [...$slot, 'start_time' => '01:00'], 1), 'Jadwal tumpang tindih diterima.');
queue_reject(fn() => save_queue_agency($pdo, ['name' => '', 'kind' => 'x'], 1), 'Instansi buruk diterima.');
$key = bin2hex(random_bytes(24));
queue_reject(fn() => create_queue_booking($pdo, 1, $key, '123456', false), 'Persetujuan DEMO tidak diperiksa.');
$code = create_queue_booking($pdo, 1, $key, '123456', true);
queue_check(create_queue_booking($pdo, 1, $key, '123456', true) === $code, 'Idempotensi gagal.');
queue_check((int) $pdo->query('SELECT occupied FROM queue_slots WHERE id=1')->fetchColumn() === 1, 'Kuota duplikat.');
queue_reject(fn() => lookup_queue_booking($pdo, $code, '000000'), 'PIN salah diterima.');
$record = lookup_queue_booking($pdo, strtolower($code), '123456');
queue_check(queue_number($record) === 'Q1-001' && !isset($record['pin_hash'], $record['request_key']), 'Nomor atau privasi salah.');
queue_check(!isset($record['pin_hash']) && !isset($record['request_key']), 'Kredensial lookup bocor.');
queue_check(!str_contains(json_encode($record), 'pin_hash'), 'Hash PIN bocor.');
queue_check(password_verify('123456', $pdo->query('SELECT pin_hash FROM queue_bookings')->fetchColumn()), 'Hash PIN tidak benar.');
$second = create_queue_booking($pdo, 1, bin2hex(random_bytes(24)), '234567', true);
queue_reject(fn() => create_queue_booking($pdo, 1, bin2hex(random_bytes(24)), '345678', true), 'Kuota terlampaui.');
queue_reject(fn() => change_queue_booking($pdo, 2, 1, 'called', 1), 'Urutan panggilan salah.');
change_queue_booking($pdo, 1, 1, 'called', 1);
queue_reject(fn() => change_queue_booking($pdo, 2, 1, 'called', 1), 'Dua panggilan bersamaan.');
queue_reject(fn() => change_queue_booking($pdo, 1, 1, 'completed', 1), 'Versi stale diterima.');
change_queue_booking($pdo, 1, 2, 'completed', 1);
queue_reject(fn() => change_queue_booking($pdo, 1, 3, 'cancelled', 1), 'Status akhir berubah.');
change_queue_booking($pdo, 2, 1, 'cancelled', 1);
queue_check((int) $pdo->query('SELECT occupied FROM queue_slots WHERE id=1')->fetchColumn() === 1, 'Kuota pembatalan tidak kembali.');
$third = create_queue_booking($pdo, 1, bin2hex(random_bytes(24)), '345678', true);
queue_check(lookup_queue_booking($pdo, $third, '345678')['number'] === 3, 'Nomor pembatalan dipakai ulang.');
queue_reject(fn() => save_queue_slot($pdo, ['id' => 1, 'version' => 1, 'capacity' => 2, 'is_open' => 1], 1), 'Versi jadwal stale diterima.');
$version = (int) $pdo->query('SELECT version FROM queue_slots WHERE id=1')->fetchColumn();
queue_reject(fn() => save_queue_slot($pdo, ['id' => 1, 'version' => $version, 'capacity' => 1, 'is_open' => 1], 1), 'Kuota di bawah terisi diterima.');
save_queue_slot($pdo, ['id' => 1, 'version' => $version, 'capacity' => 3, 'is_open' => 0], 1);
queue_reject(fn() => create_queue_booking($pdo, 1, bin2hex(random_bytes(24)), '456789', true), 'Jadwal ditutup dapat dipesan.');
save_queue_slot($pdo, [...$slot, 'service_date' => $tomorrow, 'capacity' => 3], 1);
$future = create_queue_booking($pdo, 2, bin2hex(random_bytes(24)), '456789', true);
queue_reject(fn() => change_queue_booking($pdo, 4, 1, 'called', 1), 'Janji masa depan dapat dipanggil.');
$futureRecord = lookup_queue_booking($pdo, $future, '456789');
$expiry = new DateTimeImmutable($futureRecord['expires_at'], new DateTimeZone('UTC'));
queue_check($expiry->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('Y-m-d') === queue_now()->modify('+31 days')->format('Y-m-d'), 'Retensi bukan 30 hari setelah tanggal layanan.');
$pdo->exec('UPDATE queue_services SET is_active=0 WHERE id=1');
queue_reject(fn() => create_queue_booking($pdo, 2, bin2hex(random_bytes(24)), '567890', true), 'Layanan nonaktif bisa dipesan.');
$pdo->exec('UPDATE queue_services SET is_active=1 WHERE id=1');
$pdo->exec('UPDATE queue_agencies SET is_active=0 WHERE id=1');
queue_reject(fn() => create_queue_booking($pdo, 2, bin2hex(random_bytes(24)), '567890', true), 'Instansi nonaktif bisa dipesan.');
queue_check(lookup_queue_booking($pdo, $future, '456789')['public_code'] === $future, 'Bukti lama hilang saat instansi nonaktif.');
$pdo->exec("UPDATE queue_slots SET service_date='2000-01-01' WHERE id=2");
queue_reject(fn() => create_queue_booking($pdo, 2, bin2hex(random_bytes(24)), '567890', true), 'Jadwal lampau bisa dipesan.');
$pdo->exec("UPDATE queue_bookings SET expires_at=datetime('now','-1 day') WHERE id=4");
queue_reject(fn() => lookup_queue_booking($pdo, $future, '456789'), 'Bukti kedaluwarsa dapat diakses.');
$pdo->exec('DELETE FROM queue_bookings WHERE id=4');
queue_check((int) $pdo->query('SELECT COUNT(*) FROM queue_events WHERE booking_id=4')->fetchColumn() === 0, 'Retensi tidak cascade.');
try {
    change_queue_booking($pdo, 3, 1, 'called', 999);
    throw new RuntimeException('FK gagal diperiksa.');
} catch (PDOException $error) {
    queue_check($pdo->query('SELECT status FROM queue_bookings WHERE id=3')->fetchColumn() === 'waiting', 'Transaksi panggilan tidak rollback.');
}

// Two independent processes compete for the final seat in a file-backed database.
$path = tempnam(sys_get_temp_dir(), 'kiosk-queue-test-');
if ($path === false) throw new RuntimeException('Tidak dapat membuat database uji.');
try {
    $file = queue_test_db('sqlite:' . $path);
    run_migrations($file);
    $file->exec("INSERT INTO queue_agencies(id,name,kind) VALUES(1,'Concurrent DEMO','government')");
    $file->exec("INSERT INTO queue_services(id,agency_id,name) VALUES(1,1,'Test')");
    $file->prepare('INSERT INTO queue_slots(id,service_id,service_date,start_time,end_time,capacity) VALUES(1,1,?,?,?,1)')->execute([$tomorrow, '09:00', '10:00']);
    $processes = [];
    for ($i = 0; $i < 2; $i++) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, __FILE__, 'worker', $path], [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
        if ($process === false) throw new RuntimeException('Tidak dapat menjalankan pengujian bersamaan.');
        $processes[] = [$process, $pipes];
    }
    $outputs = [];
    foreach ($processes as [$process, $pipes]) {
        $outputs[] = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        queue_check(proc_close($process) === 0 && $stderr === '', 'Proses konkurensi gagal: ' . $stderr);
    }
    queue_check(count(array_filter($outputs, static fn(string $out): bool => str_starts_with($out, 'AN-'))) === 1 && in_array('FULL', $outputs, true), 'Kuota terakhir tidak atomik.');
    queue_check((int) $file->query('SELECT COUNT(*) FROM queue_bookings')->fetchColumn() === 1, 'Overbooking bersamaan.');
    $file = null;
} finally {
    if (!unlink($path)) throw new RuntimeException('Database uji gagal dibersihkan.');
}

function kiosk_escape(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function kiosk_icon(string $name): string { return ''; }
$_SESSION = ['csrf' => 'test'];
$queueError = '';
$queueReceipt = null;
$queueTab = 'cek';
ob_start(); require dirname(__DIR__) . '/app/views/public-queues.php'; $html = ob_get_clean();
queue_check(str_contains($html, 'SIMULASI DEMO LOKAL') && str_contains($html, 'queue-code') && str_contains($html, 'keyboard-toggle') && !str_contains($html, 'name="slot_id"'), 'Tab cek tidak terpisah.');
$queueReceipt = [...$record, 'pin' => '123456'];
ob_start(); require dirname(__DIR__) . '/app/views/public-queues.php'; $html = ob_get_clean();
queue_check(str_contains($html, '&lt;script&gt;Contoh&lt;/script&gt;') && !str_contains($html, '<script>Contoh</script>') && str_contains($html, 'Q1-001') && str_contains($html, 'Cetak bukti DEMO'), 'Bukti/escaping salah.');
$queueReceipt = null;
$queueTab = 'hari-ini';
$queueAgency = ['id' => 1, 'name' => 'DEMO Test'];
$queueService = ['id' => 1, 'name' => 'DEMO Layanan', 'instructions' => 'Contoh'];
$queueSlots = [['id' => 1, 'service_date' => $date, 'start_time' => '09:00', 'end_time' => '11:00', 'capacity' => 1, 'occupied' => 1, 'is_open' => 1, 'called_number' => null, 'waiting_count' => 1]];
$queueKey = $key;
ob_start(); require dirname(__DIR__) . '/app/views/public-queues.php'; $html = ob_get_clean();
queue_check(str_contains($html, 'Kuota penuh') && !str_contains($html, 'Ambil nomor DEMO'), 'UI kuota penuh salah.');
$queueSlots[0]['occupied'] = 0;
ob_start(); require dirname(__DIR__) . '/app/views/public-queues.php'; $html = ob_get_clean();
queue_check(str_contains($html, 'Sisa kuota 1 dari 1') && str_contains($html, 'Ambil nomor DEMO') && !str_contains($html, 'id="queue-code"'), 'UI jadwal tersedia salah.');
echo "PASS: migrasi, pengelolaan, validasi jadwal, overlap, DEMO, idempotensi, PIN, kuota, pembatalan, nomor unik, urutan panggilan, konflik, rollback, instansi/layanan nonaktif, retensi, UI dan dua proses berebut kuota terakhir.\n";
