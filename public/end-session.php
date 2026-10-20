<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/public-session.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Gunakan POST.');
}
if (!public_csrf_valid()) {
    http_response_code(400);
    exit('Sesi formulir berakhir.');
}
$_SESSION = [];
session_regenerate_id(true);
public_redirect('kiosk.php');
