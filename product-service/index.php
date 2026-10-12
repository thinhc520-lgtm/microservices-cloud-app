<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($method === 'GET') {
    preg_match('/\/([0-9]+)$/', $path, $matches);
    // 1. Xem chi tiết 1 sản phẩm
    if (!empty($matches[1])) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$matches[1]]);
        $product = $stmt->fetch();
        if ($product) {
            echo json_encode(["status" => "success", "message" => "Lấy thông tin sản phẩm thành công", "data" => $product]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Sản phẩm không tồn tại", "data" => null]);
        }
        exit;
    }

    // 2. Lấy toàn bộ danh sách sản phẩm
    $stmt = $pdo->query("SELECT * FROM products");
    $products = $stmt->fetchAll();
    echo json_encode(["status" => "success", "message" => "Danh sách sản phẩm", "data" => $products]);
    exit;
}

http_response_code(404);
echo json_encode(["status" => "error", "message" => "Endpoint không hợp lệ", "data" => null]);
