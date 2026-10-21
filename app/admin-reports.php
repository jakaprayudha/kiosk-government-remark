<?php
declare(strict_types=1);
require_once __DIR__ . '/reports.php';
require_once __DIR__ . '/submissions.php';
require_once __DIR__ . '/complaints.php';
require_once __DIR__ . '/queues.php';
$reportToday = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
$reportStart = $_GET['from'] ?? $reportToday->format('Y-m-01');
$reportEnd = $_GET['until'] ?? $reportToday->format('Y-m-d');
$reportError = '';
$report = null;
try {
    $report = operational_report(db(), $reportStart, $reportEnd);
} catch (DomainException $error) {
    http_response_code(422);
    $reportError = $error->getMessage();
} catch (RuntimeException $error) {
    error_log('Laporan operasional gagal: ' . $error->getMessage());
    http_response_code(503);
    $reportError = 'Laporan gagal dimuat. Muat ulang atau hubungi pengelola database.';
}
$reportStart = is_string($reportStart) ? $reportStart : '';
$reportEnd = is_string($reportEnd) ? $reportEnd : '';
$reportGroups = [
    'submissions' => ['label' => 'Pengajuan layanan', 'statuses' => submission_statuses()],
    'complaints' => ['label' => 'Pengaduan & aspirasi', 'statuses' => complaint_statuses()],
    'queue_bookings' => ['label' => 'Pemesanan antrean DEMO', 'statuses' => queue_statuses()],
];
