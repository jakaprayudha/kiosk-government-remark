<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/submissions.php';
require_once __DIR__ . '/queues.php';

$queueError = '';
$queueReceipt = null;
$queueAgencies = [];
$queueServices = [];
$queueSlots = [];
$queueAgency = null;
$queueService = null;
$queueKey = '';
$queueTab = $_GET['tab'] ?? 'hari-ini';
if (!is_string($queueTab) || !in_array($queueTab, ['hari-ini','janji','cek'], true)) {
    http_response_code(400);
    exit('Pilihan antrean tidak valid.');
}
try {
    $pdo = open_database();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!public_csrf_valid()) throw new DomainException('Sesi formulir berakhir. Muat ulang halaman.');
        if (($_POST['action'] ?? '') === 'book') {
            $key = is_string($_POST['request_key'] ?? null) ? $_POST['request_key'] : '';
            $request = $_SESSION['queue_requests'][$key] ?? null;
            if (!is_array($request)) throw new DomainException('Formulir tidak valid. Pilih layanan kembali.');
            $slot = queue_int($_POST['slot_id'] ?? '');
            if (!in_array($slot, $request['slots'], true)) throw new DomainException('Jadwal tidak sesuai formulir. Muat ulang pilihan.');
            if (isset($request['code'])) public_redirect('kiosk.php?menu=antrean&bukti=' . $request['code']);
            public_attempt($pdo, 'create', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $code = create_queue_booking($pdo, $slot, $key, $request['pin'], ($_POST['confirmed'] ?? '') === '1');
            $_SESSION['queue_requests'][$key]['code'] = $code;
            $_SESSION['queue_receipts'] = [$code => $request['pin']];
            public_redirect('kiosk.php?menu=antrean&bukti=' . $code);
        } elseif (($_POST['action'] ?? '') === 'lookup') {
            $queueTab = 'cek';
            public_attempt($pdo, 'lookup', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $queueReceipt = lookup_queue_booking($pdo, is_string($_POST['ticket'] ?? null) ? $_POST['ticket'] : '', is_string($_POST['pin'] ?? null) ? $_POST['pin'] : '');
        } else {
            throw new DomainException('Aksi antrean tidak valid.');
        }
    } elseif (isset($_GET['bukti'])) {
        $code = is_string($_GET['bukti']) ? $_GET['bukti'] : '';
        $pin = $_SESSION['queue_receipts'][$code] ?? null;
        if (!is_string($pin)) throw new DomainException('Bukti tidak tersedia di sesi ini. Gunakan tab Cek antrean dengan kode dan PIN.');
        $queueReceipt = lookup_queue_booking($pdo, $code, $pin);
        $queueReceipt['pin'] = $pin;
    }
    $queueAgencies = $pdo->query('SELECT * FROM queue_agencies WHERE is_active=1 ORDER BY id')->fetchAll();
    if ($queueTab !== 'cek' && !$queueReceipt) {
        $agencyId = queue_int($_GET['instansi'] ?? 0, 0);
        $serviceId = queue_int($_GET['layanan'] ?? 0, 0);
        if ($agencyId > 0) {
            foreach ($queueAgencies as $agency) if ((int) $agency['id'] === $agencyId) $queueAgency = $agency;
            if (!$queueAgency) throw new DomainException('Instansi tidak tersedia.');
            $q = $pdo->prepare('SELECT * FROM queue_services WHERE agency_id=? AND is_active=1 ORDER BY id');
            $q->execute([$agencyId]);
            $queueServices = $q->fetchAll();
            foreach ($queueServices as $service) if ((int) $service['id'] === $serviceId) $queueService = $service;
            if ($serviceId > 0 && !$queueService) throw new DomainException('Layanan tidak tersedia di instansi ini.');
            if ($queueService) {
                $now = queue_now();
                $q = $pdo->prepare('SELECT t.*,
                    (SELECT number FROM queue_bookings b WHERE b.slot_id=t.id AND b.status=\'called\' LIMIT 1) AS called_number,
                    (SELECT COUNT(*) FROM queue_bookings b WHERE b.slot_id=t.id AND b.status=\'waiting\') AS waiting_count
                    FROM queue_slots t WHERE service_id=? AND service_date ' . ($queueTab === 'janji' ? '>' : '=') . ' ?
                    AND service_date<=? AND service_date||\' \'||end_time>? ORDER BY service_date,start_time');
                $q->execute([$serviceId, $now->format('Y-m-d'), $now->modify('+30 days')->format('Y-m-d'), $now->format('Y-m-d H:i')]);
                $queueSlots = $q->fetchAll();
                $queueKey = bin2hex(random_bytes(24));
                if (count($_SESSION['queue_requests'] ?? []) >= 10) $_SESSION['queue_requests'] = [];
                $_SESSION['queue_requests'][$queueKey] = ['pin' => (string) random_int(100000, 999999),
                    'slots' => array_map(static fn(array $slot): int => (int) $slot['id'], $queueSlots)];
            }
        }
    }
} catch (DomainException $error) {
    http_response_code(422);
    $queueError = $error->getMessage();
} catch (RuntimeException $error) {
    error_log('Antrean gagal diproses: ' . get_class($error));
    http_response_code(503);
    $queueError = 'Antrean belum dapat diproses. Muat ulang untuk memeriksa hasil sebelum mencoba lagi, atau hubungi petugas.';
}
