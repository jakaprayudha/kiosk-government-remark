<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/service-master.php';
require_once dirname(__DIR__) . '/app/submissions.php';
function master_check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function master_reject(callable $operation): void
{
    try { $operation(); } catch (DomainException $error) { return; }
    throw new RuntimeException('Input/operasi tidak valid diterima.');
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON');
run_migrations($pdo); run_migrations($pdo);
$pdo->exec("INSERT INTO users(id,name,username,password_hash) VALUES(1,'Test','test','test')");
$pdo->exec("INSERT INTO service_categories(id,name,slug) VALUES(1,'Kategori','kategori')");
$input = array_fill_keys(array_keys(service_master_text_fields()), '');
$input += ['name' => '', 'category_id' => 1, 'sort_order' => 0, 'retention_days' => 30];
$input['name'] = 'Layanan contoh';
$input['slug'] = 'layanan-contoh';
$input['is_published'] = '1';
$input['accepts_submissions'] = '1';
$input['requirements'] = [['id' => 0, 'name' => 'Dokumen <contoh>', 'description' => 'Penjelasan', 'is_required' => '1']];
$id = save_service_master($pdo, $input, 0, 0, 1);
master_check(count(public_service_catalog($pdo, '2026-10-21')) === 1, 'Create tidak sinkron.');
$requirements = public_service_requirements($pdo, $id);
$rid = (int) $requirements[0]['id'];
master_reject(fn() => save_service_master($pdo, $input, 0, 0, 1));
foreach (['name' => '', 'slug' => 'Kode Salah', 'category_id' => 999, 'sort_order' => 10000, 'retention_days' => 0,
    'valid_from' => '2026-02-30', 'official_url' => 'javascript:alert(1)', 'requires_face' => '2', 'is_demo' => [], 'requirements' => 'bad'] as $key => $bad) {
    master_reject(fn() => save_service_master($pdo, [...$input, $key => $bad], $id, 1, 1));
}
master_reject(fn() => save_service_master($pdo, [...$input, 'valid_from' => '2026-10-22', 'valid_until' => '2026-10-21'], $id, 1, 1));
master_reject(fn() => save_service_master($pdo, [...$input, 'requirements' => array_fill(0, 51, $input['requirements'][0])], $id, 1, 1));
foreach (service_master_text_fields() as $key => $limit) {
    master_reject(fn() => save_service_master($pdo, [...$input, $key => str_repeat('a', $limit + 1)], $id, 1, 1));
}
$boundary = [...$input, 'slug' => str_repeat('b', 120), 'name' => str_repeat('n', 180), 'sort_order' => 9999, 'retention_days' => 1,
    'valid_from' => '2024-02-29', 'valid_until' => '2024-02-29',
    'requirements' => array_fill(0, 50, ['id' => 0, 'name' => str_repeat('n', 300), 'description' => str_repeat('d', 1500), 'is_required' => '1'])];
foreach (service_master_text_fields() as $key => $limit) {
    if (!in_array($key, ['name', 'slug', 'official_url'], true)) $boundary[$key] = str_repeat('a', $limit);
}
$boundaryId = save_service_master($pdo, $boundary, 0, 0, 1);
master_check(count(public_service_requirements($pdo, $boundaryId)) === 50, 'Batas 50 persyaratan tidak diterima.');
master_check(public_service_catalog($pdo, '2024-02-29', '', $boundaryId) !== [], 'Batas tanggal inklusif tidak diterima.');
change_service_master($pdo, $boundaryId, 1, 1, null);
$update = [...$input, 'name' => '<script>Baru</script>', 'slug' => 'layanan-baru', 'requires_nik' => '1', 'requires_face' => '1',
    'requires_name' => '1', 'requires_contact' => '1', 'requires_birth_date' => '1', 'camera_enabled' => '1', 'retention_days' => 365,
    'requirements' => [['id' => $rid, 'name' => 'Dokumen diperbarui', 'description' => 'baru', 'is_required' => '0'],
        ['id' => 0, 'name' => 'Kedua', 'description' => '', 'is_required' => '1']]];
save_service_master($pdo, $update, $id, 1, 1);
$catalog = public_service_catalog($pdo, '2026-10-21')[0];
master_check($catalog['name'] === '<script>Baru</script>' && (int) $catalog['retention_days'] === 365, 'Update/sinkron gagal.');
foreach (['requires_nik', 'requires_face', 'requires_name', 'requires_contact', 'requires_birth_date', 'camera_enabled'] as $flag) master_check((int) $catalog[$flag] === 1, 'Flag tidak fleksibel.');
master_check((int) public_service_requirements($pdo, $id)[0]['id'] === $rid, 'ID persyaratan berubah saat edit.');
master_reject(fn() => save_service_master($pdo, $update, $id, 1, 1));
master_reject(fn() => change_service_master($pdo, $id, 1, 1, 0));
change_service_master($pdo, $id, 2, 1, 0);
master_check(public_service_catalog($pdo, '2026-10-21') === [], 'OFF masih terlihat.');
change_service_master($pdo, $id, 3, 1, 1);
$pdo->exec('UPDATE service_categories SET is_active=0 WHERE id=1');
master_check(public_service_catalog($pdo, '2026-10-21') === [], 'Kategori nonaktif masih terlihat.');
$pdo->exec('UPDATE service_categories SET is_active=1 WHERE id=1');
$other = save_service_master($pdo, [...$input, 'slug' => 'lain'], 0, 0, 1);
$otherRid = (int) public_service_requirements($pdo, $other)[0]['id'];
master_reject(fn() => save_service_master($pdo, [...$update, 'requirements' => [['id' => $otherRid, 'name' => 'Asing', 'description' => '']]], $id, 4, 1));
master_check((int) $pdo->query("SELECT version FROM services WHERE id=$id")->fetchColumn() === 4, 'Invalid persyaratan tidak rollback.');
try {
    save_service_master($pdo, [...$input, 'slug' => 'gagal-audit'], 0, 0, 999);
    throw new RuntimeException('Audit invalid diterima.');
} catch (PDOException $error) {
    master_check((int) $pdo->query("SELECT COUNT(*) FROM services WHERE slug='gagal-audit'")->fetchColumn() === 0, 'Simpan tidak rollback.');
}
try {
    change_service_master($pdo, $other, 1, 999, null);
    throw new RuntimeException('Audit invalid diterima.');
} catch (PDOException $error) {
    master_check(count(public_service_requirements($pdo, $other)) === 1, 'Delete tidak rollback.');
}
master_reject(fn() => change_service_master($pdo, $other, 0, 1, null));
change_service_master($pdo, $other, 1, 1, null);
master_check(public_service_requirements($pdo, $other) === [], 'Persyaratan tidak cascade.');
$savedInput = [...$input, 'slug' => 'transaksi'];
$used = save_service_master($pdo, $savedInput, 0, 0, 1);
$service = public_service_catalog($pdo, '2026-10-21', '', $used)[0];
create_submission($pdo, $service, ['name' => '', 'contact' => '', 'description' => 'Contoh', 'consent' => '1', 'confirmed' => '1'], 'master-test-request', '123456', []);
$submission = $pdo->query('SELECT * FROM submissions')->fetch();
$usedRequirement = (int) public_service_requirements($pdo, $used)[0]['id'];
$pdo->prepare("INSERT INTO submission_attachments(submission_id,requirement_id,document_name,stored_name,mime_type,size_bytes) VALUES(?,?,?,'master-test.jpg','image/jpeg',10)")->execute([$submission['id'], $usedRequirement, 'Snapshot dokumen']);
master_reject(fn() => change_service_master($pdo, $used, 1, 1, null));
change_service_master($pdo, $used, 1, 1, 0);
master_check((int) $pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn() === 1, 'OFF menghapus tiket.');
save_service_master($pdo, [...$savedInput, 'name' => 'Nama setelah transaksi', 'retention_days' => 1, 'requirements' => [], 'is_demo' => '1'], $used, 2, 1);
$afterSubmission = $pdo->query('SELECT * FROM submissions')->fetch();
master_check($afterSubmission['service_name'] === $submission['service_name'] && $afterSubmission['is_demo'] === $submission['is_demo']
    && $afterSubmission['expires_at'] === $submission['expires_at'], 'Edit master mengubah snapshot/retensi transaksi lama.');
$attachment = $pdo->query('SELECT * FROM submission_attachments')->fetch();
master_check($attachment['requirement_id'] === null && $attachment['document_name'] === 'Snapshot dokumen', 'Hapus persyaratan merusak lampiran lama.');
$pdo->exec("INSERT INTO service_pickup_sequences(service_id,last_number) VALUES($id,1)");
master_reject(fn() => change_service_master($pdo, $id, 4, 1, null));
save_service_master($pdo, [...$update, 'requirements' => [], 'valid_from' => '2026-10-22'], $id, 4, 1);
master_check(public_service_requirements($pdo, $id) === [] && public_service_catalog($pdo, '2026-10-21', '', $id) === [], 'Hapus persyaratan/rentang tanggal gagal.');
function escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string { return 'test'; }
$serviceError = ''; $serviceId = $id; $serviceModalOpen = true;
$serviceForm = $pdo->query("SELECT * FROM services WHERE id=$id")->fetch();
$serviceRequirements = [];
$serviceCategories = $pdo->query('SELECT * FROM service_categories')->fetchAll();
$serviceRecords = $pdo->query('SELECT s.*,c.name AS category_name,c.is_active AS category_active,1 AS usage_count FROM services s JOIN service_categories c ON c.id=s.category_id')->fetchAll();
ob_start(); require dirname(__DIR__) . '/app/views/admin-service-settings.php'; $html = ob_get_clean();
master_check(str_contains($html, '&lt;script&gt;Baru&lt;/script&gt;') && !str_contains($html, '<script>Baru</script>'), 'Escaping gagal.');
master_check(str_contains($html, 'role="switch"') && str_contains($html, 'form="service-form"') && str_contains($html, 'id="service-table"'), 'Tabel/toggle/footer hilang.');
echo "PASS: master layanan CRUD, validasi, persyaratan stabil, flags transaksi, status/tanggal/kategori publik, versi, audit/rollback, perlindungan transaksi/sequence, modal dan escaping.\n";
