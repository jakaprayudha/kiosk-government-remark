<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

if (current_admin() !== null) {
    redirect_to('/admin/dashboard.php');
}

$errorMessage = '';
$username = '';
$databaseReady = is_file(dirname(__DIR__, 2) . '/database/kiosk.sqlite');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $recoveryCode = is_string($_POST['recovery_code'] ?? null) ? strtolower(trim($_POST['recovery_code'])) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmation = is_string($_POST['password_confirmation'] ?? null) ? $_POST['password_confirmation'] : '';

    if (!verify_csrf_token()) {
        http_response_code(400);
        $errorMessage = 'Sesi formulir berakhir. Muat ulang halaman dan coba kembali.';
    } elseif (!$databaseReady) {
        http_response_code(503);
        $errorMessage = 'Aplikasi belum disiapkan. Pengelola perlu menjalankan php bin/setup-admin.php.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username) || !preg_match('/^[a-f0-9]{64}$/', $recoveryCode)) {
        $errorMessage = 'Username atau kode pemulihan tidak sesuai.';
    } elseif (strlen($password) < 12 || strlen($password) > 72) {
        $errorMessage = 'Password baru harus terdiri dari 12 hingga 72 byte.';
    } elseif (!hash_equals($password, $confirmation)) {
        $errorMessage = 'Konfirmasi password baru tidak cocok.';
    } else {
        $identityHash = rate_limit_identity($username);
        if (is_rate_limited('reset', $identityHash)) {
            http_response_code(429);
            $errorMessage = 'Terlalu banyak percobaan. Coba lagi setelah 15 menit.';
        } else {
            $pdo = db();
            $userQuery = $pdo->prepare(
                "SELECT id FROM users
                 WHERE username = :username COLLATE NOCASE
                   AND role = 'admin'
                   AND is_active = 1"
            );
            $userQuery->execute(['username' => $username]);
            $userId = $userQuery->fetchColumn();
            $validCodeId = false;

            if ($userId !== false) {
                $codeQuery = $pdo->prepare(
                    'SELECT id, code_hash FROM recovery_codes
                     WHERE user_id = :user_id AND used_at IS NULL'
                );
                $codeQuery->execute(['user_id' => $userId]);
                foreach ($codeQuery->fetchAll() as $storedCode) {
                    if (password_verify($recoveryCode, $storedCode['code_hash'])) {
                        $validCodeId = (int) $storedCode['id'];
                        break;
                    }
                }
            }

            if ($validCodeId === false) {
                record_auth_attempt('reset', $identityHash);
                $errorMessage = 'Username atau kode pemulihan tidak sesuai.';
            } else {
                $pdo->beginTransaction();
                $consumeCode = $pdo->prepare(
                    'UPDATE recovery_codes
                     SET used_at = CURRENT_TIMESTAMP
                     WHERE id = :id AND used_at IS NULL'
                );
                $consumeCode->execute(['id' => $validCodeId]);

                if ($consumeCode->rowCount() !== 1) {
                    $pdo->rollBack();
                    record_auth_attempt('reset', $identityHash);
                    $errorMessage = 'Username atau kode pemulihan tidak sesuai.';
                } else {
                    $updatePassword = $pdo->prepare(
                        'UPDATE users
                         SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id AND is_active = 1'
                    );
                    $updatePassword->execute([
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'id' => $userId,
                    ]);
                    record_admin_event((int) $userId, 'password_reset', 'Password admin diperbarui menggunakan kode pemulihan.');
                    $pdo->commit();
                    clear_auth_attempts('reset', $identityHash);
                    redirect_to('/admin/login.php?reset=1');
                }
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
    <title>Reset Password — KIOSK Kecamatan</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <script src="../assets/js/auth.js" defer></script>
</head>
<body>
    <a class="skip-link" href="#reset-form">Lewati ke formulir reset password</a>
    <main class="auth-shell">
        <a class="auth-brand" href="../index.php">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 40 40" fill="none"><path d="M7 16.5 20 7l13 9.5v15a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2v-15Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/><path d="M15 33V21h10v12M13 16h14" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/></svg>
            </span>
            <span class="brand-copy"><strong>KIOSK</strong><span>KECAMATAN</span></span>
        </a>

        <div class="auth-card">
            <section class="auth-aside">
                <span class="aside-label">PEMULIHAN AKUN</span>
                <div class="aside-illustration" aria-hidden="true">
                    <div class="aside-circle circle-back"></div>
                    <div class="recovery-lock">
                        <svg viewBox="0 0 90 100"><path d="M24 43V29a21 21 0 0 1 42 0v14" fill="none" stroke="currentColor" stroke-width="8" stroke-linecap="round"/><rect x="13" y="40" width="64" height="49" rx="9" fill="currentColor"/><circle cx="45" cy="61" r="6" fill="#fff"/><path d="M45 67v9" stroke="#fff" stroke-width="5" stroke-linecap="round"/></svg>
                    </div>
                    <span class="aside-shield"><svg viewBox="0 0 24 24"><path d="m12 3 8 4v5c0 4.2-3.4 7.4-8 9-4.6-1.6-8-4.8-8-9V7l8-4Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="m8.5 12 2.2 2.2 4.8-4.8" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </div>
                <h1>Pulihkan akses akun.</h1>
                <p>Gunakan kode pemulihan yang diberikan saat setup awal admin.</p>
                <div class="aside-note"><span aria-hidden="true">●</span> Kode hanya dapat digunakan satu kali</div>
            </section>

            <section class="auth-form-panel reset-panel">
                <a class="back-link" href="login.php"><span aria-hidden="true">←</span> Kembali ke login</a>
                <div class="form-heading">
                    <span class="form-eyebrow">RESET PASSWORD</span>
                    <h2>Buat password baru</h2>
                    <p>Masukkan username, kode pemulihan, dan password baru.</p>
                </div>

                <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-error" role="alert"><?= escape($errorMessage) ?></div>
                <?php endif; ?>

                <form id="reset-form" class="auth-form" method="post" action="reset-password.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                    <label class="field-label" for="username">Username admin</label>
                    <input class="form-input" id="username" name="username" type="text" value="<?= escape($username) ?>" placeholder="Masukkan username admin" autocomplete="username" inputmode="text" autocapitalize="none" spellcheck="false" maxlength="50" required>

                    <label class="field-label" for="recovery-code">Kode pemulihan</label>
                    <input class="form-input" id="recovery-code" name="recovery_code" type="text" placeholder="Masukkan kode 64 karakter" autocomplete="off" inputmode="text" autocapitalize="none" spellcheck="false" maxlength="64" required>
                    <p class="field-hint">Kode ini dibuat sekali saat setup admin. Kode akan hangus setelah digunakan.</p>

                    <label class="field-label" for="password">Password baru</label>
                    <div class="password-wrap">
                        <input class="form-input" id="password" name="password" type="password" placeholder="Minimal 12 karakter" autocomplete="new-password" inputmode="text" minlength="12" maxlength="72" required>
                        <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Tampilkan password">Lihat</button>
                    </div>

                    <label class="field-label" for="password-confirmation">Ulangi password baru</label>
                    <div class="password-wrap">
                        <input class="form-input" id="password-confirmation" name="password_confirmation" type="password" placeholder="Ulangi password baru" autocomplete="new-password" inputmode="text" minlength="12" maxlength="72" required>
                        <button class="password-toggle" type="button" data-password-toggle="password-confirmation" aria-label="Tampilkan password">Lihat</button>
                    </div>
                    <p class="field-hint">Panjang password 12–72 byte. Gunakan kombinasi yang sulit ditebak.</p>

                    <button class="submit-button" type="submit">Simpan password baru <span aria-hidden="true">→</span></button>
                </form>
                <?php require dirname(__DIR__, 2) . '/app/views/auth-keyboard.php'; ?>
                <p class="auth-footnote"><span aria-hidden="true">◈</span> Jangan bagikan kode pemulihan kepada orang lain.</p>
            </section>
        </div>
        <footer class="auth-footer"><span>© <?= date('Y') ?> KIOSK Kecamatan</span><a href="../index.php">Informasi layanan publik</a></footer>
    </main>
</body>
</html>
