<?php
require_once __DIR__ . '/../config/database.php';

$username = 'admin';
$password = 'Admin@123';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$tableName = null;
$stmt = $pdo->query("SHOW TABLES LIKE 'admin_user%'");
$tables = $stmt->fetchAll(PDO::FETCH_NUM);

foreach ($tables as $row) {
    if (($row[0] ?? '') === 'admin_users') {
        $tableName = 'admin_users';
        break;
    }
    if (($row[0] ?? '') === 'admin_user') {
        $tableName = 'admin_user';
    }
}

if ($tableName === null) {
    echo "Khong tim thay bang admin_user/admin_users" . PHP_EOL;
    exit(1);
}

$check = $pdo->prepare("SELECT id FROM {$tableName} WHERE username = :username LIMIT 1");
$check->execute(['username' => $username]);
$existing = $check->fetch();

if ($existing) {
    $update = $pdo->prepare("UPDATE {$tableName} SET password_hash = :password_hash WHERE id = :id");
    $update->execute([
        'password_hash' => $passwordHash,
        'id' => (int)$existing['id'],
    ]);
    echo "Da cap nhat mat khau cho user admin trong bang {$tableName}" . PHP_EOL;
} else {
    $columnsStmt = $pdo->query("SHOW COLUMNS FROM {$tableName}");
    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('is_active', $columns, true)) {
        $insert = $pdo->prepare("INSERT INTO {$tableName} (username, password_hash, is_active) VALUES (:username, :password_hash, 1)");
    } else {
        $insert = $pdo->prepare("INSERT INTO {$tableName} (username, password_hash) VALUES (:username, :password_hash)");
    }

    $insert->execute([
        'username' => $username,
        'password_hash' => $passwordHash,
    ]);

    echo "Da tao user admin trong bang {$tableName}" . PHP_EOL;
}

echo "Username: {$username}" . PHP_EOL;
echo "Password: {$password}" . PHP_EOL;
