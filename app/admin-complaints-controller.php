<?php
declare(strict_types=1);
require_once __DIR__ . '/complaints.php';
$complaintAdminError = '';
$complaintRecord = null;
$complaintHistory = [];
$complaintRecords = [];
$complaintTotal = 0;
$complaintPage = max(1, filter_var($_GET['p'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$complaintFilter = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$complaintId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($complaintId === false || ($complaintFilter !== '' && !isset(complaint_statuses()[$complaintFilter]))) {
    http_response_code(400);
    exit('ID atau filter laporan tidak valid.');
}
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token()) throw new DomainException('Sesi formulir berakhir. Muat ulang.');
        foreach (['expected_status', 'status', 'public_message', 'internal_note'] as $key) {
            if (!is_string($_POST[$key] ?? null)) throw new DomainException('Formulir tidak valid.');
        }
        $version = filter_var($_POST['version'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($version === false) throw new DomainException('Versi laporan tidak valid. Muat ulang.');
        update_complaint(db(), $complaintId, $version, $_POST['expected_status'], $_POST['status'], $_POST['public_message'], $_POST['internal_note'], (int) $admin['id']);
        redirect_to('/admin/dashboard.php?module=complaints&id=' . $complaintId . '&saved=1');
    }
} catch (DomainException | InvalidArgumentException $error) {
    http_response_code(422);
    $complaintAdminError = $error->getMessage();
} catch (RuntimeException $error) {
    error_log('Tindak lanjut pengaduan gagal: ' . get_class($error));
    http_response_code(503);
    $complaintAdminError = 'Tindak lanjut gagal disimpan. Muat ulang untuk memeriksa data terbaru.';
}
try {
    if ($complaintId > 0) {
        $query = db()->prepare('SELECT * FROM complaints WHERE id=? AND expires_at>CURRENT_TIMESTAMP');
        $query->execute([$complaintId]);
        $complaintRecord = $query->fetch();
        if (!$complaintRecord) {
            http_response_code(404);
            $complaintAdminError = 'Laporan tidak ditemukan atau melewati masa retensi.';
        } else {
            $query = db()->prepare('SELECT e.*,u.name AS officer_name FROM complaint_events e LEFT JOIN users u ON u.id=e.changed_by WHERE complaint_id=? ORDER BY e.id DESC');
            $query->execute([$complaintId]);
            $complaintHistory = $query->fetchAll();
        }
    } else {
        $where = ' WHERE expires_at>CURRENT_TIMESTAMP' . ($complaintFilter !== '' ? ' AND status=?' : '');
        $params = $complaintFilter !== '' ? [$complaintFilter] : [];
        $query = db()->prepare('SELECT COUNT(*) FROM complaints' . $where);
        $query->execute($params);
        $complaintTotal = (int) $query->fetchColumn();
        $complaintPage = min($complaintPage, max(1, (int) ceil($complaintTotal / 20)));
        $query = db()->prepare('SELECT id,public_code,kind,category,title,name,contact,location,status,created_at FROM complaints' . $where . ' ORDER BY id DESC LIMIT 20 OFFSET ' . (($complaintPage - 1) * 20));
        $query->execute($params);
        $complaintRecords = $query->fetchAll();
    }
} catch (RuntimeException $error) {
    error_log('Daftar pengaduan gagal dimuat: ' . get_class($error));
    http_response_code(503);
    $complaintAdminError = 'Data pengaduan belum dapat dimuat. Hubungi pengelola database.';
}
