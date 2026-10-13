<?php
$host = getenv('DB_HOST') ?: 'user-db';
$db   = getenv('DB_NAME') ?: 'user_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    // Trả về lỗi chi tiết từ MySQL để dễ debug
    echo json_encode([
        "status" => "error", 
        "message" => "Lỗi kết nối CSDL: " . $e->getMessage(), 
        "data" => null
    ]);
    exit;
}
