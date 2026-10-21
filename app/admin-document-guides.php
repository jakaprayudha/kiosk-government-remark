<?php
declare(strict_types=1);
require_once __DIR__ . '/document-guides.php';
$guideError = '';
$guideId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($guideId === false) {
    http_response_code(400);
    exit('ID panduan tidak valid.');
}
$guideForm = ['name' => '', 'region' => 'Kabupaten Serdang Bedagai', 'requirements_text' => '', 'fee_amount' => '',
    'fee_note' => '', 'legal_basis' => '', 'source_url' => '', 'is_demo' => 1, 'fee_verified' => 0,
    'is_published' => 0, 'sort_order' => 0, 'version' => 0];
$managedGuides = [];
$guideModalOpen = isset($_GET['new']) || ($guideId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST')
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save');
try {
    if ($guideId > 0) {
        $query = db()->prepare('SELECT * FROM document_guides WHERE id = ?');
        $query->execute([$guideId]);
        $row = $query->fetch();
        if (!$row) {
            http_response_code(404);
            exit('Panduan tidak ditemukan.');
        }
        $guideForm = $row;
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
            change_document_guide(db(), $guideId, $version, (int) $admin['id'], $active);
            redirect_to('/admin/dashboard.php?module=document-guides&' . ($action === 'delete' ? 'deleted' : 'saved') . '=1');
        }
        if ($action !== 'save') throw new DomainException('Aksi panduan tidak valid.');
        foreach (array_keys($guideForm) as $key) {
            if (is_string($_POST[$key] ?? null)) $guideForm[$key] = $_POST[$key];
        }
        foreach (['is_demo', 'fee_verified', 'is_published'] as $key) $guideForm[$key] = isset($_POST[$key]) ? 1 : 0;
        $guideForm['version'] = $version;
        save_document_guide(db(), $_POST, $guideId, $version, (int) $admin['id']);
        redirect_to('/admin/dashboard.php?module=document-guides&saved=1');
    }
} catch (DomainException $error) {
    http_response_code(422);
    $guideError = $error->getMessage();
} catch (RuntimeException $error) {
    error_log('Panduan dokumen gagal diproses: ' . get_class($error));
    http_response_code(503);
    $guideError = 'Data gagal diproses. Muat ulang dan hubungi pengelola bila masalah berlanjut.';
}
try {
    $managedGuides = db()->query('SELECT id,name,region,is_demo,is_published,fee_amount,fee_verified,sort_order,version FROM document_guides ORDER BY sort_order,name,id')->fetchAll();
} catch (RuntimeException $error) {
    error_log('Daftar panduan gagal dimuat: ' . get_class($error));
    http_response_code(503);
    $guideError = 'Daftar persyaratan dan biaya gagal dimuat.';
}
