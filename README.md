Microservices Cloud Application - Sports ShopỨng dụng thương mại điện tử Cửa hàng Thể thao xây dựng theo kiến trúc Microservices, phân rã theo nguyên tắc Database-per-Service, đóng gói bằng Docker và điều phối tập trung qua Docker Compose trên máy chủ Cloud.   1. Kiến trúc hệ thốngNginx API Gateway (nginx_gateway): Điểm tiếp nhận duy nhất tại cổng 80, đóng vai trò Reverse Proxy định tuyến API và cân bằng tải Round-Robin.   Web Frontend (web_frontend): Giao diện người dùng web tĩnh (HTML/CSS/JS).   User Service (app_user_service): RESTful API quản lý tài khoản, mã hóa mật khẩu Bcrypt. Đi kèm cơ sở dữ liệu db_user_mysql (user_db).   Product Service (app_product_service): RESTful API quản lý danh mục và tồn kho sản phẩm thể thao. Đi kèm cơ sở dữ liệu db_product_mysql (product_db).   Order Service (app_order_service): RESTful API xử lý giao dịch đặt hàng liên dịch vụ (xác thực User $\rightarrow$ kiểm tra Product $\rightarrow$ trừ tồn kho). Đi kèm cơ sở dữ liệu db_order_mysql (order_db).   Docker Network (app-network): Mạng ảo Bridge cách ly nội bộ, cho phép các container giao tiếp qua Service Name.   Docker Volumes (db_*_data): Lưu trữ bền vững dữ liệu MySQL trên ổ cứng máy chủ.   2. Hướng dẫn cài đặt và vận hànhKhởi động toàn bộ cụm 8 ContainerBashdocker compose up -d --build
   Kiểm tra trạng thái hệ thốngBashdocker compose ps
   Theo dõi nhật ký (Logs)Bash# Xem log toàn bộ hệ thống
docker compose logs -f

# Xem log riêng của Nginx Gateway
docker logs -f nginx_gateway
   3. Các kịch bản kiểm thử thực nghiệm (Terminal Commands)Kịch bản 1: Kiểm thử mở rộng quy mô (Horizontal Scaling)Nhân bản product-service từ 1 lên 3 container để nâng cao năng lực chịu tải:   Bash# Thực hiện scale lên 3 container:
docker compose up -d --scale product-service=3
docker compose ps

# LỆNH KHÔI PHỤC: Thu gọn về 1 container ban đầu
docker compose up -d --scale product-service=1 --remove-orphans
docker compose ps
Kịch bản 2: Kiểm thử Cân bằng tải (Load Balancing - Round-Robin)Gửi liên tiếp lưu lượng tải vào API Gateway để kiểm tra Nginx phân phối luân phiên ~33.3% tải cho mỗi container:   Trên Windows PowerShell:PowerShell1..300 | ForEach-Object { 
    Invoke-RestMethod -Uri "http://localhost/api/products" -Method Get | Out-Null 
}
Trên Linux / Cloud VPS (Dùng Apache Benchmark):Bashab -n 1000 -c 10 http://localhost/api/products
   Kiểm tra luồng chia tải trên log Nginx:Bashdocker logs -f nginx_gateway
   LỆNH KHÔI PHỤC: Khởi động lại Nginx và đưa container về chuẩnBashdocker compose up -d --scale product-service=1 --remove-orphans
docker restart nginx_gateway
docker compose ps
Kịch bản 3: Kiểm thử Cô lập sự cố (Fault Isolation)Mô phỏng sự cố dừng đột ngột product-service để kiểm chứng hệ thống không bị sụp đổ dây chuyền (User Service và Web vẫn chạy bình thường):   Bash# 1. Dừng container sản phẩm:
docker stop app_product_service

# 2. Kiểm tra: User Service vẫn hoạt động bình thường
curl -I http://localhost/api/users/1

# 3. LỆNH KHÔI PHỤC: Kích hoạt lại container sản phẩm
docker compose up -d product-service
docker compose ps
   Kịch bản 4: Kiểm thử Lưu trữ bền vững (Docker Volume)Xóa bỏ cưỡng bức các container CSDL để xác minh dữ liệu không bị mất sau khi khởi tạo lại:   Bash# 1. Dừng và xóa 2 container Database:
docker compose stop user-db order-db
docker compose rm -f user-db order-db

# 2. LỆNH KHÔI PHỤC: Tạo lại container CSDL từ Docker Volume đã lưu
docker compose up -d user-db order-db

# 3. Kiểm tra dữ liệu cũ vẫn bảo toàn nguyên vẹn:
curl http://localhost/api/users/1
curl http://localhost/api/orders/1
docker compose ps
4. Lệnh khôi phục tổng thể toàn hệ thốngLệnh dọn dẹp chung sau khi hoàn thành toàn bộ quá trình kiểm thử để hệ thống sẵn sàng triển khai:   Bashdocker compose up -d --scale product-service=1 --remove-orphans
docker restart nginx_gateway
docker compose ps
