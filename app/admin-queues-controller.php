<?php
declare(strict_types=1);
require_once __DIR__ . '/queues.php';
$queueAdminError = '';
$queueEdit = null;
$queueRows = [];
$queueAdminTotal = 0;
$queueAdminPage = max(1, filter_var($_GET['p'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$queueView = $_GET['view'] ?? 'slots';
if (!is_string($queueView) || !in_array($queueView, ['agencies','services','slots','bookings'], true)) {
    http_response_code(400);
    exit('Halaman antrean tidak valid.');
}
$queueDate = is_string($_GET['date'] ?? null) ? $_GET['date'] : queue_now()->format('Y-m-d');
$parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $queueDate);
if (!$parsed || $parsed->format('Y-m-d') !== $queueDate) { http_response_code(400); exit('Tanggal tidak valid.'); }
try {
    $queueSlotFilter = queue_int($_GET['slot'] ?? 0, 0);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token()) throw new DomainException('Sesi formulir berakhir. Muat ulang.');
        match ($queueView) {
            'agencies' => save_queue_agency(db(), $_POST, (int) $admin['id']),
            'services' => save_queue_service(db(), $_POST, (int) $admin['id']),
            'slots' => save_queue_slot(db(), $_POST, (int) $admin['id']),
            'bookings' => change_queue_booking(db(), queue_int($_POST['id'] ?? ''), queue_int($_POST['version'] ?? ''), queue_text($_POST, 'status', 20), (int) $admin['id']),
        };
        redirect_to('/admin/dashboard.php?module=queues&view=' . $queueView . '&date=' . $queueDate . '&slot=' . $queueSlotFilter . '&saved=1');
    }
} catch (DomainException | InvalidArgumentException $error) {
    http_response_code(422);
    $queueAdminError = $error->getMessage();
} catch (RuntimeException $error) {
    error_log('Pengaturan antrean gagal: ' . get_class($error));
    http_response_code(503);
    $queueAdminError = 'Perubahan gagal disimpan. Periksa duplikasi nama/jadwal, instansi/layanan yang dipilih, lalu muat ulang.';
}
try {
    $queueAgencies = db()->query('SELECT * FROM queue_agencies ORDER BY id')->fetchAll();
    $queueServices = db()->query('SELECT s.*,a.name AS agency_name FROM queue_services s JOIN queue_agencies a ON a.id=s.agency_id ORDER BY s.id')->fetchAll();
    $editId = queue_int($_GET['edit'] ?? 0, 0);
    if ($queueView === 'agencies' || $queueView === 'services') {
        $queueRows = $queueView === 'agencies' ? $queueAgencies : $queueServices;
        foreach ($queueRows as $row) if ((int) $row['id'] === $editId) $queueEdit = $row;
    } elseif ($queueView === 'slots') {
        $q = db()->prepare('SELECT t.*,s.name AS service_name,a.name AS agency_name FROM queue_slots t JOIN queue_services s ON s.id=t.service_id JOIN queue_agencies a ON a.id=s.agency_id WHERE t.service_date=? ORDER BY a.id,s.id,t.start_time');
        $q->execute([$queueDate]);
        $queueRows = $q->fetchAll();
        foreach ($queueRows as $row) if ((int) $row['id'] === $editId) $queueEdit = $row;
    } else {
        $where = ' WHERE b.expires_at>CURRENT_TIMESTAMP AND t.service_date=?' . ($queueSlotFilter > 0 ? ' AND t.id=?' : '');
        $params = $queueSlotFilter > 0 ? [$queueDate, $queueSlotFilter] : [$queueDate];
        $q = db()->prepare('SELECT COUNT(*) FROM queue_bookings b JOIN queue_slots t ON t.id=b.slot_id' . $where);
        $q->execute($params);
        $queueAdminTotal = (int) $q->fetchColumn();
        $queueAdminPage = min($queueAdminPage, max(1, (int) ceil($queueAdminTotal / 20)));
        $q = db()->prepare('SELECT b.*,t.service_date,t.start_time,t.end_time,s.name AS service_name,a.name AS agency_name
            FROM queue_bookings b JOIN queue_slots t ON t.id=b.slot_id JOIN queue_services s ON s.id=t.service_id JOIN queue_agencies a ON a.id=s.agency_id'
            . $where . ' ORDER BY t.start_time,t.id,b.number LIMIT 20 OFFSET ' . (($queueAdminPage - 1) * 20));
        $q->execute($params);
        $queueRows = $q->fetchAll();
    }
    if ($editId > 0 && !$queueEdit) throw new DomainException('Data yang akan diubah tidak ditemukan. Pilih tanggal yang sesuai.');
} catch (DomainException $error) {
    http_response_code(422);
    $queueAdminError = $error->getMessage();
} catch (RuntimeException $error) {
    error_log('Antrean admin gagal dimuat: ' . get_class($error));
    http_response_code(503);
    $queueAdminError = 'Data antrean tidak dapat dimuat. Hubungi pengelola database.';
}
