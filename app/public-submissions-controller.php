<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/service-catalog.php';
require_once __DIR__ . '/submissions.php';
require_once __DIR__ . '/submission-photos.php';

$transactionError = '';
$transactionService = null;
$transactionRequirements = [];
$receipt = null;
$ticketStatus = null;
$ticketEvents = [];
$formKey = '';
$input = ['name' => '', 'contact' => '', 'description' => ''];
$today = $now->format('Y-m-d');

try {
    $pdo = open_database();
    if ($selected === 'pengajuan') {
        $id = filter_var($_GET['layanan'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) throw new DomainException('Pilih layanan dari katalog untuk memulai pengajuan.');
        $matches = public_service_catalog($pdo, $today, '', $id);
        $transactionService = $matches[0] ?? null;
        if (!$transactionService || (int) $transactionService['accepts_submissions'] !== 1) {
            http_response_code(404);
            throw new DomainException('Layanan tidak tersedia untuk pengajuan melalui KIOSK.');
        }
        $transactionRequirements = public_service_requirements($pdo, $id);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!public_csrf_valid()) {
                http_response_code(400);
                throw new DomainException('Formulir berakhir atau unggahan terlalu besar. Muat ulang dan coba kembali.');
            }
            $formKey = is_string($_POST['request_key'] ?? null) ? $_POST['request_key'] : '';
            $request = $_SESSION['requests'][$formKey] ?? null;
            if (!is_array($request) || $request['service_id'] !== $id) throw new DomainException('Formulir tidak valid. Muat ulang halaman.');
            if (isset($request['code'])) {
                public_redirect('kiosk.php?menu=bukti&tiket=' . $request['code']);
            }
            foreach (array_keys($input) as $key) {
                $input[$key] = is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
            }
            validate_submission($transactionService, $_POST);
            public_attempt($pdo, 'create', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $photos = store_submission_photos($_FILES['photos'] ?? [], $transactionRequirements, (int) $transactionService['camera_enabled'] === 1);
            try {
                $code = create_submission($pdo, $transactionService, $_POST, $formKey, $request['pin'], $photos);
            } catch (Throwable $error) {
                delete_submission_photos($photos);
                throw $error;
            }
            $_SESSION['requests'][$formKey]['code'] = $code;
            $_SESSION['receipts'] = [$code => $request['pin']];
            public_redirect('kiosk.php?menu=bukti&tiket=' . $code);
        }
        if ($formKey === '') {
            $formKey = bin2hex(random_bytes(24));
            if (count($_SESSION['requests'] ?? []) >= 10) $_SESSION['requests'] = [];
            $_SESSION['requests'][$formKey] = ['service_id' => $id, 'pin' => (string) random_int(100000, 999999)];
        }
    } elseif ($selected === 'bukti') {
        $code = is_string($_GET['tiket'] ?? null) ? $_GET['tiket'] : '';
        if (!isset($_SESSION['receipts'][$code])) {
            http_response_code(403);
            throw new DomainException('Bukti tidak tersedia pada sesi ini. Gunakan cek status dengan tiket dan PIN.');
        }
        $query = $pdo->prepare('SELECT public_code, service_name, is_demo, status, created_at FROM submissions WHERE public_code = ? AND expires_at > CURRENT_TIMESTAMP');
        $query->execute([$code]);
        $receipt = $query->fetch();
        if (!$receipt) throw new DomainException('Tiket tidak ditemukan atau telah melewati masa retensi.');
    } elseif ($selected === 'status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!public_csrf_valid()) {
            http_response_code(400);
            throw new DomainException('Sesi formulir berakhir. Muat ulang halaman.');
        }
        public_attempt($pdo, 'lookup', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $code = is_string($_POST['ticket'] ?? null) ? strtoupper(trim($_POST['ticket'])) : '';
        $pin = is_string($_POST['pin'] ?? null) ? $_POST['pin'] : '';
        if (str_starts_with($code, 'KIOSK:V1:')) $code = substr($code, 9);
        if (!preg_match('/^[A-F0-9]{20}$/', $code) || !preg_match('/^[0-9]{6}$/', $pin)) {
            throw new DomainException('Tiket atau PIN tidak sesuai.');
        }
        $query = $pdo->prepare('SELECT id, public_code, pin_hash, service_name, is_demo, status, public_note, created_at, updated_at FROM submissions WHERE public_code = ? AND expires_at > CURRENT_TIMESTAMP');
        $query->execute([$code]);
        $match = $query->fetch();
        $hash = $match ? $match['pin_hash'] : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        if (!password_verify($pin, $hash) || !$match) throw new DomainException('Tiket atau PIN tidak sesuai.');
        unset($match['pin_hash']);
        $ticketStatus = $match;
        $eventsQuery = $pdo->prepare('SELECT new_status, public_message, created_at FROM submission_events WHERE submission_id = ? ORDER BY id');
        $eventsQuery->execute([$match['id']]);
        $ticketEvents = $eventsQuery->fetchAll();
    }
} catch (DomainException $error) {
    if (http_response_code() === 200) http_response_code(422);
    $transactionError = $error->getMessage();
} catch (RuntimeException $error) {
    error_log('Transaksi KIOSK gagal: ' . get_class($error));
    http_response_code(503);
    $transactionError = 'Transaksi tidak dapat diproses saat ini. Jangan kirim ulang berulang kali; hubungi petugas. Jika sudah memperoleh tiket, gunakan tiket tersebut.';
}
