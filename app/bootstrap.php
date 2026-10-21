<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
session_name('kiosk_admin_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function db(): PDO
{
    static $connection;

    if (!$connection instanceof PDO) {
        $connection = open_database();
    }

    return $connection;
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(): bool
{
    $submittedToken = $_POST['csrf_token'] ?? '';

    return is_string($submittedToken)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submittedToken);
}

function redirect_to(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function current_admin(): ?array
{
    $userId = $_SESSION['admin_user_id'] ?? null;
    if (!is_int($userId)) {
        return null;
    }

    $statement = db()->prepare(
        'SELECT id, name, username, role, is_active, office_id
         FROM users
         WHERE id = :id'
    );
    $statement->execute(['id' => $userId]);
    $admin = $statement->fetch();

    if (!$admin || (int) $admin['is_active'] !== 1 || $admin['role'] !== 'admin') {
        unset($_SESSION['admin_user_id']);
        return null;
    }

    return $admin;
}

function require_admin(bool $allowOfficeAdmin = false): array
{
    $admin = current_admin();
    if ($admin === null) {
        redirect_to('/admin/login.php');
    }
    if (!$allowOfficeAdmin && $admin['office_id'] !== null) {
        http_response_code(403);
        exit('Akses terbatas untuk admin pusat.');
    }

    return $admin;
}

function rate_limit_identity(string $username): string
{
    $address = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return hash('sha256', strtolower(trim($username)) . '|' . $address);
}

function is_rate_limited(string $action, string $identityHash): bool
{
    $statement = db()->prepare(
        "SELECT COUNT(*)
         FROM auth_attempts
         WHERE action = :action
           AND identity_hash = :identity_hash
           AND attempted_at >= datetime('now', '-15 minutes')"
    );
    $statement->execute(['action' => $action, 'identity_hash' => $identityHash]);

    return (int) $statement->fetchColumn() >= 5;
}

function record_auth_attempt(string $action, string $identityHash): void
{
    $pdo = db();
    $pdo->exec("DELETE FROM auth_attempts WHERE attempted_at < datetime('now', '-1 day')");
    $statement = $pdo->prepare(
        'INSERT INTO auth_attempts (action, identity_hash, attempted_at)
         VALUES (:action, :identity_hash, CURRENT_TIMESTAMP)'
    );
    $statement->execute(['action' => $action, 'identity_hash' => $identityHash]);
}

function clear_auth_attempts(string $action, string $identityHash): void
{
    $statement = db()->prepare(
        'DELETE FROM auth_attempts WHERE action = :action AND identity_hash = :identity_hash'
    );
    $statement->execute(['action' => $action, 'identity_hash' => $identityHash]);
}

function record_admin_event(int $userId, string $action, string $summary): void
{
    $statement = db()->prepare(
        'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, summary, created_at)
         VALUES (:user_id, :action, :entity_type, :entity_id, :summary, CURRENT_TIMESTAMP)'
    );
    $statement->execute([
        'user_id' => $userId,
        'action' => $action,
        'entity_type' => 'user',
        'entity_id' => $userId,
        'summary' => $summary,
    ]);
}
