<?php
declare(strict_types=1);
require_once __DIR__ . '/service-master.php';
$serviceError = '';
$serviceId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($serviceId === false) { http_response_code(400); exit('ID layanan tidak valid.'); }
$serviceForm = array_fill_keys(array_keys(service_master_text_fields()), '');
$serviceForm += array_fill_keys(array_keys(service_master_flags()), 0);
$serviceForm += ['category_id' => '', 'sort_order' => 0, 'retention_days' => 30, 'valid_from' => '', 'valid_until' => '', 'version' => 0];
$serviceRequirements = [];
$serviceRecords = $serviceCategories = [];
$serviceModalOpen = isset($_GET['new']) || ($serviceId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST');
try {
    if ($serviceId > 0) {
        $q = db()->prepare('SELECT * FROM services WHERE id=?');
        $q->execute([$serviceId]);
        $row = $q->fetch();
        if (!$row) { http_response_code(404); throw new DomainException('Layanan tidak ditemukan.'); }
        $serviceForm = $row;
        $serviceRequirements = public_service_requirements(db(), $serviceId);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token()) throw new DomainException('Sesi formulir berakhir. Muat ulang halaman.');
        $version = filter_var($_POST['version'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($version === false) throw new DomainException('Versi formulir tidak valid.');
        $action = $_POST['action'] ?? '';
        if ($action === 'delete' || $action === 'toggle') {
            $active = null;
            if ($action === 'toggle') {
                $value = $_POST['is_published'] ?? '0';
                if (!in_array($value, ['0', '1'], true)) throw new DomainException('Status tidak valid.');
                $active = (int) $value;
            }
            change_service_master(db(), $serviceId, $version, (int) $admin['id'], $active);
            redirect_to('/admin/dashboard.php?module=services&' . ($action === 'delete' ? 'deleted' : 'saved') . '=1');
        }
        if ($action !== 'save') throw new DomainException('Aksi layanan tidak valid.');
        $serviceModalOpen = true;
        foreach ($serviceForm as $key => $value) {
            if (is_string($_POST[$key] ?? null)) $serviceForm[$key] = $_POST[$key];
        }
        foreach (service_master_flags() as $key => $label) $serviceForm[$key] = ($_POST[$key] ?? '') === '1' ? 1 : 0;
        $serviceForm['version'] = $version;
        $serviceRequirements = [];
        if (is_array($_POST['requirements'] ?? null)) {
            foreach ($_POST['requirements'] as $requirement) {
                if (!is_array($requirement)) continue;
                $serviceRequirements[] = ['id' => is_scalar($requirement['id'] ?? null) ? (int) $requirement['id'] : 0,
                    'name' => is_string($requirement['name'] ?? null) ? $requirement['name'] : '',
                    'description' => is_string($requirement['description'] ?? null) ? $requirement['description'] : '',
                    'is_required' => ($requirement['is_required'] ?? '') === '1' ? 1 : 0];
            }
        }
        save_service_master(db(), $_POST, $serviceId, $version, (int) $admin['id']);
        redirect_to('/admin/dashboard.php?module=services&saved=1');
    }
} catch (DomainException $error) {
    if (http_response_code() !== 404) http_response_code(422);
    $serviceError = $error->getMessage();
} catch (RuntimeException $error) {
    error_log('Master layanan gagal: ' . get_class($error));
    http_response_code(503);
    $serviceError = 'Layanan belum dapat diproses. Muat ulang atau hubungi pengelola.';
}
try {
    $serviceCategories = db()->query('SELECT id,name,is_active FROM service_categories ORDER BY sort_order,name,id')->fetchAll();
    $serviceRecords = db()->query('SELECT s.*,c.name AS category_name,c.is_active AS category_active,
        (SELECT COUNT(*) FROM submissions WHERE service_id=s.id)+(SELECT COUNT(*) FROM service_pickup_sequences WHERE service_id=s.id) AS usage_count
        FROM services s JOIN service_categories c ON c.id=s.category_id ORDER BY c.sort_order,s.sort_order,s.name,s.id')->fetchAll();
} catch (RuntimeException $error) {
    error_log('Daftar master layanan gagal: ' . get_class($error));
    http_response_code(503);
    $serviceError = 'Daftar layanan/kategori gagal dimuat.';
}
