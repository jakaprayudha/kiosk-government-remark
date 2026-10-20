<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/submission-photos.php';
require_admin();
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$query = db()->prepare('SELECT submission_attachments.stored_name FROM submission_attachments JOIN submissions ON submissions.id = submission_attachments.submission_id WHERE submission_attachments.id = ? AND submissions.expires_at > CURRENT_TIMESTAMP');
$query->execute([$id ?: 0]);
$filename = $query->fetchColumn();
if (!$filename || !preg_match('/^[a-f0-9]{48}\.jpg$/', $filename) || !is_file(submission_upload_dir() . '/' . $filename)) {
    http_response_code(404);
    exit('Lampiran tidak tersedia.');
}
header('Content-Type: image/jpeg');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="dokumen.jpg"');
header('Cache-Control: no-store');
header('Content-Security-Policy: default-src \'none\'');
readfile(submission_upload_dir() . '/' . $filename);
