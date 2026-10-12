<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    if (empty($input['user_id']) || empty($input['product_id']) || empty($input['quantity'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Thiếu thông tin đặt hàng", "data" => null]);
        exit;
    }

    $userId    = $input['user_id'];
    $productId = $input['product_id'];
    $quantity  = (int)$input['quantity'];

    // 1. Gọi sang User Service kiểm tra user có tồn tại không
    $userRes = @file_get_contents("http://user-service/$userId");
    if (!$userRes) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Tài khoản không tồn tại hoặc User Service gặp sự cố", "data" => null]);
        exit;
    }

    // 2. Gọi sang Product Service kiểm tra sản phẩm và số lượng tồn kho
    $prodRes = @file_get_contents("http://product-service/$productId");
    $prodData = json_decode($prodRes, true);
    if (!$prodData || $prodData['status'] !== 'success') {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Sản phẩm không tồn tại", "data" => null]);
        exit;
    }

    $product = $prodData['data'];
    if ($product['stock'] < $quantity) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Số lượng tồn kho không đủ", "data" => null]);
        exit;
    }

    // 3. Tính tổng tiền và lưu vào order_db (khớp cột status của bạn)
    $totalPrice = $product['price'] * $quantity;
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, product_id, quantity, total_price, status) VALUES (?, ?, ?, ?, 'COMPLETED')");
    $stmt->execute([$userId, $productId, $quantity, $totalPrice]);

    echo json_encode([
        "status" => "success",
        "message" => "Đặt hàng thành công",
        "data" => [
            "order_id"    => $pdo->lastInsertId(),
            "total_price" => $totalPrice,
            "status"      => "COMPLETED"
        ]
    ]);
    exit;
}

// Lấy danh sách lịch sử đơn hàng
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
    echo json_encode(["status" => "success", "message" => "Danh sách đơn hàng", "data" => $stmt->fetchAll()]);
    exit;
}

http_response_code(404);
echo json_encode(["status" => "error", "message" => "Endpoint không hợp lệ", "data" => null]);
