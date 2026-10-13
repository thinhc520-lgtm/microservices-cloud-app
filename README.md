# Microservices Cloud Application - Sports Shop

Dự án ứng dụng kiến trúc Microservices phục vụ môn học Điện toán đám mây. Hệ thống gồm các dịch vụ độc lập quản lý người dùng, sản phẩm, đơn hàng và giao diện được điều phối qua Nginx Reverse Proxy.

## 1. Kiến trúc hệ thống

Hệ thống bao gồm các container:

- **nginx_gateway**: Reverse Proxy / API Gateway điều hướng request cổng 80.
- **web_frontend**: Giao diện Web Client (HTML/JS).
- **user-service**: REST API quản lý tài khoản & phân quyền (kết nối `db_user_mysql`).
- **product-service**: REST API quản lý danh mục sản phẩm (kết nối `db_product_mysql`).
- **order-service**: REST API xử lý đặt hàng (kết nối `db_order_mysql`).
- **3 MySQL Databases**: Lưu trữ độc lập cho từng dịch vụ với Docker Volume bảo toàn dữ liệu.

## 2. Hướng dẫn vận hành

### Khởi động hệ thống

```bash
docker compose up -d --build
```
