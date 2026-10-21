<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/public-session.php';
require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/submission-letters.php';
$code = is_string($_GET['tiket'] ?? null) ? $_GET['tiket'] : '';
$grant = $_SESSION['letter_access'][$code] ?? 0;
if (!preg_match('/^[A-F0-9]{20}$/', $code) || !is_int($grant) || $grant < time()) {
    http_response_code(403);
    exit('Akses surat berakhir. Cek tiket dan PIN kembali di KIOSK.');
}
try {
    $letter = public_submission_letter(open_database(), $code);
    if (!$letter) {
        http_response_code(404);
        exit('Surat belum tersedia atau telah melewati retensi.');
    }
    stream_submission_letter($letter, ($_GET['download'] ?? '') === '1');
} catch (RuntimeException $error) {
    error_log('Akses surat gagal: ' . get_class($error));
    http_response_code(503);
    exit('Surat tidak dapat dimuat. Hubungi petugas.');
}
