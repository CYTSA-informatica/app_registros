<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

const REMEMBER_COOKIE_NAME = 'registros_remember';
const REMEMBER_TOKEN_DAYS = 30;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function ensure_remember_tokens_table(): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    app_pdo()->exec(
        "CREATE TABLE IF NOT EXISTS remember_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            selector CHAR(24) NOT NULL UNIQUE,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_remember_user (user_id),
            INDEX idx_remember_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $initialized = true;
}

function build_session_user(array $user): array
{
    return [
        'id' => (int) $user['id'],
        'username' => (string) $user['username'],
        'email' => (string) $user['email'],
        'dpto' => (string) ($user['dpto'] ?? ''),
        'isAdmin' => (bool) $user['isAdmin'],
    ];
}

function set_remember_cookie(string $value, int $expiresAt): void
{
    setcookie(REMEMBER_COOKIE_NAME, $value, [
        'expires' => $expiresAt,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function clear_remember_cookie(): void
{
    setcookie(REMEMBER_COOKIE_NAME, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function issue_remember_token(int $userId): void
{
    ensure_remember_tokens_table();

    $selector = bin2hex(random_bytes(12));
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = time() + (REMEMBER_TOKEN_DAYS * 24 * 60 * 60);
    $expiresAtSql = date('Y-m-d H:i:s', $expiresAt);

    $pdo = app_pdo();
    $stmt = $pdo->prepare('INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (:user_id, :selector, :token_hash, :expires_at)');
    $stmt->execute([
        'user_id' => $userId,
        'selector' => $selector,
        'token_hash' => $tokenHash,
        'expires_at' => $expiresAtSql,
    ]);

    set_remember_cookie($selector . ':' . $token, $expiresAt);
}

function refresh_remember_token_for_active_user(int $userId): void
{
    static $refreshed = false;

    if ($refreshed) {
        return;
    }
    $refreshed = true;

    $cookieValue = (string) ($_COOKIE[REMEMBER_COOKIE_NAME] ?? '');
    if ($cookieValue === '') {
        return;
    }

    $parts = explode(':', $cookieValue, 2);
    if (count($parts) !== 2) {
        clear_remember_cookie();
        return;
    }

    [$selector] = $parts;
    if ($selector === '') {
        clear_remember_cookie();
        return;
    }

    ensure_remember_tokens_table();

    $stmt = app_pdo()->prepare('SELECT id, user_id FROM remember_tokens WHERE selector = :selector LIMIT 1');
    $stmt->execute(['selector' => $selector]);
    $row = $stmt->fetch();

    if (!$row || (int) $row['user_id'] !== $userId) {
        clear_remember_cookie();
        return;
    }

    $newSelector = bin2hex(random_bytes(12));
    $newToken = bin2hex(random_bytes(32));
    $newTokenHash = hash('sha256', $newToken);
    $newExpiresAtTs = time() + (REMEMBER_TOKEN_DAYS * 24 * 60 * 60);
    $newExpiresAtSql = date('Y-m-d H:i:s', $newExpiresAtTs);

    $updateStmt = app_pdo()->prepare(
        'UPDATE remember_tokens SET selector = :selector, token_hash = :token_hash, expires_at = :expires_at WHERE id = :id'
    );
    $updateStmt->execute([
        'selector' => $newSelector,
        'token_hash' => $newTokenHash,
        'expires_at' => $newExpiresAtSql,
        'id' => (int) $row['id'],
    ]);

    set_remember_cookie($newSelector . ':' . $newToken, $newExpiresAtTs);
}

function clear_remember_token_from_db(): void
{
    if (empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
        return;
    }

    $cookieValue = (string) $_COOKIE[REMEMBER_COOKIE_NAME];
    $parts = explode(':', $cookieValue, 2);
    if (count($parts) !== 2) {
        return;
    }

    $selector = $parts[0];
    ensure_remember_tokens_table();

    $stmt = app_pdo()->prepare('DELETE FROM remember_tokens WHERE selector = :selector');
    $stmt->execute(['selector' => $selector]);
}

function establish_user_session(array $user, bool $remember = true): void
{
    $sessionUser = build_session_user($user);
    $_SESSION['user'] = $sessionUser;

    if ($remember) {
        try {
            issue_remember_token($sessionUser['id']);
        } catch (Throwable $e) {
            error_log('Failed to issue remember token: ' . $e->getMessage());
        }
    }
}

function try_restore_user_from_remember_cookie(): ?array
{
    if (!empty($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    $cookieValue = (string) ($_COOKIE[REMEMBER_COOKIE_NAME] ?? '');
    if ($cookieValue === '') {
        return null;
    }

    $parts = explode(':', $cookieValue, 2);
    if (count($parts) !== 2) {
        clear_remember_cookie();
        return null;
    }

    [$selector, $token] = $parts;
    if ($selector === '' || $token === '') {
        clear_remember_cookie();
        return null;
    }

    ensure_remember_tokens_table();

    $stmt = app_pdo()->prepare(
        'SELECT rt.id, rt.user_id, rt.token_hash, rt.expires_at, u.id, u.username, u.email, u.dpto, u.isAdmin
         FROM remember_tokens rt
         INNER JOIN users u ON u.id = rt.user_id
         WHERE rt.selector = :selector
         LIMIT 1'
    );
    $stmt->execute(['selector' => $selector]);
    $row = $stmt->fetch();

    if (!$row) {
        clear_remember_cookie();
        return null;
    }

    $expiresAt = strtotime((string) $row['expires_at']);
    $tokenMatches = hash_equals((string) $row['token_hash'], hash('sha256', $token));
    if (!is_int($expiresAt) || $expiresAt < time() || !$tokenMatches) {
        $deleteStmt = app_pdo()->prepare('DELETE FROM remember_tokens WHERE id = :id');
        $deleteStmt->execute(['id' => (int) $row['id']]);
        clear_remember_cookie();
        return null;
    }

    $_SESSION['user'] = build_session_user($row);
    
    return $_SESSION['user'];
}

function current_user(): ?array
{
    if (!empty($_SESSION['user'])) {
        refresh_remember_token_for_active_user((int) $_SESSION['user']['id']);
        return $_SESSION['user'];
    }

    return try_restore_user_from_remember_cookie();
}

function is_admin(): bool
{
    return (bool) (current_user()['isAdmin'] ?? false);
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: /index.php');
        exit;
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        http_response_code(403);
        exit('No tienes permisos para realizar esta accion.');
    }
}

function login_user(string $email, string $password): bool
{
    $identifier = trim($email);
    if ($identifier === '') {
        return false;
    }

    $stmt = app_pdo()->prepare('SELECT id, username, email, dpto, contra_hash, isAdmin FROM users WHERE username = :identifier LIMIT 1');
    $stmt->execute(['identifier' => $identifier]);
    $user = $stmt->fetch();

    if ($user) {
        $storedPassword = (string) ($user['contra_hash'] ?? '');
        $passwordMatches = $storedPassword !== '' && (
            password_verify($password, $storedPassword)
            || hash_equals($storedPassword, $password)
            || hash_equals($storedPassword, hash('sha256', $password))
        );
        if (!$passwordMatches) {
            return false;
        }

        establish_user_session($user, true);
        return true;
    }

    $stmt = app_pdo()->prepare('SELECT id, username, email, dpto, contra_hash, isAdmin FROM users WHERE email = :email ORDER BY username ASC');
    $stmt->execute(['email' => $identifier]);
    $users = $stmt->fetchAll();

    foreach ($users as $candidate) {
        $storedPassword = (string) ($candidate['contra_hash'] ?? '');
        $passwordMatches = $storedPassword !== '' && (
            password_verify($password, $storedPassword)
            || hash_equals($storedPassword, $password)
            || hash_equals($storedPassword, hash('sha256', $password))
        );
        if ($passwordMatches) {
            establish_user_session($candidate, true);
            return true;
        }
    }

    return false;
}

function logout_user(): void
{
    clear_remember_token_from_db();
    clear_remember_cookie();

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}
