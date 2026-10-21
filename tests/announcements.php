<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/migrations.php';
require_once dirname(__DIR__) . '/app/announcements.php';
function announcement_check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function announcement_reject(callable $operation): void
{
    try { $operation(); } catch (DomainException $error) { return; }
    throw new RuntimeException('Operasi/input tidak valid diterima.');
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON');
run_migrations($pdo); run_migrations($pdo);
$pdo->exec("INSERT INTO users(id,name,username,password_hash) VALUES(1,'Test','test','test')");
$now = new DateTimeImmutable('2026-10-21 12:30:59', new DateTimeZone('Asia/Jakarta'));
announcement_check(public_announcements($pdo, $now) === [], 'Awal tidak kosong.');
$input = ['title' => 'Pengumuman contoh', 'body' => "Baris satu\nBaris dua", 'starts_at' => '', 'ends_at' => '', 'priority' => '0', 'is_published' => '1'];
foreach (['title' => '', 'body' => [], 'priority' => -1, 'is_published' => [], 'starts_at' => '2026-02-30T12:00', 'ends_at' => '2026-10-21 12:30'] as $key => $value) {
    announcement_reject(fn() => mutate_announcement($pdo, 'save', [...$input, $key => $value], 0, 0, 1));
}
foreach (['title' => 180, 'body' => 10000] as $key => $max) announcement_reject(fn() => validate_announcement([...$input, $key => str_repeat('a', $max + 1)]));
announcement_reject(fn() => validate_announcement([...$input, 'priority' => '10000']));
announcement_reject(fn() => validate_announcement([...$input, 'starts_at' => '2026-10-22T00:00', 'ends_at' => '2026-10-21T23:59']));
$id = mutate_announcement($pdo, 'save', $input, 0, 0, 1);
$high = mutate_announcement($pdo, 'save', [...$input, 'title' => str_repeat('t', 180), 'body' => str_repeat('b', 10000), 'priority' => '9999',
    'starts_at' => '2026-10-21T12:30', 'ends_at' => '2026-10-21T12:30'], 0, 0, 1);
announcement_check(array_column(public_announcements($pdo, $now), 'id') === [$high, $id], 'Prioritas/menit inklusif salah.');
announcement_check(count(public_announcements($pdo, $now->setTimezone(new DateTimeZone('UTC')))) === 2, 'WIB tidak konsisten.');
foreach (['2026-10-21 12:29:59', '2026-10-21 12:31:00'] as $time) announcement_check(count(public_announcements($pdo, new DateTimeImmutable($time, new DateTimeZone('Asia/Jakarta')))) === 1, 'Di luar jadwal masih tampil.');
$future = mutate_announcement($pdo, 'save', [...$input, 'starts_at' => '2026-10-22T00:00'], 0, 0, 1);
$past = mutate_announcement($pdo, 'save', [...$input, 'ends_at' => '2026-10-20T23:59'], 0, 0, 1);
announcement_check(count(public_announcements($pdo, $now)) === 2, 'Jadwal satu sisi tidak dipatuhi.');
mutate_announcement($pdo, 'toggle', [], $id, 1, 1);
announcement_check(count(public_announcements($pdo, $now)) === 1, 'OFF masih tampil.');
announcement_reject(fn() => mutate_announcement($pdo, 'toggle', ['is_published' => '1'], $id, 1, 1));
announcement_reject(fn() => mutate_announcement($pdo, 'delete', [], $id, 1, 1));
mutate_announcement($pdo, 'toggle', ['is_published' => '1'], $id, 2, 1);
announcement_check(count(public_announcements($pdo, $now)) === 2, 'ON tidak pulih.');
mutate_announcement($pdo, 'save', [...$input, 'title' => '<script>Judul</script>', 'body' => '<img src=x onerror=alert(1)>'], $id, 3, 1);
foreach (['save', 'toggle', 'delete'] as $action) {
    try {
        mutate_announcement($pdo, $action, $input, $id, 4, 999);
        throw new RuntimeException('Audit invalid diterima.');
    } catch (PDOException $error) {
        announcement_check((int) $pdo->query('SELECT version FROM announcements WHERE id=' . $id)->fetchColumn() === 4, 'Mutasi tidak rollback.');
    }
}
function kiosk_escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function escape(string $value): string { return kiosk_escape($value); }
function csrf_token(): string { return 'test'; }
$announcements = public_announcements($pdo, $now); $announcementsError = '';
ob_start(); require dirname(__DIR__) . '/app/views/public-announcements.php'; $html = ob_get_clean();
announcement_check(str_contains($html, '&lt;script&gt;Judul&lt;/script&gt;') && !str_contains($html, '<img src=x'), 'Public tidak di-escape.');
$announcements = [];
ob_start(); require dirname(__DIR__) . '/app/views/public-announcements.php'; $html = ob_get_clean();
announcement_check(trim($html) === '', 'Blok kosong masih tampil.');
$announcementsError = 'Uji gagal database';
ob_start(); require dirname(__DIR__) . '/app/views/public-announcements.php'; $html = ob_get_clean();
announcement_check(str_contains($html, 'role="alert"') && str_contains($html, $announcementsError), 'Error tidak eksplisit.');
$announcementId = $id; $announcementError = ''; $announcementModalOpen = true;
$announcementForm = $pdo->query('SELECT * FROM announcements WHERE id=' . $id)->fetch();
$announcementForm['starts_at'] = ''; $announcementForm['ends_at'] = '';
$announcementRecords = $pdo->query('SELECT * FROM announcements')->fetchAll();
ob_start(); require dirname(__DIR__) . '/app/views/admin-announcements.php'; $html = ob_get_clean();
foreach (['id="announcement-table"', 'role="switch"', 'open data-auto-open', 'category-dialog-footer', 'form="announcement-form"', 'button-danger-outline', '&lt;script&gt;Judul&lt;/script&gt;'] as $markup) announcement_check(str_contains($html, $markup), 'Kontrol hilang: ' . $markup);
announcement_check(!str_contains($html, '<script>Judul</script>'), 'Admin tidak di-escape.');
mutate_announcement($pdo, 'delete', [], $id, 4, 1);
announcement_reject(fn() => mutate_announcement($pdo, 'delete', [], $id, 4, 1));
announcement_check((int) $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action='announcement_delete'")->fetchColumn() === 1, 'Audit delete salah.');
announcement_check((int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn() === 0, 'Mutasi menyentuh master lain.');
echo "PASS: pengumuman CRUD, validasi/batas, prioritas, jadwal WIB inklusif, ON/OFF, konflik, rollback/audit, modal dan escaping publik/admin.\n";
