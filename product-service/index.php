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

// 3. Giảm số lượng tồn kho (API nội bộ cho Order Service gọi khi đặt hàng)
if (($method === 'POST' || $method === 'PUT') && (strpos($path, 'reduce-stock') !== false || strpos($path, 'stock') !== false)) {
    $input = json_decode(file_get_contents('php://input'), true);
    preg_match('/\/([0-9]+)/', $path, $matches);
    $productId = $input['product_id'] ?? ($matches[1] ?? null);
    $quantity  = (int)($input['quantity'] ?? 0);

    if (empty($productId) || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Thiếu ID sản phẩm hoặc số lượng trừ kho không hợp lệ", "data" => null]);
        exit;
    }

    $productId = (int)$productId;

    // Trừ kho an toàn bằng câu lệnh UPDATE có điều kiện stock >= quantity
    $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
    $stmt->execute([$quantity, $productId, $quantity]);

    if ($stmt->rowCount() > 0) {
        $getStmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $getStmt->execute([$productId]);
        $product = $getStmt->fetch();

        echo json_encode([
            "status" => "success",
            "message" => "Trừ tồn kho thành công",
            "data" => $product
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Không thể trừ kho: Tồn kho không đủ hoặc sản phẩm không tồn tại",
            "data" => null
        ]);
    }
    exit;
}

http_response_code(404);
echo json_encode(["status" => "error", "message" => "Endpoint không hợp lệ", "data" => null]);
