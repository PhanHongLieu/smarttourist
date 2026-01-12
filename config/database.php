<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306'; // Sẽ lấy 4000 trên Render
$dbname = getenv('DB_NAME') ?: 'smarttourist';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    // Kết nối PDO với cổng linh hoạt
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // TiDB yêu cầu một số cấu hình để tương thích tốt nhất với MySQL
    $pdo->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    die("Lỗi kết nối TiDB: " . $e->getMessage());
}
?>