<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/office-management.php';
function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function rejected(callable $call): void {
    try { $call(); } catch (DomainException) { return; }
    throw new RuntimeException('Tindakan seharusnya ditolak.');
}
function input(string $tab, array $values = []): array {
    return array_map('strval', array_replace(office_master_defaults($tab), $values));
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON');
run_migrations($pdo); run_migrations($pdo);
$pdo->exec("INSERT INTO users(name,username,password_hash,role) VALUES('Pusat','central','unused','admin')");
$central = (int) $pdo->lastInsertId();
$profiles = [];
foreach (['alpha', 'beta'] as $slug) {
    $profiles[$slug] = mutate_office_master($pdo, $central, 'profiles', 0, 0, 0, 'save', input('profiles', [
        'slug' => $slug, 'name' => 'Kecamatan ' . $slug, 'regency' => 'Kabupaten', 'province' => 'Provinsi',
        'latitude' => '3.4', 'longitude' => '99.1', 'is_published' => '1',
    ]));
}
[$a, $b] = array_values($profiles);
$account = input('accounts', ['name' => 'Admin A', 'username' => 'admin-a', 'password' => 'A-valid-password123!']);
$adminA = mutate_office_master($pdo, $central, 'accounts', $a, 0, 0, 'save', $account + ['office_id' => (string) $b, 'role' => 'officer']);
$account['username'] = 'admin-b';
$adminB = mutate_office_master($pdo, $central, 'accounts', $b, 0, 0, 'save', $account);
$stored = $pdo->query("SELECT * FROM users WHERE id=$adminA")->fetch();
check((int) $stored['office_id'] === $a && $stored['role'] === 'admin', 'Akun harus terkunci pada wilayah yang dipilih.');
check(password_verify('A-valid-password123!', $stored['password_hash']), 'Password harus di-hash.');
$account['username'] = 'ADMIN-A';
rejected(fn() => mutate_office_master($pdo, $central, 'accounts', $a, 0, 0, 'save', $account));
$account['username'] = 'valid'; $account['password'] = 'short';
rejected(fn() => mutate_office_master($pdo, $central, 'accounts', $a, 0, 0, 'save', $account));
$account['password'] = "Invalid\0password123";
rejected(fn() => mutate_office_master($pdo, $central, 'accounts', $a, 0, 0, 'save', $account));
$v = input('villages', ['slug' => 'desa-sama', 'name' => 'Desa A', 'latitude' => '3.4', 'longitude' => '99.1', 'is_published' => '1']);
$va = mutate_office_master($pdo, $adminA, 'villages', $a, 0, 0, 'save', $v);
$vb = mutate_office_master($pdo, $adminB, 'villages', $b, 0, 0, 'save', $v + ['office_id' => (string) $a]);
$f = input('facilities', ['slug' => 'sarana-sama', 'name' => 'Sarana A', 'latitude' => '3.4', 'longitude' => '99.1', 'is_published' => '1']);
$fa = mutate_office_master($pdo, $adminA, 'facilities', $a, 0, 0, 'save', $f);
$fb = mutate_office_master($pdo, $adminB, 'facilities', $b, 0, 0, 'save', $f);
foreach (['villages' => $vb, 'facilities' => $fb, 'profiles' => $b, 'accounts' => $adminB] as $tab => $foreignId) {
    foreach (['save', 'toggle', 'delete'] as $action) {
        rejected(fn() => mutate_office_master($pdo, $adminA, $tab, $b, $foreignId, 1, $action, []));
        rejected(fn() => mutate_office_master($pdo, $adminA, $tab, $a, $foreignId, 1, $action, []));
    }
    rejected(fn() => mutate_office_master($pdo, $adminA, $tab, $b, 0, 0, 'save', []));
}
rejected(fn() => mutate_office_master($pdo, $adminA, 'profiles', $a, 0, 0, 'save', []));
rejected(fn() => mutate_office_master($pdo, $adminA, 'profiles', $a, $a, 1, 'delete', []));
rejected(fn() => mutate_office_master($pdo, $adminA, 'accounts', $a, $adminA, 1, 'toggle', []));
rejected(fn() => register_kiosk_device($pdo, $adminA, $a, 'KIOSK'));
rejected(fn() => mutate_office_master($pdo, $central, 'profiles', $a, $a, 1, 'delete', []));
$initialAudit = (int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
foreach ([
    ['latitude' => '91'], ['longitude' => '-181'], ['population' => '-1'],
    ['population_year' => '2201'], ['sort_order' => '10000'], ['is_published' => '2'],
    ['name' => ['invalid']], ['slug' => 'Upper'], ['name' => str_repeat('a', 181)],
] as $invalid) rejected(fn() => mutate_office_master($pdo, $adminA, 'villages', $a, 0, 0, 'save', array_replace($v, $invalid)));
rejected(fn() => mutate_office_master($pdo, $adminA, 'facilities', $a, 0, 0, 'save', array_replace($f, ['category' => 'bad'])));
check((int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn() === $initialAudit, 'Penolakan harus rollback audit.');
mutate_office_master($pdo, $adminA, 'villages', $a, $va, 1, 'save', array_replace($v, ['name' => 'Desa diperbarui', 'office_id' => (string) $b]));
check((int) $pdo->query("SELECT office_id FROM office_villages WHERE id=$va")->fetchColumn() === $a, 'Desa tidak boleh dipindah via input.');
rejected(fn() => mutate_office_master($pdo, $adminA, 'villages', $a, $va, 1, 'delete', []));
$token = register_kiosk_device($pdo, $central, $a, 'KIOSK A');
check(strlen($token) === 64 && kiosk_office_slug($pdo, $token) === 'alpha', 'Perangkat menentukan profil A.');
check($pdo->query('SELECT token_hash FROM kiosk_devices')->fetchColumn() !== $token, 'Token mentah tidak boleh disimpan.');
check(kiosk_office_slug($pdo, null) === null, 'Tidak ada fallback perangkat.');
rejected(fn() => kiosk_office_slug($pdo, 'invalid'));
rejected(fn() => kiosk_office_slug($pdo, str_repeat('a', 64)));
$newToken = register_kiosk_device($pdo, $central, $b, 'KIOSK B', $token);
rejected(fn() => kiosk_office_slug($pdo, $token));
check(kiosk_office_slug($pdo, $newToken) === 'beta', 'Penetapan ulang harus pindah ke B.');
$device = (int) $pdo->query("SELECT id FROM kiosk_devices WHERE is_active=1")->fetchColumn();
rejected(fn() => mutate_office_master($pdo, $adminA, 'devices', $a, $device, 1, 'toggle', []));
mutate_office_master($pdo, $central, 'devices', $b, $device, 1, 'toggle', []);
rejected(fn() => kiosk_office_slug($pdo, $newToken));
mutate_office_master($pdo, $central, 'devices', $b, $device, 2, 'toggle', ['is_active' => '1']);
$public = public_office($pdo, 'alpha');
check(count($public['villages']) === 1 && $public['villages'][0]['name'] === 'Desa diperbarui' && count($public['facilities']) === 1, 'Publik harus hanya menampilkan data wilayah.');
mutate_office_master($pdo, $adminA, 'villages', $a, $va, 2, 'toggle', []);
mutate_office_master($pdo, $adminA, 'facilities', $a, $fa, 1, 'toggle', []);
check(public_office($pdo, 'alpha')['villages'] === [] && public_office($pdo, 'alpha')['facilities'] === [], 'OFF menyembunyikan anak.');
mutate_office_master($pdo, $adminA, 'profiles', $a, $a, 1, 'toggle', []);
check(public_office($pdo, 'alpha') === null, 'Profil OFF menyembunyikan seluruh wilayah.');
$account = input('accounts', ['name' => 'Admin A', 'username' => 'admin-a', 'password' => '']);
mutate_office_master($pdo, $central, 'accounts', $a, $adminA, 1, 'save', $account);
check($pdo->query("SELECT password_hash FROM users WHERE id=$adminA")->fetchColumn() === $stored['password_hash'], 'Password kosong harus mempertahankan hash.');
mutate_office_master($pdo, $central, 'accounts', $a, $adminA, 2, 'toggle', []);
rejected(fn() => office_actor($pdo, $adminA));
mutate_office_master($pdo, $central, 'villages', $a, $va, 3, 'delete', []);
mutate_office_master($pdo, $central, 'facilities', $a, $fa, 2, 'delete', []);
function escape(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string { return 'test'; }
$officeAdminError = '';
$officeAccountsPage = true;
$officeModule = 'users';
$officeTab = 'accounts';
$officeScope = null;
$managedOfficeId = $a;
$officeRecordId = 0;
$officeDefinitions = office_master_definitions();
$officeOptions = [['id' => $a, 'name' => 'Kecamatan alpha']];
$officeRecords = [['id' => $adminA, 'name' => '<script>Admin</script>', 'username' => 'admin-a', 'version' => 3, 'is_active' => 0]];
$officeForm = office_master_defaults('accounts');
$officeModalOpen = false;
$officeBaseUrl = 'dashboard.php?module=users&tab=accounts&office_id=' . $a;
ob_start();
require dirname(__DIR__) . '/app/views/admin-office.php';
$html = ob_get_clean();
foreach (['Akun admin per kecamatan', 'name="module" value="users"', 'data-auto-submit', 'Username / kecamatan', 'Kecamatan alpha', 'module=users&amp;tab=accounts', 'id="office-dialog"', 'role="switch"', '&lt;script&gt;Admin&lt;/script&gt;'] as $text) {
    check(str_contains($html, $text), 'Menu akun tidak lengkap: ' . $text);
}
check(!str_contains($html, '<script>Admin</script>') && !str_contains($html, 'name="role"') && !str_contains($html, 'name="password_hash"'), 'Menu akun tidak boleh membocorkan hash atau menyediakan promosi role.');
echo "PASS: CRUD, isolasi dua kecamatan, akun/password, versi, rollback, validasi, perangkat/rebind/revokasi, publikasi induk/anak, menu users dan escaping.\n";
