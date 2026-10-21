<?php
declare(strict_types=1);

require_once __DIR__ . '/submissions.php';
require_once __DIR__ . '/submission-letters.php';
$adminError = '';
$submission = null;
$attachments = [];
$history = [];
$records = [];
$total = 0;
$pageNumber = max(1, filter_var($_GET['p'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$filterStatus = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
if ($filterStatus !== '' && !isset(submission_statuses()[$filterStatus])) {
    http_response_code(400);
    exit('Filter status tidak valid.');
}
$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($id === false) {
    http_response_code(400);
    exit('ID tidak valid.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadedLetter = null;
    try {
        if (!verify_csrf_token()) throw new DomainException('Sesi formulir berakhir. Muat ulang.');
        foreach (['expected_status', 'status', 'public_note', 'internal_note'] as $key) {
            if (!is_string($_POST[$key] ?? null)) throw new DomainException('Formulir tidak valid.');
        }
        if ($_POST['status'] === 'completed' && ($_POST['letter_certified'] ?? '') !== '1') throw new DomainException('Konfirmasikan PDF telah diperiksa dan disahkan sebelum menyelesaikan.');
        $uploadedLetter = store_submission_letter($_FILES['letter'] ?? []);
        change_submission_status(db(), $id, $_POST['expected_status'], $_POST['status'], trim($_POST['public_note']), trim($_POST['internal_note']), (int) $admin['id'], $uploadedLetter);
        $uploadedLetter = null;
        redirect_to('/admin/dashboard.php?module=submissions&id=' . $id . '&saved=1');
    } catch (DomainException | InvalidArgumentException $error) {
        if ($uploadedLetter !== null) delete_submission_photos([$uploadedLetter]);
        http_response_code(422);
        $adminError = $error->getMessage();
    } catch (RuntimeException $error) {
        if ($uploadedLetter !== null) delete_submission_photos([$uploadedLetter]);
        error_log('Pembaruan pengajuan gagal: ' . get_class($error));
        http_response_code(503);
        $adminError = 'Status gagal disimpan. Muat ulang untuk memeriksa status terkini sebelum mencoba lagi.';
    }
}
if ($id > 0) {
    $query = db()->prepare('SELECT * FROM submissions WHERE id = ? AND expires_at > CURRENT_TIMESTAMP');
    $query->execute([$id]);
    $submission = $query->fetch();
    if (!$submission) {
        http_response_code(404);
        $adminError = 'Pengajuan tidak ditemukan atau melewati masa retensi.';
    } else {
        $query = db()->prepare('SELECT id, document_name, size_bytes FROM submission_attachments WHERE submission_id = ? ORDER BY id');
        $query->execute([$id]);
        $attachments = $query->fetchAll();
        $query = db()->prepare('SELECT submission_events.*, users.name AS officer_name FROM submission_events LEFT JOIN users ON users.id = submission_events.changed_by WHERE submission_id = ? ORDER BY submission_events.id DESC');
        $query->execute([$id]);
        $history = $query->fetchAll();
        $finalLetter = public_submission_letter(db(), $submission['public_code']);
    }
} else {
    $where = ' WHERE expires_at > CURRENT_TIMESTAMP';
    $parameters = [];
    if ($filterStatus !== '') {
        $where .= ' AND status = ?';
        $parameters[] = $filterStatus;
    }
    $count = db()->prepare('SELECT COUNT(*) FROM submissions' . $where);
    $count->execute($parameters);
    $total = (int) $count->fetchColumn();
    $pageNumber = min($pageNumber, max(1, (int) ceil($total / 20)));
    $offset = ($pageNumber - 1) * 20;
    $query = db()->prepare('SELECT id, public_code, service_name, name, status, is_demo, created_at FROM submissions' . $where . ' ORDER BY id DESC LIMIT 20 OFFSET ' . $offset);
    $query->execute($parameters);
    $records = $query->fetchAll();
}
