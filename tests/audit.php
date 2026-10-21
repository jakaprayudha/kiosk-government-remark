<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/audit.php';
function audit_check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON');
run_migrations($pdo);
audit_check(audit_list($pdo, '2026-10-21', '2026-10-21', '', '', 'desc', '1')['total'] === 0, 'Audit kosong harus kosong.');
$pdo->exec("INSERT INTO users(id,name,username,password_hash) VALUES(1,'<script>Nama</script>','uji.audit','HASH-RAHASIA')");
$insert = $pdo->prepare("INSERT INTO audit_logs(user_id,action,entity_type,entity_id,summary,created_at) VALUES(1,?,'user',1,?,?)");
foreach (['2026-10-20 16:59:59', '2026-10-20 17:00:00', '2026-10-21 16:59:59', '2026-10-21 17:00:00'] as $time) {
    $insert->execute(['login', '<b>Ringkasan</b>', $time]);
}
for ($i = 0; $i < 23; $i++) $insert->execute(['future_action', 'Literal %_', '2026-10-21 00:00:00']);
$list = audit_list($pdo, '2026-10-21', '2026-10-21', '', '', 'desc', '1');
audit_check($list['total'] === 25 && count($list['rows']) === 20 && $list['pages'] === 2, 'Batas WIB dan pagination salah.');
$second = audit_list($pdo, '2026-10-21', '2026-10-21', '', '', 'desc', '2');
audit_check(count($second['rows']) === 5 && !array_intersect(array_column($list['rows'], 'id'), array_column($second['rows'], 'id')), 'Pagination tidak boleh duplikat.');
$asc = audit_list($pdo, '2026-10-21', '2026-10-21', '', '', 'asc', '1');
audit_check((int) $asc['rows'][0]['id'] === 2 && (int) $list['rows'][0]['id'] === 3, 'Urutan waktu salah.');
audit_check((int) $list['rows'][1]['id'] === 27, 'Urutan ID harus stabil untuk timestamp sama.');
audit_check(audit_list($pdo, '2026-10-21', '2026-10-21', 'login', 'UJI.AUDIT', 'desc', '1')['total'] === 2, 'Filter aksi dan pencarian username salah.');
audit_check(audit_list($pdo, '2026-10-21', '2026-10-21', '', '%_', 'desc', '1')['total'] === 23, 'Pencarian harus literal, bukan wildcard.');
audit_check(audit_list($pdo, '2026-10-21', '2026-10-21', "' OR 1=1 --", '', 'desc', '1')['total'] === 0, 'Filter harus parameterized.');
audit_check(audit_list($pdo, '2026-10-21', '2026-10-21', '', '', 'desc', '999')['page'] === 2, 'Halaman di luar batas harus dibatasi.');
foreach ([
    ['bad','2026-10-21','','','desc','1'],
    ['2026-10-21','2026-10-21',[],'','desc','1'],
    ['2026-10-21','2026-10-21','',[],'desc','1'],
    ['2026-10-21','2026-10-21','','','DESC; DELETE','1'],
    ['2026-10-21','2026-10-21','','','desc',[]],
    ['2026-10-21','2026-10-21','','','desc','-1'],
] as $args) {
    try { audit_list($pdo, ...$args); throw new RuntimeException('Filter salah diterima.'); } catch (DomainException) {}
}
function escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$auditFilters = ['from'=>'2026-10-21', 'until'=>'2026-10-21', 'action'=>'', 'q'=>'', 'order'=>'desc'];
$auditError = '';
$actionLabels = audit_action_labels();
$audit = $list;
ob_start(); require dirname(__DIR__) . '/app/views/admin-audit.php'; $html = ob_get_clean();
audit_check(str_contains($html, '&lt;script&gt;Nama&lt;/script&gt;') && str_contains($html, '&lt;b&gt;Ringkasan&lt;/b&gt;') && !str_contains($html, 'HASH-RAHASIA'), 'Identitas dan ringkasan harus di-escape tanpa hash.');
audit_check(str_contains($html, '22/10/2026') === false && str_contains($html, '23:59:59'), 'Tampilan harus WIB.');
$pdo->exec('DELETE FROM users WHERE id=1');
audit_check(audit_list($pdo, '2026-10-21', '2026-10-21', '', '', 'desc', '1')['total'] === 25, 'Audit akun terhapus harus tetap ada.');
$audit = audit_list($pdo, '2026-10-21', '2026-10-21', '', '', 'desc', '1');
ob_start(); require dirname(__DIR__) . '/app/views/admin-audit.php'; $html = ob_get_clean();
audit_check(str_contains($html, 'Akun tidak tersedia') && str_contains($html, 'future_action'), 'Fallback akun/aksi baru harus jelas.');
$audit = null; $auditError = 'Audit gagal dimuat.';
ob_start(); require dirname(__DIR__) . '/app/views/admin-audit.php'; $html = ob_get_clean();
audit_check(str_contains($html, 'role="alert"') && !str_contains($html, '<tbody>'), 'Kegagalan bukan daftar kosong.');
echo "PASS: audit WIB, filter literal, parameter query, urutan stabil, pagination, validasi, escaping, akun terhapus, aksi baru dan error.\n";
