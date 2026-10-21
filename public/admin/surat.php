<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/submission-letters.php';
require_admin();
$code = is_string($_GET['tiket'] ?? null) ? $_GET['tiket'] : '';
$letter = public_submission_letter(db(), $code);
if (!$letter) {
    http_response_code(404);
    exit('Surat tidak tersedia.');
}
stream_submission_letter($letter);
