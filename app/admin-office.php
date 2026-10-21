<?php
declare(strict_types=1);
require_once __DIR__ . '/office-management.php';
$officeAdminError = '';
$officeRecords = $officeOptions = [];
$officeTab = $_GET['tab'] ?? 'profiles';
$officeDefinitions = office_master_definitions();
if (!is_string($officeTab) || !isset($officeDefinitions[$officeTab])) { http_response_code(400); exit('Tab profil tidak valid.'); }
$officeScope = $admin['office_id'];
if ($officeScope !== null && in_array($officeTab, ['accounts', 'devices'], true)) { http_response_code(403); exit('Akses hanya untuk admin pusat.'); }
$managedOfficeId = filter_var($_GET['office_id'] ?? ($officeScope ?? 0), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
$officeRecordId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($managedOfficeId === false || $officeRecordId === false) { http_response_code(400); exit('ID tidak valid.'); }
if ($officeScope !== null && $managedOfficeId !== (int) $officeScope) { http_response_code(403); exit('Akses kecamatan lain ditolak.'); }
$officeForm = office_master_defaults($officeTab);
$officeModalOpen = isset($_GET['new']) || ($officeRecordId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST')
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save');
try {
    $actor = office_actor(db(), (int) $admin['id']);
    if ($officeScope === null) $officeOptions = db()->query('SELECT id,name FROM office_profiles ORDER BY name,id')->fetchAll();
    else {
        $q = db()->prepare('SELECT id,name FROM office_profiles WHERE id=?');
        $q->execute([$officeScope]); $officeOptions = $q->fetchAll();
    }
    if ($officeRecordId > 0 && $officeTab === 'profiles') {
        if ($officeScope !== null && $officeRecordId !== (int) $officeScope) { http_response_code(403); exit('Profil kecamatan lain ditolak.'); }
        $managedOfficeId = $officeRecordId;
    }
    if ($managedOfficeId > 0 && !in_array($managedOfficeId, array_map('intval', array_column($officeOptions, 'id')), true)) throw new DomainException('Kecamatan tidak ditemukan.');
    office_assert_scope($actor, $managedOfficeId);
    $table = $officeDefinitions[$officeTab]['table'];
    if ($officeRecordId > 0) {
        $q = db()->prepare("SELECT * FROM $table WHERE id=?" . ($officeTab === 'profiles' ? '' : ' AND office_id=?') . ($officeTab === 'accounts' ? " AND role='admin'" : ''));
        $q->execute($officeTab === 'profiles' ? [$officeRecordId] : [$officeRecordId, $managedOfficeId]);
        $record = $q->fetch();
        if (!$record) throw new DomainException('Data tidak ditemukan pada kecamatan ini.');
        $officeForm = $record;
        if ($officeTab === 'accounts') $officeForm['password'] = '';
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token()) throw new DomainException('Sesi formulir berakhir. Muat ulang.');
        $action = $_POST['action'] ?? '';
        if (!is_string($action)) throw new DomainException('Aksi tidak valid.');
        if ($action === 'bind') {
            if ($officeTab !== 'devices' || !is_string($_POST['name'] ?? null)) throw new DomainException('Penetapan perangkat tidak valid.');
            $token = register_kiosk_device(db(), (int) $admin['id'], $managedOfficeId, $_POST['name'], $_COOKIE['kiosk_device'] ?? null);
            if (!setcookie('kiosk_device', $token, ['expires' => time() + 365 * 86400, 'path' => '/', 'secure' => $isHttps, 'httponly' => true, 'samesite' => 'Lax'])) throw new RuntimeException('Cookie perangkat tidak dapat ditetapkan.');
        } else {
            $version = filter_var($_POST['version'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($version === false) throw new DomainException('Versi tidak valid.');
            if ($action === 'save') {
                foreach ($officeDefinitions[$officeTab]['fields'] as $key => $field) {
                    if ($field[1] === 'password') continue;
                    if ($field[1] === 'flag') $officeForm[$key] = ($_POST[$key] ?? '') === '1' ? 1 : 0;
                    elseif (is_string($_POST[$key] ?? null)) $officeForm[$key] = $_POST[$key];
                }
                $officeForm['version'] = $version;
            }
            $savedId = mutate_office_master(db(), (int) $admin['id'], $officeTab, $managedOfficeId, $officeRecordId, $version, $action, $_POST);
            if ($officeTab === 'profiles') $managedOfficeId = $action === 'delete' ? 0 : $savedId;
        }
        redirect_to('/admin/dashboard.php?module=office&tab=' . $officeTab . '&office_id=' . $managedOfficeId . '&saved=1');
    }
    if ($officeTab === 'profiles') {
        $q = db()->prepare('SELECT * FROM office_profiles' . ($officeScope === null ? '' : ' WHERE id=?') . ' ORDER BY name,id');
        $q->execute($officeScope === null ? [] : [$officeScope]);
    } else {
        $columns = $officeTab === 'accounts' ? 'id,name,username,is_active,version' : ($officeTab === 'devices' ? 'id,name,is_active,version' : '*');
        $q = db()->prepare("SELECT $columns FROM $table WHERE office_id=?" . ($officeTab === 'accounts' ? " AND role='admin'" : '') . ' ORDER BY name,id');
        $q->execute([$managedOfficeId]);
    }
    $officeRecords = $q->fetchAll();
} catch (DomainException $error) {
    http_response_code(422); $officeAdminError = $error->getMessage();
} catch (RuntimeException $error) {
    error_log('Profil kecamatan gagal diproses: ' . $error->getMessage());
    http_response_code(503); $officeAdminError = 'Data kecamatan gagal diproses. Muat ulang dan hubungi pengelola.';
}
$officeBaseUrl = 'dashboard.php?module=office&tab=' . $officeTab . '&office_id=' . $managedOfficeId;
