<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/office-activity.php';
function check_activity(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON');
run_migrations($pdo); run_migrations($pdo);
$pdo->exec("INSERT INTO office_profiles(id,slug,name,regency,province,latitude,longitude) VALUES
    (1,'alpha','Alpha','Kab','Prov',0,0),(2,'beta','Beta','Kab','Prov',0,0);
    INSERT INTO users(id,name,username,password_hash,role,office_id,last_login_at) VALUES
    (1,'Pusat','pusat','hash','admin',NULL,CURRENT_TIMESTAMP),
    (2,'Admin A','alpha','hash','admin',1,'2026-10-21 06:00:00'),
    (3,'Admin B','beta','hash','admin',1,'2026-10-21 07:00:00');
    UPDATE users SET is_active=0 WHERE id=3;
    INSERT INTO audit_logs(user_id,action,entity_type,summary) VALUES(1,'login','user','Pusat'),(2,'login','user','A'),(3,'login','user','B');
    INSERT INTO audit_logs(user_id,action,entity_type,summary,created_at) VALUES(2,'login','user','Lama','1900-01-01 00:00:00')");
$token = str_repeat('a',64);
$other = str_repeat('b',64);
$insert = $pdo->prepare('INSERT INTO kiosk_devices(office_id,name,token_hash,is_active) VALUES(?,?,?,?)');
$insert->execute([1,'A',hash('sha256',$token),1]);
$insert->execute([2,'B',hash('sha256',$other),0]);
foreach ([null,[], 'invalid',str_repeat('c',64),$other] as $invalid) record_kiosk_activity($pdo,$invalid);
record_kiosk_activity($pdo,$token); record_kiosk_activity($pdo,$token);
$rows = office_activity_recap($pdo);
check_activity(count($rows) === 2, 'Semua kecamatan termasuk tanpa aktivitas harus tampil.');
check_activity((int)$rows[0]['accounts'] === 2 && (int)$rows[0]['active_accounts'] === 1 && (int)$rows[0]['logins_today'] === 2, 'Admin pusat/akun nonaktif/login lama harus dihitung sesuai definisi.');
check_activity($rows[0]['last_login_at'] === '2026-10-21 07:00:00' && (int)$rows[0]['page_views'] === 2 && $rows[0]['last_seen_at'] !== null, 'Login terbaru dan page views salah.');
check_activity((int)$rows[1]['page_views'] === 0 && $rows[1]['last_seen_at'] === null && (int)$rows[1]['active_devices'] === 0, 'Perangkat dicabut tidak boleh mencatat aktivitas.');
check_activity(office_activity_time('2026-10-20 18:00:00') === '21/10/2026 01:00:00 WIB' && office_activity_time(null) === 'Belum tercatat', 'Waktu WIB/fallback salah.');
$pdo->exec('UPDATE kiosk_devices SET is_active=0 WHERE office_id=1');
record_kiosk_activity($pdo,$token);
check_activity((int)office_activity_recap($pdo)[0]['page_views'] === 2, 'OFF harus berhenti mencatat tanpa menghapus angka lama.');
function escape(string $text): string { return htmlspecialchars($text,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8'); }
$officeActivityRows = $rows;
$officeActivityRows[0]['name'] = '<script>Alpha</script>';
$officeActivityError = '';
ob_start(); require dirname(__DIR__) . '/app/views/admin-office-activity.php'; $html = ob_get_clean();
check_activity(str_contains($html,'&lt;script&gt;Alpha&lt;/script&gt;') && !str_contains($html,'<script>Alpha'), 'Nama harus di-escape.');
check_activity(str_contains($html,'bukan status online') && str_contains($html,'Belum tercatat'), 'Definisi aktivitas/fallback harus jelas.');
$officeActivityError = 'Tidak tersedia';
ob_start(); require dirname(__DIR__) . '/app/views/admin-office-activity.php'; $html = ob_get_clean();
check_activity(str_contains($html,'role="alert"') && !str_contains($html,'<table>'), 'Error tidak boleh menampilkan rekap kosong seolah sukses.');
echo "PASS: migrasi idempoten, rekap dua kecamatan, login pusat/wilayah, akun aktif, perangkat aktif/dicabut, hitungan GET, timestamp WIB, kosong/error dan escaping.\n";
