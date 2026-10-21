<?php
declare(strict_types=1);
require_once __DIR__ . '/settings.php';
$settingsError = '';
$settingsForm = null;
try {
    $settingsForm = kiosk_settings(db());
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token()) throw new DomainException('Sesi formulir berakhir. Muat ulang halaman.');
        $version = filter_var($_POST['version'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($version === false) throw new DomainException('Versi formulir tidak valid.');
        foreach (['region_name', 'kiosk_title', 'tagline', 'footer_text'] as $key) {
            $settingsForm[$key] = is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
        }
        $settingsForm['version'] = $version;
        $logo = null;
        $file = $_FILES['logo'] ?? null;
        $uploadError = is_array($file) ? ($file['error'] ?? null) : UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_NO_FILE) {
            if ($uploadError !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
                throw new DomainException('Unggah logo gagal. Periksa batas ukuran dan pilih ulang file.');
            }
            if (($_POST['remove_logo'] ?? '') === '1') throw new DomainException('Pilih unggah logo atau hapus logo, bukan keduanya.');
            if (filesize($file['tmp_name']) > 524288) throw new DomainException('Logo maksimal 512 KB.');
            $bytes = file_get_contents($file['tmp_name']);
            if ($bytes === false) throw new RuntimeException('File unggahan logo tidak dapat dibaca.');
            $logo = settings_logo($bytes);
        } elseif (($_POST['remove_logo'] ?? '') === '1') {
            $logo = '';
        }
        save_kiosk_settings(db(), (int) $admin['id'], $_POST, $version, $logo);
        redirect_to('/admin/dashboard.php?module=settings&saved=1');
    }
} catch (DomainException $error) {
    http_response_code(422);
    $settingsError = $error->getMessage();
} catch (RuntimeException $error) {
    error_log('Pengaturan KIOSK gagal: ' . $error->getMessage());
    http_response_code(503);
    $settingsError = 'Pengaturan gagal dimuat atau disimpan. Muat ulang atau hubungi pengelola database.';
}
