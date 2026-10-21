<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/submission-photos.php';

try {
    $pdo = open_database();
    $expired = $pdo->query('SELECT id FROM submissions WHERE expires_at <= CURRENT_TIMESTAMP')->fetchAll();
    $attachments = $pdo->prepare('SELECT stored_name FROM submission_attachments WHERE submission_id = ?');
    $letters = $pdo->prepare('SELECT stored_name FROM submission_letters WHERE submission_id = ?');
    $delete = $pdo->prepare('DELETE FROM submissions WHERE id = ? AND expires_at <= CURRENT_TIMESTAMP');
    $count = 0;
    foreach ($expired as $row) {
        $attachments->execute([$row['id']]);
        delete_submission_photos($attachments->fetchAll());
        $letters->execute([$row['id']]);
        delete_submission_photos($letters->fetchAll());
        $delete->execute([$row['id']]);
        $count += $delete->rowCount();
    }
    fwrite(STDOUT, "{$count} pengajuan kedaluwarsa beserta foto dan riwayat dihapus.\n");
} catch (Throwable $error) {
    error_log('Penghapusan retensi gagal: ' . get_class($error));
    fwrite(STDERR, "Penghapusan retensi gagal. Periksa izin database/storage dan jalankan ulang.\n");
    exit(1);
}
