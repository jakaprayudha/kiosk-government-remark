<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode tidak diizinkan.');
}

if (!verify_csrf_token()) {
    http_response_code(400);
    exit('Sesi formulir berakhir. Muat ulang halaman dan coba kembali.');
}

$admin = current_admin();
if ($admin !== null) {
    record_admin_event((int) $admin['id'], 'logout', 'Admin keluar dari panel.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'] ?? 'Lax',
    ]);
}
session_destroy();
redirect_to('/admin/login.php');
