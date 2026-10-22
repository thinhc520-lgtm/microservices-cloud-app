Microservices Cloud Application - Sports Shop
Ứng dụng thương mại điện tử Cửa hàng Thể thao xây dựng theo kiến trúc Microservices, phân rã theo nguyên tắc Database-per-Service, đóng gói bằng Docker và điều phối tập trung qua Docker Compose trên môi trường Cloud.

1. Kiến trúc hệ thống
Nginx API Gateway (nginx_gateway): Cổng tiếp nhận duy nhất tại port 80, đóng vai trò Reverse Proxy và cân bằng tải Round-Robin.

Web Frontend (web_frontend): Giao diện người dùng Web tĩnh (HTML/CSS/JS).

User Service (app_user_service): RESTful API quản lý tài khoản, mã hóa Bcrypt (db_user_mysql).

Product Service (app_product_service): RESTful API quản lý danh mục và tồn kho (db_product_mysql).

Order Service (app_order_service): RESTful API xử lý giao dịch đơn hàng liên dịch vụ (db_order_mysql).

Docker Network (app-network): Mạng ảo Bridge cách ly nội bộ, tự động phân giải tên miền (Embedded DNS).

Docker Volumes (db_*_data): Lưu trữ dữ liệu MySQL bền vững trên ổ đĩa máy chủ.

2. Hướng dẫn cài đặt và vận hành
Khởi động toàn bộ cụm 8 Container
Bash
docker compose up -d --build
Kiểm tra trạng thái các Container
Bash
docker compose ps
Theo dõi nhật ký hệ thống (Logs)
Bash
# Xem log toàn bộ hệ thống
docker compose logs -f

# Xem riêng log của Nginx Gateway
docker logs -f nginx_gateway
3. Các kịch bản kiểm thử thực nghiệm
Kịch bản 1: Mở rộng quy mô (Horizontal Scaling)
Bước 1: Tăng số lượng instance của Product Service lên 3 container

Bash
docker compose up -d --scale product-service=3
docker compose ps
Bước 2 (Khôi phục): Thu gọn về 1 container ban đầu

Bash
docker compose up -d --scale product-service=1 --remove-orphans
docker compose ps
Kịch bản 2: Cân bằng tải (Load Balancing - Round-Robin)
Bước 1: Bắn 300 requests liên tục để thử tảiChạy trên Windows PowerShell:

PowerShell
1..300 | ForEach-Object { Invoke-RestMethod -Uri "http://localhost/api/products" -Method Get | Out-Null }
Hoặc chạy trên Linux / Cloud VPS (Apache Benchmark):

Bash
ab -n 1000 -c 10 http://localhost/api/products
Bước 2: Kiểm tra tỷ lệ chia đều tải trên log Nginx

Bash
docker logs -f nginx_gateway
Bước 3 (Khôi phục): Đưa container về chuẩn và làm mới Gateway

Bash
docker compose up -d --scale product-service=1 --remove-orphans
docker restart nginx_gateway
docker compose ps
Kịch bản 3: Cô lập sự cố (Fault Isolation)
Bước 1: Giả lập sự cố làm sập container Product Service

Bash
docker stop app_product_service
Bước 2: Kiểm tra User Service và Web vẫn hoạt động bình thường

Bash
curl -I http://localhost/api/users/1
Bước 3 (Khôi phục): Kích hoạt lại container Product Service

Bash
docker compose up -d product-service
docker compose ps
Kịch bản 4: Lưu trữ bền vững (Docker Volume Persistence)
Bước 1: Dừng và xóa cưỡng bức 2 container cơ sở dữ liệu

Bash
docker compose stop user-db order-db
docker compose rm -f user-db order-db
Bước 2 (Khôi phục): Dựng lại container CSDL mới từ Docker Volume

Bash
docker compose up -d user-db order-db
Bước 3: Kiểm tra dữ liệu cũ vẫn còn nguyên vẹn 100%

Bash
curl http://localhost/api/users/1
curl http://localhost/api/orders/1
docker compose ps
Kịch bản 5: Khả năng tự phục hồi (Self-Healing & Auto-Restart)
Bước 1: Ép tắt cưỡng bức tiến trình của một dịch vụ (giả lập crash đột ngột)

Bash
docker kill app_order_service
Bước 2: Theo dõi Docker tự động hồi sinh container trong 1-2 giây

Bash
docker ps -f name=app_order_service
Bước 3: Kiểm tra API của dịch vụ vẫn thông suốt

Bash
curl -I http://localhost/api/orders/1
Kịch bản 6: Giám sát tài nguyên thời gian thực (Resource Monitoring)
Bước 1: Bật màn hình giám sát động các container

Bash
docker stats --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}"
Bước 2: Bắn tải thử nghiệm để quan sát biến động CPU/RAM trên bảng statsMở một tab terminal khác và chạy:

Bash
for i in {1..200}; do curl -s http://localhost/api/products > /dev/null; done
Bước 3: Nhấn Ctrl + C để thoát màn hình giám sát.

4. Lệnh khôi phục tổng thể toàn hệ thống
Lệnh dọn dẹp chung sau khi kiểm thử xong, đưa toàn bộ hệ sinh thái về cấu hình 8 container chuẩn ban đầu:

Bash
docker compose up -d --scale product-service=1 --remove-orphans
docker restart nginx_gateway
docker compose ps
