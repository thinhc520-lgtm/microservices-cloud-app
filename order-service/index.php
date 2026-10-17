<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    // 1. Tự động nhận diện cả 2 định dạng:
    // - Dạng trực tiếp từ Postman: { user_id, product_id, quantity }
    // - Dạng lồng mảng từ Frontend của Thịnh: { user_id, items: [{ product_id, quantity }] }
    $userId    = $input['user_id'] ?? null;
    $productId = $input['product_id'] ?? ($input['items'][0]['product_id'] ?? null);
    $quantity  = $input['quantity'] ?? ($input['items'][0]['quantity'] ?? 1);

    if (empty($userId) || empty($productId) || empty($quantity)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Thiếu thông tin đặt hàng", "data" => null]);
        exit;
    }

    $userId    = (int)$userId;
    $productId = (int)$productId;
    $quantity  = (int)$quantity;

    // 2. Gọi sang User Service kiểm tra user (thêm fallback index.php tránh lỗi rewrite Apache)
    $userRes = @file_get_contents("http://user-service/index.php/$userId");
    if (!$userRes) {
        $userRes = @file_get_contents("http://user-service/$userId");
    }
    if (!$userRes) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Tài khoản không tồn tại hoặc User Service gặp sự cố", "data" => null]);
        exit;
    }

    // 3. Gọi sang Product Service kiểm tra sản phẩm và tồn kho
    $prodRes = @file_get_contents("http://product-service/index.php/$productId");
    if (!$prodRes) {
        $prodRes = @file_get_contents("http://product-service/$productId");
    }
    $prodData = json_decode($prodRes, true);
    if (!$prodData || $prodData['status'] !== 'success' || empty($prodData['data'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Sản phẩm không tồn tại", "data" => null]);
        exit;
    }

    $product = $prodData['data'];
    if ($product['stock'] < $quantity) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Số lượng tồn kho không đủ (Hiện còn: " . $product['stock'] . ")", "data" => null]);
        exit;
    }

    // 4. Tính tổng tiền và lưu vào order_db
    $totalPrice = $product['price'] * $quantity;
    try {
        $status = $input['status'] ?? 'PENDING';
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, product_id, quantity, total_price, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $productId, $quantity, $totalPrice, $status]);
        $orderId = $pdo->lastInsertId();

        // 5. Gọi sang Product Service để cập nhật giảm tồn kho
        $reducePayload = json_encode([
            "product_id" => $productId,
            "quantity"   => $quantity
        ]);
        $streamOptions = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => $reducePayload,
                'timeout' => 5
            ]
        ];
        $streamContext = stream_context_create($streamOptions);
        $reduceRes = @file_get_contents("http://product-service/index.php/reduce-stock", false, $streamContext);
        if (!$reduceRes) {
            @file_get_contents("http://product-service/reduce-stock", false, $streamContext);
        }

        echo json_encode([
            "status"  => "success",
            "message" => "Đặt hàng thành công",
            "data"    => [
                "order_id"    => $orderId,
                "total_price" => $totalPrice,
                "status"      => $status
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Lỗi lưu đơn hàng: " . $e->getMessage(), "data" => null]);
    }
    exit;
}

// Lấy danh sách lịch sử đơn hàng
if ($method === 'GET') {
    $uriOrderId = null;
    if (preg_match('#/([0-9]+)$#', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $matches)) {
        $uriOrderId = (int)$matches[1];
    }
    if ($uriOrderId) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$uriOrderId]);
        $order = $stmt->fetch();
        if ($order) {
            echo json_encode(["status" => "success", "message" => "Chi tiết đơn hàng", "data" => $order]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Không tìm thấy đơn hàng", "data" => null]);
        }
        exit;
    }

    $stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
    echo json_encode(["status" => "success", "message" => "Danh sách đơn hàng", "data" => $stmt->fetchAll()]);
    exit;
}

// Cập nhật trạng thái đơn hàng (PUT /api/orders hoặc /api/orders/{id})
if ($method === 'PUT') {
    $uriOrderId = null;
    if (preg_match('#/([0-9]+)$#', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $matches)) {
        $uriOrderId = (int)$matches[1];
    }
    $orderId = (int)($input['order_id'] ?? ($input['id'] ?? $uriOrderId));
    $newStatus = trim($input['status'] ?? '');

    if (empty($orderId) || empty($newStatus)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Thiếu order_id hoặc status", "data" => null]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        echo json_encode([
            "status"  => "success",
            "message" => "Cập nhật trạng thái đơn hàng thành công",
            "data"    => ["order_id" => $orderId, "status" => $newStatus]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Lỗi cập nhật: " . $e->getMessage(), "data" => null]);
    }
    exit;
}

http_response_code(404);
echo json_encode(["status" => "error", "message" => "Endpoint không hợp lệ", "data" => null]);
