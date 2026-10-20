<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

if (current_admin() !== null) {
    redirect_to('/admin/dashboard.php');
}

$errorMessage = '';
$successMessage = isset($_GET['reset']) ? 'Password berhasil diperbarui. Silakan masuk dengan password baru.' : '';
$username = '';
$databaseReady = is_file(dirname(__DIR__, 2) . '/database/kiosk.sqlite');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!verify_csrf_token()) {
        http_response_code(400);
        $errorMessage = 'Sesi formulir berakhir. Muat ulang halaman dan coba kembali.';
    } elseif (!$databaseReady) {
        http_response_code(503);
        $errorMessage = 'Aplikasi belum disiapkan. Pengelola perlu menjalankan php bin/setup-admin.php.';
    } else {
        $identityHash = rate_limit_identity($username);
        if (is_rate_limited('login', $identityHash)) {
            http_response_code(429);
            $errorMessage = 'Terlalu banyak percobaan. Coba lagi setelah 15 menit.';
        } else {
            $statement = db()->prepare(
                'SELECT id, name, username, password_hash, role, is_active
                 FROM users
                 WHERE username = :username COLLATE NOCASE'
            );
            $statement->execute(['username' => $username]);
            $user = $statement->fetch();
            $passwordValid = $user && password_verify($password, $user['password_hash']);

            if (!$passwordValid || (int) $user['is_active'] !== 1 || $user['role'] !== 'admin') {
                record_auth_attempt('login', $identityHash);
                $errorMessage = 'Username atau password tidak sesuai.';
            } else {
                clear_auth_attempts('login', $identityHash);
                session_regenerate_id(true);
                $_SESSION['admin_user_id'] = (int) $user['id'];
                unset($_SESSION['csrf_token']);

                $update = db()->prepare(
                    'UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id'
                );
                $update->execute(['id' => $user['id']]);
                record_admin_event((int) $user['id'], 'login', 'Admin masuk ke panel.');
                redirect_to('/admin/dashboard.php');
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f4f7fb">
    <title>Login Admin — KIOSK Kecamatan</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <script src="../assets/js/auth.js" defer></script>
</head>
<body>
    <a class="skip-link" href="#login-form">Lewati ke formulir login</a>
    <main class="auth-shell">
        <a class="auth-brand" href="../index.php">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 40 40" fill="none"><path d="M7 16.5 20 7l13 9.5v15a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2v-15Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/><path d="M15 33V21h10v12M13 16h14" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/></svg>
            </span>
            <span class="brand-copy"><strong>KIOSK</strong><span>KECAMATAN</span></span>
        </a>

        <div class="auth-card">
            <section class="auth-aside">
                <span class="aside-label">PANEL PENGELOLA</span>
                <div class="aside-illustration" aria-hidden="true">
                    <div class="aside-circle circle-back"></div>
                    <div class="aside-building">
                        <span class="aside-roof"></span>
                        <span class="aside-building-sign">
                            <svg viewBox="0 0 32 32"><path d="M16 3 4 11v17h24V11L16 3Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M11 28V17h10v11M8 13h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                        </span>
                        <span class="aside-columns"><i></i><i></i><i></i></span>
                    </div>
                    <span class="aside-shield"><svg viewBox="0 0 24 24"><path d="m12 3 8 4v5c0 4.2-3.4 7.4-8 9-4.6-1.6-8-4.8-8-9V7l8-4Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="m8.5 12 2.2 2.2 4.8-4.8" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </div>
                <h1>Selamat datang kembali.</h1>
                <p>Masuk untuk mengelola informasi dan layanan KIOSK Kecamatan.</p>
                <div class="aside-note"><span aria-hidden="true">●</span> Akses khusus petugas berwenang</div>
            </section>

            <section class="auth-form-panel">
                <a class="back-link" href="../index.php"><span aria-hidden="true">←</span> Kembali ke beranda</a>
                <div class="form-heading">
                    <span class="form-eyebrow">LOGIN ADMIN</span>
                    <h2>Masuk ke panel</h2>
                    <p>Gunakan akun admin yang sudah disiapkan.</p>
                </div>

                <?php if ($successMessage !== ''): ?>
                    <div class="alert alert-success" role="status"><?= escape($successMessage) ?></div>
                <?php endif; ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-error" role="alert"><?= escape($errorMessage) ?></div>
                <?php endif; ?>

                <form id="login-form" class="auth-form" method="post" action="login.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                    <label class="field-label" for="username">Username</label>
                    <input class="form-input" id="username" name="username" type="text" value="<?= escape($username) ?>" placeholder="Masukkan username" autocomplete="username" inputmode="text" autocapitalize="none" spellcheck="false" maxlength="50" required>

                    <div class="field-heading">
                        <label class="field-label" for="password">Password</label>
                        <a class="field-link" href="reset-password.php">Lupa password?</a>
                    </div>
                    <div class="password-wrap">
                        <input class="form-input" id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" inputmode="text" maxlength="72" required>
                        <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Tampilkan password">Lihat</button>
                    </div>
                    <p class="field-hint">Gunakan keyboard perangkat atau tombol keyboard layar sentuh.</p>

                    <button class="submit-button" type="submit">Masuk <span aria-hidden="true">→</span></button>
                </form>
                <?php require dirname(__DIR__, 2) . '/app/views/auth-keyboard.php'; ?>
                <p class="auth-footnote"><span aria-hidden="true">◈</span> Halaman ini hanya untuk pengelola kecamatan.</p>
            </section>
        </div>
        <footer class="auth-footer"><span>© <?= date('Y') ?> KIOSK Kecamatan</span><a href="../index.php">Informasi layanan publik</a></footer>
    </main>
</body>
</html>
