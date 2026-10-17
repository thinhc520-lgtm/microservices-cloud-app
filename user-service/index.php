<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$input  = json_decode(file_get_contents('php://input'), true);

// 1. Đăng ký tài khoản (khớp cột username, email, password)
if ($method === 'POST' && strpos($path, 'register') !== false) {
    if (empty($input['username']) || empty($input['email']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Vui lòng nhập đầy đủ username, email và password", "data" => null]);
        exit;
    }

    $hashPassword = password_hash($input['password'], PASSWORD_BCRYPT);
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$input['username'], $input['email'], $hashPassword]);
        echo json_encode(["status" => "success", "message" => "Đăng ký thành công", "data" => ["id" => $pdo->lastInsertId()]]);
    } catch (PDOException $e) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Username hoặc Email đã tồn tại", "data" => null]);
    }
    exit;
}

// 2. Đăng nhập tài khoản
if ($method === 'POST' && strpos($path, 'login') !== false) {
    $identifier = $input['username'] ?? $input['email'] ?? '';
    if (empty($identifier) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Vui lòng nhập tài khoản và mật khẩu", "data" => null]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($input['password'], $user['password'])) {
        unset($user['password']);

        // Kiểm tra thêm trạng thái của tài khoản
        $statusStr = strtolower(trim((string)($user['status'] ?? 'active')));
        if (isset($user['status']) && ($user['status'] == 0 || $statusStr === 'locked' || $statusStr === 'inactive' || $statusStr === 'blocked')) {
            http_response_code(403);
            echo json_encode([
                "status" => "error", 
                "message" => "Tài khoản của bạn đã bị khóa bởi Quản trị viên. Vui lòng liên hệ hỗ trợ!", 
                "data" => null
            ]);
            exit;
        }

        // Chỉ khi tài khoản có trạng thái HOẠT ĐỘNG (status == 1 hoặc status === 'ACTIVE') mới cấp quyền đăng nhập thành công
        if (isset($user['status']) && !($user['status'] == 1 || strtoupper($user['status']) === 'ACTIVE')) {
            http_response_code(403);
            echo json_encode([
                "status" => "error", 
                "message" => "Tài khoản của bạn đã bị khóa bởi Quản trị viên. Vui lòng liên hệ hỗ trợ!", 
                "data" => null
            ]);
            exit;
        }

        echo json_encode(["status" => "success", "message" => "Đăng nhập thành công", "data" => $user]);
    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Sai thông tin đăng nhập", "data" => null]);
    }
    exit;
}

// 3. API lấy danh sách người dùng hoặc lấy User theo ID
if ($method === 'GET') {
    preg_match('/\/([0-9]+)$/', $path, $matches);
    if (!empty($matches[1])) {
        $stmt = $pdo->prepare("SELECT id, username, email, role, status, created_at FROM users WHERE id = ?");
        $stmt->execute([$matches[1]]);
        $user = $stmt->fetch();
        if ($user) {
            echo json_encode(["status" => "success", "message" => "Tìm thấy người dùng", "data" => $user]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Không tìm thấy người dùng", "data" => null]);
        }
        exit;
    }

    $stmt = $pdo->query("SELECT id, username, email, role, status, created_at FROM users ORDER BY id ASC");
    echo json_encode(["status" => "success", "message" => "Danh sách người dùng", "data" => $stmt->fetchAll()]);
    exit;
}

// 4. API cập nhật trạng thái người dùng (PUT/PATCH/POST /api/users/{id})
if (($method === 'PUT' || $method === 'PATCH' || $method === 'POST') && preg_match('/\/([0-9]+)/', $path, $matches)) {
    $userId = (int)$matches[1];
    $newStatus = $input['status'] ?? null;
    if ($newStatus !== null) {
        $statusStr = strtolower(trim((string)$newStatus));
        $dbStatus = ($statusStr === 'active' || $newStatus == 1) ? 'ACTIVE' : 'BLOCKED';
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$dbStatus, $userId]);
        echo json_encode([
            "status" => "success", 
            "message" => "Cập nhật trạng thái người dùng thành công", 
            "data" => ["id" => $userId, "status" => $dbStatus]
        ]);
        exit;
    }
}

// 5. API xóa người dùng (DELETE /api/users/{id})
if ($method === 'DELETE' && preg_match('/\/([0-9]+)$/', $path, $matches)) {
    $userId = (int)$matches[1];

    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "ID tài khoản không hợp lệ", "data" => null]);
        exit;
    }

    // Kiểm tra tài khoản có tồn tại không
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Không tìm thấy người dùng", "data" => null]);
        exit;
    }

    // Không cho phép xóa tài khoản admin (id = 1 hoặc role = 'ADMIN')
    if ($userId === 1 || strtoupper(trim((string)$user['role'])) === 'ADMIN' || strtolower(trim((string)$user['username'])) === 'admin') {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Không được phép xóa tài khoản Quản trị viên (ADMIN)!", "data" => null]);
        exit;
    }

    try {
        $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delStmt->execute([$userId]);
        echo json_encode([
            "status"  => "success",
            "message" => "Xóa tài khoản thành công!",
            "data"    => ["id" => $userId, "username" => $user['username']]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Lỗi xóa tài khoản: " . $e->getMessage(), "data" => null]);
    }
    exit;
}

http_response_code(404);
echo json_encode(["status" => "error", "message" => "Endpoint không hợp lệ", "data" => null]);
