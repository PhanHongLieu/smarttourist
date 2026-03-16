<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function adminEnsureUsersTable(): void
{
    global $pdo;

    $sql = "
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($sql);
}

function adminHasAnyUser(): bool
{
    global $pdo;
    adminEnsureUsersTable();

    $stmt = $pdo->query('SELECT COUNT(*) FROM admin_users');
    return (int)$stmt->fetchColumn() > 0;
}

function adminCreateUser(string $username, string $password): bool
{
    global $pdo;
    adminEnsureUsersTable();

    $username = trim($username);
    if ($username === '' || $password === '') {
        return false;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    if ((int)$stmt->fetchColumn() > 0) {
        return false;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $pdo->prepare('INSERT INTO admin_users (username, password_hash, is_active) VALUES (:username, :password_hash, 1)');
    return $insert->execute([
        'username' => $username,
        'password_hash' => $hash,
    ]);
}

function adminIsLoggedIn(): bool
{
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function adminLogin(string $username, string $password): bool
{
    global $pdo;
    adminEnsureUsersTable();

    $stmt = $pdo->prepare('SELECT id, username, password_hash, is_active FROM admin_users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && (int)$user['is_active'] === 1 && password_verify($password, (string)$user['password_hash'])) {
        if (password_needs_rehash((string)$user['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE admin_users SET password_hash = :password_hash WHERE id = :id');
            $update->execute([
                'password_hash' => $newHash,
                'id' => (int)$user['id'],
            ]);
        }

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = (int)$user['id'];
        $_SESSION['admin_username'] = (string)$user['username'];
        session_regenerate_id(true);
        return true;
    }

    return false;
}

function adminRequireLogin(): void
{
    if (!adminIsLoggedIn()) {
        $next = $_SERVER['REQUEST_URI'] ?? 'tours.php';
        header('Location: login.php?next=' . urlencode($next));
        exit;
    }
}

function adminLogout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
