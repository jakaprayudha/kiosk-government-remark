<?php
declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
session_name('kiosk_public_session');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();
header('Cache-Control: no-store');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 140) {
    $_SESSION = [];
    session_regenerate_id(true);
}
$_SESSION['last_activity'] = time();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

function public_csrf_valid(): bool
{
    return is_string($_POST['csrf'] ?? null) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

function public_redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}
