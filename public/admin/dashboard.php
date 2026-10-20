<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
$admin = require_admin();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f7fb">
    <title>Panel Admin — KIOSK Kecamatan</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <main class="dashboard-shell">
        <header class="dashboard-header">
            <a class="auth-brand" href="../index.php">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 40 40" fill="none"><path d="M7 16.5 20 7l13 9.5v15a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2v-15Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/><path d="M15 33V21h10v12M13 16h14" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/></svg>
                </span>
                <span class="brand-copy"><strong>KIOSK</strong><span>KECAMATAN</span></span>
            </a>
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                <button class="button-outline" type="submit">Keluar</button>
            </form>
        </header>
        <section class="dashboard-card">
            <span class="form-eyebrow">PANEL ADMIN</span>
            <h1>Selamat datang, <?= escape($admin['name']) ?>.</h1>
            <p>Anda berhasil masuk ke panel pengelola KIOSK Kecamatan.</p>
            <div class="dashboard-notice">
                <strong>Setup login selesai.</strong>
                <span>Fitur pengelolaan layanan dan pengajuan akan ditambahkan pada tahap berikutnya.</span>
            </div>
            <a class="back-link dashboard-back" href="../index.php"><span aria-hidden="true">←</span> Kembali ke landing page</a>
        </section>
    </main>
</body>
</html>
