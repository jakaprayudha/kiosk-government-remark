<?php
declare(strict_types=1);
$settingsError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf_token()) throw new DomainException('Sesi formulir berakhir.');
        $settingId = filter_var($_POST['service_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $retention = filter_var($_POST['retention_days'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 365]]);
        if (!$settingId || !$retention) throw new DomainException('Layanan atau retensi tidak valid.');
        $flags = [];
        foreach (['accepts_submissions', 'requires_name', 'requires_contact', 'camera_enabled', 'requires_nik', 'requires_birth_date', 'requires_face'] as $key) {
            if (isset($_POST[$key]) && $_POST[$key] !== '1') throw new DomainException('Pengaturan tidak valid.');
            $flags[] = isset($_POST[$key]) ? 1 : 0;
        }
        db()->beginTransaction();
        $update = db()->prepare('UPDATE services SET accepts_submissions = ?, requires_name = ?, requires_contact = ?, camera_enabled = ?, requires_nik = ?, requires_birth_date = ?, requires_face = ?, retention_days = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $update->execute([...$flags, $retention, $settingId]);
        if ($update->rowCount() !== 1) throw new DomainException('Layanan tidak ditemukan.');
        db()->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, summary) VALUES (?, 'service_settings', 'service', ?, 'Pengaturan transaksi layanan diperbarui.')")->execute([$admin['id'], $settingId]);
        db()->commit();
        redirect_to('/admin/dashboard.php?module=services&saved=1');
    } catch (DomainException $error) {
        if (db()->inTransaction()) db()->rollBack();
        http_response_code(422);
        $settingsError = $error->getMessage();
    } catch (RuntimeException $error) {
        if (db()->inTransaction()) db()->rollBack();
        error_log('Pengaturan transaksi gagal: ' . get_class($error));
        http_response_code(503);
        $settingsError = 'Pengaturan gagal disimpan. Muat ulang dan hubungi pengelola bila masalah berlanjut.';
    }
}
$configuredServices = db()->query('SELECT id, name, accepts_submissions, requires_name, requires_contact, camera_enabled, requires_nik, requires_birth_date, requires_face, retention_days FROM services ORDER BY name')->fetchAll();
