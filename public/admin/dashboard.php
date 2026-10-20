<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/admin-navigation.php';
$admin = require_admin();
$navigation = admin_navigation();
$module = $_GET['module'] ?? 'dashboard';

if (!is_string($module) || !isset($navigation[$module])) {
    http_response_code(404);
    exit('Menu admin tidak ditemukan.');
}

$page = $navigation[$module];
$stats = null;
$events = [];
if ($module === 'dashboard') {
    $stats = db()->query(
        "SELECT
            (SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1) AS active_admins,
            (SELECT COUNT(*) FROM users WHERE role = 'officer' AND is_active = 1) AS active_officers,
            (SELECT COUNT(*) FROM audit_logs) AS total_events,
            (SELECT COUNT(*) FROM audit_logs
             WHERE date(created_at, '+7 hours') = date('now', '+7 hours')) AS today_events"
    )->fetch();
    $events = db()->query(
        'SELECT audit_logs.action, audit_logs.summary, audit_logs.created_at, users.name
         FROM audit_logs LEFT JOIN users ON users.id = audit_logs.user_id
         ORDER BY audit_logs.created_at DESC, audit_logs.id DESC LIMIT 7'
    )->fetchAll();
}
$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
$actionLabels = ['login' => 'Login', 'logout' => 'Logout', 'password_reset' => 'Reset password'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title><?= escape($page['label']) ?> — Admin KIOSK Kecamatan</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="../assets/js/admin.js" defer></script>
</head>
<body>
    <a class="skip-link" href="#admin-content">Lewati ke konten utama</a>
    <aside class="sidebar" id="admin-sidebar">
        <a class="brand" href="dashboard.php">
            <span class="brand-mark"><?= admin_icon('building') ?></span>
            <span><strong>KIOSK<span class="brand-subtitle">KECAMATAN</span></strong></span>
            <span class="brand-badge">ADMIN</span>
        </a>
        <nav class="sidebar-nav" aria-label="Menu admin">
            <?php $previousGroup = ''; ?>
            <?php foreach ($navigation as $key => $item): ?>
                <?php if ($previousGroup !== $item['group']): ?>
                    <p class="nav-group"><?= escape($item['group']) ?></p>
                    <?php $previousGroup = $item['group']; ?>
                <?php endif; ?>
                <a class="nav-link <?= $module === $key ? 'is-active' : '' ?>"
                   href="dashboard.php<?= $key === 'dashboard' ? '' : '?module=' . escape($key) ?>"
                   <?= $module === $key ? 'aria-current="page"' : '' ?>>
                    <?= admin_icon($item['icon']) ?>
                    <span><?= escape($item['label']) ?></span>
                    <?php if ($key !== 'dashboard'): ?><span class="planned-dot" title="Modul belum tersedia"><span class="sr-only">Belum tersedia</span></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <a class="nav-link" href="../kiosk.php" target="_blank" rel="noopener noreferrer"><?= admin_icon('external') ?><span>Lihat KIOSK publik<span class="sr-only"> (tab baru)</span></span></a>
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                <button class="nav-link logout-button" type="submit"><?= admin_icon('logout') ?><span>Keluar dari akun</span></button>
            </form>
        </div>
    </aside>

    <div class="admin-main">
        <header class="topbar">
            <button class="menu-button" type="button" aria-expanded="false" aria-controls="admin-sidebar" aria-label="Buka menu admin"><?= admin_icon('menu') ?></button>
            <div class="breadcrumb">Panel admin <span>/</span> <strong><?= escape($page['label']) ?></strong></div>
            <div class="account-chip">
                <span class="avatar" aria-hidden="true"><?= escape(strtoupper(substr($admin['username'], 0, 1))) ?></span>
                <span><strong><?= escape($admin['name']) ?></strong><small>Administrator</small></span>
            </div>
        </header>

        <main class="content" id="admin-content">
            <div class="page-heading">
                <div><span class="eyebrow">PENGELOLAAN KIOSK KECAMATAN</span><h1><?= escape($page['label']) ?></h1><p><?= $module === 'dashboard' ? 'Pantau aktivitas dan siapkan informasi layanan dari satu tempat.' : escape($page['description']) ?></p></div>
                <div class="date-label"><?= admin_icon('calendar') ?><span><?= $now->format('d/m/Y') ?><small>Waktu Indonesia Barat</small></span></div>
            </div>

            <?php if ($module === 'dashboard'): ?>
                <section class="welcome-banner">
                    <div><span class="banner-label">RUANG KERJA ADMIN</span><h2>Selamat datang, <?= escape($admin['name']) ?>.</h2><p>Panel pengelola untuk bekerja nyaman melalui laptop, keyboard, dan mouse.</p><a class="button button-white" href="../kiosk.php" target="_blank" rel="noopener noreferrer">Lihat KIOSK publik <span class="sr-only">(tab baru)</span><?= admin_icon('external') ?></a></div>
                    <div class="banner-art" aria-hidden="true"><?= admin_icon('building') ?><span>KIOSK KECAMATAN</span></div>
                </section>

                <section class="stats-grid" aria-label="Ringkasan data admin">
                    <?php foreach ([
                        ['label' => 'Admin aktif', 'value' => $stats['active_admins'], 'icon' => 'users', 'tone' => 'blue', 'note' => 'Akun administrator aktif'],
                        ['label' => 'Petugas aktif', 'value' => $stats['active_officers'], 'icon' => 'users', 'tone' => 'green', 'note' => 'Akun dengan peran petugas'],
                        ['label' => 'Aktivitas hari ini', 'value' => $stats['today_events'], 'icon' => 'clock', 'tone' => 'orange', 'note' => 'Dicatat berdasarkan WIB'],
                        ['label' => 'Total aktivitas', 'value' => $stats['total_events'], 'icon' => 'chart', 'tone' => 'purple', 'note' => 'Seluruh catatan audit tersimpan'],
                    ] as $stat): ?>
                        <article class="stat-card"><div class="stat-top"><span><?= escape($stat['label']) ?></span><span class="icon-box <?= escape($stat['tone']) ?>"><?= admin_icon($stat['icon']) ?></span></div><strong class="stat-number"><?= number_format((int) $stat['value'], 0, ',', '.') ?></strong><p><?= escape($stat['note']) ?></p></article>
                    <?php endforeach; ?>
                </section>

                <div class="dashboard-grid">
                    <section class="panel">
                        <div class="panel-heading"><div><h2>Aktivitas terbaru</h2><p>Catatan nyata dari database aplikasi.</p></div><span class="badge badge-live">Audit aktif</span></div>
                        <div class="table-scroll">
                            <table>
                                <caption class="sr-only">Tujuh aktivitas admin terbaru</caption>
                                <thead><tr><th>Pengguna / aktivitas</th><th>Aksi</th><th>Waktu (WIB)</th></tr></thead>
                                <tbody>
                                    <?php if ($events === []): ?>
                                        <tr><td colspan="3" class="empty-table">Belum ada aktivitas tercatat.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($events as $event): ?>
                                        <?php $eventTime = (new DateTimeImmutable($event['created_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta')); ?>
                                        <tr><td><strong><?= escape($event['name'] ?? 'Akun tidak tersedia') ?></strong><span class="table-note"><?= escape($event['summary']) ?></span></td><td><span class="action-label"><?= escape($actionLabels[$event['action']] ?? $event['action']) ?></span></td><td class="time-cell"><time datetime="<?= escape($eventTime->format(DATE_ATOM)) ?>"><?= $eventTime->format('d/m/Y') ?><span class="table-note"><?= $eventTime->format('H:i') ?></span></time></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="panel-footer">Menampilkan maksimal 7 aktivitas terakhir. Data diperbarui saat halaman dimuat.</div>
                    </section>

                    <section class="panel readiness">
                        <div class="panel-heading"><div><h2>Kesiapan sistem</h2><p>Status implementasi saat ini.</p></div></div>
                        <ul class="status-list">
                            <li><span class="status-check" aria-hidden="true">✓</span><div><strong>Login & pemulihan akun</strong><p>Autentikasi admin sudah tersedia.</p></div><span class="status-text">Aktif</span></li>
                            <li><span class="status-check" aria-hidden="true">✓</span><div><strong>Database SQLite</strong><p>Terhubung ke penyimpanan lokal.</p></div><span class="status-text">Aktif</span></li>
                            <li><span class="status-pending" aria-hidden="true">·</span><div><strong>Pengelolaan konten</strong><p>Tambah, edit, dan hapus menyusul.</p></div></li>
                            <li><span class="status-pending" aria-hidden="true">·</span><div><strong>Operasional layanan</strong><p>Pengajuan dan antrean menyusul.</p></div></li>
                        </ul>
                        <div class="notice"><strong>Belum ada statistik layanan</strong><p>Jumlah pengajuan, pengaduan, dan antrean akan ditampilkan setelah modul operasional tersedia. Tidak menggunakan data contoh.</p></div>
                    </section>
                </div>

                <section class="panel modules-panel">
                    <div class="panel-heading"><div><h2>Ruang pengelolaan KIOSK</h2><p>Pilih modul untuk melihat cakupan fitur yang akan disiapkan.</p></div><span class="badge">CRUD menyusul</span></div>
                    <div class="module-grid">
                        <?php foreach (['services', 'announcements', 'office', 'queues'] as $key): ?>
                            <a class="module-card" href="dashboard.php?module=<?= escape($key) ?>"><span class="icon-box blue"><?= admin_icon($navigation[$key]['icon']) ?></span><h3><?= escape($navigation[$key]['label']) ?></h3><p><?= escape($navigation[$key]['description']) ?></p><span class="module-link">Lihat rencana modul <?= admin_icon('arrow') ?></span></a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php else: ?>
                <section class="panel placeholder-panel">
                    <span class="placeholder-icon"><?= admin_icon($page['icon']) ?></span>
                    <span class="badge">MODUL BELUM TERSEDIA</span>
                    <h2><?= escape($page['label']) ?></h2>
                    <p><?= escape($page['description']) ?></p>
                    <div class="placeholder-note">Halaman ini baru menyiapkan navigasi. Fitur pengelolaan data akan dibuat pada tahap berikutnya; belum ada data yang dapat ditambah, diubah, atau dihapus di sini.</div>
                    <a class="button button-primary" href="dashboard.php">Kembali ke dashboard <?= admin_icon('arrow') ?></a>
                </section>
            <?php endif; ?>
            <footer class="admin-footer"><span>© <?= $now->format('Y') ?> KIOSK Kecamatan</span><span>Panel pengelola · Desktop & laptop</span></footer>
        </main>
    </div>
</body>
</html>
