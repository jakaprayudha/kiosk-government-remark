<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/reports.php';
require_once dirname(__DIR__) . '/app/submissions.php';
require_once dirname(__DIR__) . '/app/complaints.php';
require_once dirname(__DIR__) . '/app/queues.php';
function check_report(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON');
run_migrations($pdo);
foreach ([['bad','2026-10-21'], ['2026-02-30','2026-10-21'], ['2026-10-22','2026-10-21'], [[], '2026-10-21'], ['0000-01-01','2026-10-21']] as [$start,$end]) {
    try { report_period($start,$end); throw new RuntimeException('Periode salah diterima.'); } catch (DomainException) {}
}
check_report(report_period('2026-10-21','2026-10-21') === ['2026-10-20 17:00:00','2026-10-21 17:00:00'], 'Batas WIB harus dikonversi ke UTC dengan akhir eksklusif.');
check_report(report_period('2024-02-29','2024-02-29')[0] === '2024-02-28 17:00:00', 'Tanggal kabisat valid harus diterima.');
check_report(operational_report($pdo,'2026-10-21','2026-10-21')['services'] === [], 'Data kosong tidak boleh mengarang hasil.');
$pdo->exec("INSERT INTO service_categories(id,name,slug) VALUES(1,'Contoh','contoh');
INSERT INTO services(id,category_id,name,slug) VALUES(1,1,'Master baru','contoh');
INSERT INTO queue_agencies(id,name,kind) VALUES(1,'Contoh','government');
INSERT INTO queue_services(id,agency_id,name) VALUES(1,1,'Contoh');
INSERT INTO queue_slots(id,service_id,service_date,start_time,end_time,capacity) VALUES(1,1,'2026-11-01','09:00','10:00',10)");
$sub = $pdo->prepare('INSERT INTO submissions(public_code,pin_hash,request_key,service_id,service_name,is_demo,description,status,created_at,expires_at,name,internal_note) VALUES(?,?,?,1,?,1,?,?,?,?,?,?)');
$complaint = $pdo->prepare("INSERT INTO complaints(public_code,pin_hash,request_key,kind,category,title,description,status,created_at,expires_at,name) VALUES(?,?,?,'complaint','service','Privat','Privat',?,?,?,?)");
$booking = $pdo->prepare('INSERT INTO queue_bookings(slot_id,request_key,public_code,pin_hash,number,status,created_at,expires_at) VALUES(1,?,?,?,?,?,?,?)');
$times = ['2026-10-20 16:59:59','2026-10-20 17:00:00','2026-10-21 16:59:59','2026-10-21 17:00:00','2026-10-21 06:00:00'];
foreach ($times as $index => $time) {
    $expires = $index === 4 ? '1900-01-01 00:00:00' : '9999-01-01 00:00:00';
    $status = $index === 2 ? 'completed' : 'new';
    $sub->execute(["code$index",'hash',"key$index",'<script>Snapshot</script>','Privat',$status,$time,$expires,'NAMA RAHASIA','CATATAN RAHASIA']);
    $complaint->execute(["code$index",'hash',"key$index",$status,$time,$expires,'NAMA RAHASIA']);
    $booking->execute(["key$index","code$index",'hash',$index+1,$index === 2 ? 'cancelled' : 'waiting',$time,$expires]);
}
$report = operational_report($pdo,'2026-10-21','2026-10-21');
check_report($report['submissions'] === ['completed'=>1,'new'=>1] && $report['complaints'] === ['completed'=>1,'new'=>1], 'Batas detik WIB/retensi/status salah.');
check_report($report['queue_bookings'] === ['cancelled'=>1,'waiting'=>1], 'Antrean harus dihitung menurut waktu dibuat, bukan tanggal jadwal.');
check_report(count($report['services']) === 1 && (int) $report['services'][0]['total'] === 2 && (int) $report['services'][0]['demo'] === 2 && (int) $report['services'][0]['completed'] === 1, 'Rincian layanan dan DEMO harus sama dengan total.');
check_report(!str_contains(json_encode($report), 'RAHASIA'), 'Agregasi tidak boleh mengambil identitas atau catatan.');
function escape(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$reportError = '';
$reportStart = $reportEnd = '2026-10-21';
$reportGroups = [
    'submissions'=>['label'=>'Pengajuan layanan','statuses'=>submission_statuses()],
    'complaints'=>['label'=>'Pengaduan & aspirasi','statuses'=>complaint_statuses()],
    'queue_bookings'=>['label'=>'Antrean DEMO','statuses'=>queue_statuses()],
];
ob_start(); require dirname(__DIR__) . '/app/views/admin-reports.php'; $html = ob_get_clean();
check_report(str_contains($html,'&lt;script&gt;Snapshot&lt;/script&gt;') && !str_contains($html,'<script>Snapshot'), 'Snapshot harus di-escape.');
foreach (['status terkini','retensi','DEMO','Laporan gabungan','Pengajuan per layanan'] as $text) check_report(str_contains($html,$text),'Penjelasan laporan hilang: '.$text);
$report = null;
$reportError = 'Tanggal tidak valid.';
ob_start(); require dirname(__DIR__) . '/app/views/admin-reports.php'; $html = ob_get_clean();
check_report(str_contains($html,'role="alert"') && !str_contains($html,'stat-number'), 'Error tidak boleh menampilkan ringkasan sukses/palsu.');
echo "PASS: periode, kabisat, batas detik WIB/UTC, akhir inklusif, retensi, status, snapshot, agregasi DEMO, antrean menurut tanggal dibuat, kosong/error, privasi dan escaping.\n";
