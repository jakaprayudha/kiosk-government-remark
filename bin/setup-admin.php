<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/migrations.php';

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    return trim((string) fgets(STDIN));
}

function prompt_hidden(string $label): string
{
    fwrite(STDOUT, $label);
    $hidden = false;
    if (DIRECTORY_SEPARATOR !== '\\' && function_exists('exec')) {
        exec('stty -echo 2>/dev/null', $output, $status);
        $hidden = $status === 0;
    }

    try {
        $value = trim((string) fgets(STDIN));
    } finally {
        if ($hidden) {
            exec('stty echo 2>/dev/null');
            fwrite(STDOUT, PHP_EOL);
        }
    }

    return $value;
}

try {
    $pdo = open_database(true);
    run_migrations($pdo);
    $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($adminCount > 0) {
        throw new RuntimeException('Admin sudah tersedia. Setup awal hanya dapat dijalankan satu kali.');
    }

    fwrite(STDOUT, "Setup awal KIOSK Kecamatan — buat akun admin pertama.\n");
    $name = prompt('Nama admin: ');
    $username = prompt('Username (3-50 karakter: huruf, angka, titik, _ atau -): ');
    if ($name === '' || strlen($name) > 120) {
        throw new RuntimeException('Nama admin wajib diisi dan maksimal 120 karakter.');
    }
    if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
        throw new RuntimeException('Format username tidak valid.');
    }

    $password = prompt_hidden('Password (12-72 byte): ');
    $confirmation = prompt_hidden('Ulangi password: ');
    if (strlen($password) < 12 || strlen($password) > 72) {
        throw new RuntimeException('Password harus terdiri dari 12 hingga 72 byte.');
    }
    if (!hash_equals($password, $confirmation)) {
        throw new RuntimeException('Konfirmasi password tidak cocok.');
    }

    $recoveryCode = bin2hex(random_bytes(32));
    $pdo->beginTransaction();
    $insertUser = $pdo->prepare(
        'INSERT INTO users (name, username, password_hash, role, is_active)
         VALUES (:name, :username, :password_hash, :role, 1)'
    );
    $insertUser->execute([
        'name' => $name,
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'admin',
    ]);
    $userId = (int) $pdo->lastInsertId();

    $insertCode = $pdo->prepare(
        'INSERT INTO recovery_codes (user_id, code_hash) VALUES (:user_id, :code_hash)'
    );
    $insertCode->execute([
        'user_id' => $userId,
        'code_hash' => password_hash($recoveryCode, PASSWORD_DEFAULT),
    ]);
    $pdo->commit();

    fwrite(STDOUT, "\nAdmin berhasil dibuat.\n");
    fwrite(STDOUT, "Kode pemulihan (tampil sekali; simpan di tempat aman):\n");
    fwrite(STDOUT, $recoveryCode . "\n");
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Setup gagal: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
