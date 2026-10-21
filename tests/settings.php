<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/settings.php';
function settings_check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON');
run_migrations($pdo);
run_migrations($pdo);
settings_check(kiosk_settings($pdo)['kiosk_title'] === 'KIOSK', 'Default atau migrasi salah.');
$pdo->exec("INSERT INTO office_profiles(id,slug,name,regency,province,latitude,longitude) VALUES(1,'uji','Kecamatan Uji','Kabupaten Uji','Provinsi Uji',0,0);
INSERT INTO users(id,name,username,password_hash,office_id,is_active) VALUES
(1,'Pusat','pusat','hash',NULL,1),(2,'Wilayah','wilayah','hash',1,1),(3,'Nonaktif','nonaktif','hash',NULL,0)");
$input = ['region_name'=>'Kabupaten <Uji>', 'kiosk_title'=>'KIOSK <Uji>', 'tagline'=>'Sentuh menu', 'footer_text'=>'Jaga data'];
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aFooAAAAASUVORK5CYII=');
$logo = settings_logo($png);
save_kiosk_settings($pdo, 1, $input, 0, $logo);
$row = kiosk_settings($pdo);
settings_check((int) $row['version'] === 1 && $row['logo_data'] === $logo && $row['region_name'] === $input['region_name'], 'Simpan identitas/logo gagal.');
settings_check((int) $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action='kiosk_settings'")->fetchColumn() === 1, 'Simpan harus dicatat audit.');
foreach ([2,3,999] as $actor) {
    try { save_kiosk_settings($pdo,$actor,$input,1,null); throw new RuntimeException('Aktor terlarang diterima.'); } catch (DomainException) {}
}
try { save_kiosk_settings($pdo,1,$input,0,null); throw new RuntimeException('Versi lama diterima.'); } catch (DomainException) {}
foreach ([['kiosk_title'=>''], ['tagline'=>[]], ['footer_text'=>"bad\0data"], ['region_name'=>str_repeat('x',121)]] as $invalid) {
    try { save_kiosk_settings($pdo,1,array_replace($input,$invalid),1,null); throw new RuntimeException('Input salah diterima.'); } catch (DomainException) {}
}
foreach (['', '<svg xmlns="http://www.w3.org/2000/svg"></svg>', str_repeat('x',524289), 'not an image', substr_replace($png, pack('N', 2049), 16, 4)] as $invalid) {
    try { settings_logo($invalid); throw new RuntimeException('Logo salah diterima.'); } catch (DomainException) {}
}
try { save_kiosk_settings($pdo,1,$input,1,'javascript:alert(1)'); throw new RuntimeException('Data URL salah diterima.'); } catch (DomainException) {}
settings_check((int) kiosk_settings($pdo)['version'] === 1 && (int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn() === 1, 'Penolakan tidak boleh mengubah data/audit.');
$pdo->exec("CREATE TRIGGER fail_settings_audit BEFORE INSERT ON audit_logs BEGIN SELECT RAISE(ABORT,'Uji kegagalan audit'); END");
try { save_kiosk_settings($pdo,1,$input,1,''); throw new RuntimeException('Kegagalan audit tidak terdeteksi.'); } catch (PDOException) {}
settings_check((int) kiosk_settings($pdo)['version'] === 1 && kiosk_settings($pdo)['logo_data'] === $logo, 'Kegagalan audit harus rollback perubahan pengaturan.');
$pdo->exec('DROP TRIGGER fail_settings_audit');
save_kiosk_settings($pdo,1,$input,1,null);
settings_check(kiosk_settings($pdo)['logo_data'] === $logo, 'Upload kosong harus mempertahankan logo.');
save_kiosk_settings($pdo,1,$input,2,'');
settings_check(kiosk_settings($pdo)['logo_data'] === '', 'Hapus logo gagal.');
function escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string { return 'test'; }
$settingsForm = kiosk_settings($pdo); $settingsError = '';
ob_start(); require dirname(__DIR__) . '/app/views/admin-settings.php'; $html = ob_get_clean();
settings_check(str_contains($html,'KIOSK &lt;Uji&gt;') && !str_contains($html,'KIOSK <Uji>'), 'Form harus escape identitas.');
$settingsError = 'Pengaturan gagal.'; $settingsForm = null;
ob_start(); require dirname(__DIR__) . '/app/views/admin-settings.php'; $html = ob_get_clean();
settings_check(str_contains($html,'role="alert"') && !str_contains($html,'<form'), 'Kegagalan baca harus jelas.');
echo "PASS: migrasi idempoten, identitas/logo, validasi, admin pusat, versi, rollback/audit, pertahankan/hapus logo, escaping dan error.\n";
