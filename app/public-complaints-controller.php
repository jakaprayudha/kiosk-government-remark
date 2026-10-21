<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/submissions.php';
require_once __DIR__ . '/complaints.php';

$complaintError = '';
$complaintReceipt = null;
$complaintResult = null;
$complaintInput = ['kind' => 'complaint', 'category' => 'service', 'title' => '', 'location' => '', 'description' => '', 'name' => '', 'contact' => ''];
$complaintKey = '';
$complaintTab = $_GET['tab'] ?? 'laporan';
if (!is_string($complaintTab) || !in_array($complaintTab, ['laporan', 'tanggapan'], true)) {
    http_response_code(400);
    exit('Tab pengaduan tidak valid.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaintTab = ($_POST['action'] ?? '') === 'lookup' ? 'tanggapan' : 'laporan';
}
try {
    $pdo = open_database();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!public_csrf_valid()) throw new DomainException('Sesi formulir berakhir. Muat ulang dan coba kembali.');
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $complaintKey = is_string($_POST['request_key'] ?? null) ? $_POST['request_key'] : '';
            $request = $_SESSION['complaint_requests'][$complaintKey] ?? null;
            if (!is_array($request)) throw new DomainException('Formulir tidak valid. Muat ulang halaman.');
            if (isset($request['code'])) public_redirect('kiosk.php?menu=pengaduan&bukti=' . $request['code']);
            foreach ($complaintInput as $key => $value) {
                $complaintInput[$key] = is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
            }
            public_attempt($pdo, 'create', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $code = create_complaint($pdo, $_POST, $complaintKey, $request['pin']);
            $_SESSION['complaint_requests'][$complaintKey]['code'] = $code;
            $_SESSION['complaint_receipts'] = [$code => $request['pin']];
            public_redirect('kiosk.php?menu=pengaduan&bukti=' . $code);
        } elseif ($action === 'lookup') {
            public_attempt($pdo, 'lookup', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $code = is_string($_POST['ticket'] ?? null) ? $_POST['ticket'] : '';
            $pin = is_string($_POST['pin'] ?? null) ? $_POST['pin'] : '';
            $complaintResult = lookup_complaint($pdo, $code, $pin);
        } else {
            throw new DomainException('Aksi laporan tidak valid.');
        }
    } elseif ($complaintTab === 'laporan' && isset($_GET['bukti'])) {
        $code = is_string($_GET['bukti']) ? $_GET['bukti'] : '';
        $pin = $_SESSION['complaint_receipts'][$code] ?? null;
        if (!is_string($pin)) throw new DomainException('Bukti tidak tersedia pada sesi ini. Gunakan cek tanggapan dengan kode dan PIN.');
        $complaintReceipt = lookup_complaint($pdo, $code, $pin);
        $complaintReceipt['pin'] = $pin;
    }
} catch (DomainException $error) {
    http_response_code(422);
    $complaintError = $error->getMessage();
} catch (RuntimeException $error) {
    error_log('Pengaduan gagal diproses: ' . get_class($error));
    http_response_code(503);
    $complaintError = 'Laporan belum dapat diproses. Tidak ada konfirmasi pengiriman; coba kembali atau hubungi petugas.';
}
if ($complaintTab === 'laporan' && !$complaintReceipt && ($complaintKey === '' || !isset($_SESSION['complaint_requests'][$complaintKey]))) {
    $complaintKey = bin2hex(random_bytes(24));
    if (count($_SESSION['complaint_requests'] ?? []) >= 10) $_SESSION['complaint_requests'] = [];
    $_SESSION['complaint_requests'][$complaintKey] = ['pin' => (string) random_int(100000, 999999)];
}
