<?php
declare(strict_types=1);
require_once __DIR__ . '/announcements.php';
$announcementError = '';
$announcementRecords = [];
$announcementId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($announcementId === false) { http_response_code(400); exit('ID pengumuman tidak valid.'); }
$announcementForm = ['title' => '', 'body' => '', 'starts_at' => '', 'ends_at' => '', 'priority' => 0, 'is_published' => 0, 'version' => 0];
$announcementModalOpen = isset($_GET['new']) || ($announcementId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST')
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save');
try {
    if ($announcementId > 0) {
        $query = db()->prepare('SELECT * FROM announcements WHERE id=?');
        $query->execute([$announcementId]);
        $row = $query->fetch();
        if (!$row) { http_response_code(404); throw new DomainException('Pengumuman tidak ditemukan.'); }
        $announcementForm = $row;
        foreach (['starts_at', 'ends_at'] as $key) $announcementForm[$key] = str_replace(' ', 'T', $row[$key] ?? '');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token()) throw new DomainException('Sesi formulir berakhir. Muat ulang halaman.');
        $version = filter_var($_POST['version'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($version === false) throw new DomainException('Versi formulir tidak valid.');
        if (($_POST['action'] ?? '') === 'save') {
            foreach (['title', 'body', 'starts_at', 'ends_at', 'priority'] as $key) {
                if (is_string($_POST[$key] ?? null)) $announcementForm[$key] = $_POST[$key];
            }
            $announcementForm['is_published'] = ($_POST['is_published'] ?? '') === '1' ? 1 : 0;
            $announcementForm['version'] = $version;
        }
        mutate_announcement(db(), is_string($_POST['action'] ?? null) ? $_POST['action'] : '', $_POST, $announcementId, $version, (int) $admin['id']);
        redirect_to('/admin/dashboard.php?module=announcements&' . (($_POST['action'] ?? '') === 'delete' ? 'deleted' : 'saved') . '=1');
    }
} catch (DomainException $error) {
    if (http_response_code() !== 404) http_response_code(422);
    $announcementError = $error->getMessage();
} catch (RuntimeException $error) {
    error_log('Pengumuman gagal diproses: ' . get_class($error));
    http_response_code(503);
    $announcementError = 'Pengumuman gagal diproses. Muat ulang atau hubungi pengelola.';
}
try {
    $announcementRecords = db()->query('SELECT * FROM announcements ORDER BY priority DESC,created_at DESC,id DESC')->fetchAll();
} catch (RuntimeException $error) {
    error_log('Daftar pengumuman gagal: ' . get_class($error));
    http_response_code(503);
    $announcementError = 'Daftar pengumuman gagal dimuat.';
}
