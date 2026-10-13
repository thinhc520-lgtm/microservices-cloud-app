# TÀI LIỆU THIẾT KẾ REST API - HỆ THỐNG MICROSERVICES

Hệ thống gồm 3 Microservices độc lập, truyền nhận dữ liệu qua định dạng JSON.

---

## 1. DỊCH VỤ NGƯỜI DÙNG (User Service - Port: 8001)

Quản lý tài khoản và xác thực người dùng.

### 1.1. Đăng ký tài khoản

* Endpoint: POST /api/users/register

* Request Body:

{
  "username": "thanhdang",
  "password": "123456",
  "email": "dang@example.com"
}

* Response 201 Created:

{
  "message": "Đăng ký tài khoản thành công",
  "user_id": 1
}

### 1.2. Đăng nhập

* Endpoint: POST /api/users/login

* Request Body:

{
  "username": "thanhdang",
  "password": "123456"
}

* Response 200 OK:

{
  "message": "Đăng nhập thành công",
  "user_id": 1,
  "token": "sample_jwt_token_123456"
}

### 1.3. Lấy thông tin người dùng theo ID

* Endpoint: GET /api/users/{id}

* Response 200 OK:

{
  "id": 1,
  "username": "thanhdang",
  "email": "dang@example.com",
  "created_at": "2026-09-22 10:00:00"
}

---

## 2. DỊCH VỤ SẢN PHẨM (Product Service - Port: 8002)

Quản lý danh mục sản phẩm và kho hàng.

### 2.1. Lấy danh sách tất cả sản phẩm

* Endpoint: GET /api/products

* Response 200 OK:

[
  {
    "id": 1,
    "name": "Sản phẩm A",
    "price": 100000,
    "stock": 10
  },
  {
    "id": 2,
    "name": "Sản phẩm B",
    "price": 200000,
    "stock": 50
  }
]

### 2.2. Xem chi tiết 1 sản phẩm

* Endpoint: GET /api/products/{id}

* Response 200 OK:

{
  "id": 1,
  "name": "Sản phẩm A",
  "description": "Mô tả chi tiết sản phẩm A",
  "price": 100000,
  "stock": 10
}

### 2.3. Thêm sản phẩm mới

* Endpoint: POST /api/products

* Request Body:

{
  "name": "Sản phẩm mới",
  "description": "Mô tả sản phẩm mới",
  "price": 150000,
  "stock": 20
}

* Response 201 Created:

{
  "message": "Thêm sản phẩm thành công",
  "product_id": 3
}

---

## 3. DỊCH VỤ ĐƠN HÀNG (Order Service - Port: 8003)

Quản lý đặt hàng và lịch sử giao dịch.

### 3.1. Tạo đơn hàng mới

* Endpoint: POST /api/orders

* Request Body:

{
  "user_id": 1,
  "product_id": 1,
  "quantity": 2,
  "total_price": 200000
}

* Response 201 Created:

{
  "message": "Tạo đơn hàng thành công",
  "order_id": 101,
  "status": "PENDING"
}

### 3.2. Xem danh sách đơn hàng của một người dùng

* Endpoint: GET /api/orders/user/{user_id}

* Response 200 OK:

[
  {
    "id": 101,
    "product_id": 1,
    "quantity": 2,
    "total_price": 200000,
    "status": "PENDING",
    "created_at": "2026-09-22 10:30:00"
  }
]