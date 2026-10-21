<?php
declare(strict_types=1);

require_once __DIR__ . '/audit.php';
$auditToday = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
$auditFilters = [
    'from' => $_GET['from'] ?? $auditToday->format('Y-m-01'),
    'until' => $_GET['until'] ?? $auditToday->format('Y-m-d'),
    'action' => $_GET['action'] ?? '',
    'q' => $_GET['q'] ?? '',
    'order' => $_GET['order'] ?? 'desc',
];
$auditError = '';
$audit = null;
try {
    $audit = audit_list(
        db(), $auditFilters['from'], $auditFilters['until'], $auditFilters['action'],
        $auditFilters['q'], $auditFilters['order'], $_GET['p'] ?? '1'
    );
} catch (DomainException $error) {
    http_response_code(422);
    $auditError = $error->getMessage();
} catch (RuntimeException $error) {
    error_log('Audit aktivitas gagal dimuat: ' . $error->getMessage());
    http_response_code(503);
    $auditError = 'Audit aktivitas gagal dimuat. Muat ulang atau hubungi pengelola database.';
}
$auditFilters = array_map(static fn ($value): string => is_string($value) ? $value : '', $auditFilters);
