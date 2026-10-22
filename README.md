# Kịch bản Kiểm thử Hệ sinh thái Microservices trên Cloud (AWS EC2)

Tài liệu hướng dẫn thực nghiệm và trình diễn các đặc tính kỹ thuật cốt lõi của hệ thống Microservices (Scaling, Load Balancing, Fault Isolation, Volume Persistence, Self-Healing, Monitoring) trên môi trường Cloud VPS.

---

## 1. Thông tin môi trường triển khai

- **Public IP máy chủ EC2**: `13.220.188.139`
- **Cổng tiếp nhận (Nginx Gateway)**: `80`
- **Địa chỉ Web Khách hàng**: `http://13.220.188.139/index.html`
- **Địa chỉ Quản trị Đơn hàng**: `http://13.220.188.139/admin.html`
- **Đường dẫn thư mục dự án trên EC2**: `~/microservices-cloud-app`

---

## 2. Kịch bản 1: Mở rộng quy mô linh hoạt (Horizontal Scaling)

Chứng minh khả năng tăng giảm số lượng container Backend động mà không làm gián đoạn hệ thống.

- **Bước 1: Tăng số lượng instance của Product Service lên 3 container (Chạy trên Terminal EC2)**
```bash
cd ~/microservices-cloud-app
sudo docker compose up -d --scale product-service=3
sudo docker compose ps
```
*Cách xác minh:* Terminal xuất hiện 3 container `product-service-1`, `product-service-2`, `product-service-3` đều ở trạng thái `Up`.

- **Bước 2 (Khôi phục): Thu gọn về lại 1 container chuẩn ban đầu (Chạy trên Terminal EC2)**
```bash
sudo docker compose up -d --scale product-service=1 --remove-orphans
sudo docker compose ps
```

---

## 3. Kịch bản 2: Cân bằng tải qua Gateway (Load Balancing - Round-Robin)

Chứng minh Nginx Gateway tiếp nhận truy cập từ Internet và phân phối tải luân phiên cho các container Backend chạy ngầm.

- **Bước 1: Bật chế độ theo dõi log Nginx theo thời gian thực (Chạy trên Terminal EC2)**
```bash
sudo docker logs -f nginx_gateway
```

- **Bước 2: Gửi 100 requests liên tục vào Public IP Cloud (Chạy trên PowerShell máy tính)**
```powershell
1..100 | ForEach-Object { Invoke-RestMethod -Uri "[http://13.220.188.139/api/products](http://13.220.188.139/api/products)" -Method Get | Out-Null }
```
*Cách xác minh:* Quan sát màn hình Terminal EC2, log của `nginx_gateway` sẽ nhảy liên tục các bản ghi `GET /api/products HTTP/1.1 200`.

- **Bước 3:** Nhấn `Ctrl + C` trên Terminal EC2 để dừng theo dõi log.

---

## 4. Kịch bản 3: Cô lập sự cố (Fault Isolation)

Chứng minh kiến trúc Microservices phân tán giúp hệ thống không bị "chết chùm" khi một dịch vụ thành phần gặp sự cố.

- **Bước 1: Ép dừng hoạt động dịch vụ Product Service (Chạy trên Terminal EC2)**
```bash
sudo docker stop microservices-cloud-app-product-service-1
```

- **Bước 2: Kiểm tra tính sẵn sàng của các dịch vụ còn lại (Chạy trên Trình duyệt máy tính)**
1. Mở trang quản trị: `http://13.220.188.139/admin.html` $\rightarrow$ Nhấn `Ctrl + F5` (Dịch vụ Order vẫn tải đủ danh sách đơn hàng).
2. Kiểm tra trực tiếp API User: `http://13.220.188.139/api/users/1` $\rightarrow$ Trình duyệt vẫn trả về JSON dữ liệu người dùng bình thường.

- **Bước 3 (Khôi phục): Kích hoạt lại Product Service (Chạy trên Terminal EC2)**
```bash
sudo docker compose up -d product-service
sudo docker compose ps
```

---

## 5. Kịch bản 4: Lưu trữ bền vững dữ liệu (Docker Volume Persistence)

Chứng minh vòng đời của dữ liệu độc lập với vòng đời của Container: xóa bỏ hoàn toàn container cơ sở dữ liệu thì dữ liệu trên Cloud vẫn nguyên vẹn 100%.

- **Bước 1: Xóa cưỡng bức container cơ sở dữ liệu đơn hàng (Chạy trên Terminal EC2)**
```bash
sudo docker rm -f db_order_mysql
```

- **Bước 2: Kiểm tra trạng thái lỗi kết nối (Chạy trên Trình duyệt máy tính)**
Truy cập `http://13.220.188.139/admin.html` và nhấn `Ctrl + F5` $\rightarrow$ Trang web báo lỗi mất kết nối cơ sở dữ liệu (chứng minh container đã bị xóa thật).

- **Bước 3: Dựng lại container mới từ Docker Volume đã có (Chạy trên Terminal EC2)**
```bash
sudo docker compose up -d order-db
```

- **Bước 4: Kiểm tra phục hồi dữ liệu (Chạy trên Trình duyệt máy tính)**
Chờ 10 giây cho MySQL khởi động, quay lại `http://13.220.188.139/admin.html` nhấn `Ctrl + F5`.
*Cách xác minh:* Toàn bộ 17 đơn hàng hiển thị đầy đủ nguyên vẹn mà không cần nạp lại file SQL.

---

## 6. Kịch bản 5: Khả năng tự phục hồi (Self-Healing & Auto-Restart)

Chứng minh chính sách `restart: always` giúp hệ thống tự động hồi sinh các dịch vụ bị crash đột ngột mà không cần quản trị viên can thiệp.

- **Bước 1: Ép tắt đột ngột dịch vụ Order (Chạy trên Terminal EC2)**
```bash
sudo docker kill app_order_service
```

- **Bước 2: Kiểm tra Docker tự khởi động lại tiến trình (Chạy trên Terminal EC2)**
```bash
sudo docker ps -f name=app_order_service
```
*Cách xác minh:* Cột `STATUS` hiển thị `Up Less than a second` hoặc `Up 2 seconds` (container đã được tự động bật lại).

---

## 7. Kịch bản 6: Giám sát tài nguyên thời gian thực (Resource Monitoring)

Theo dõi mức tiêu thụ tài nguyên phần cứng (CPU, RAM, Lưu lượng mạng) của từng microservice khi có tải truy cập.

- **Bước 1: Mở bảng giám sát tài nguyên động (Chạy trên Terminal EC2)**
```bash
sudo docker stats
```

- **Bước 2: Bắn tải để quan sát biến động CPU/RAM (Chạy trên PowerShell máy tính)**
```powershell
1..200 | ForEach-Object { Invoke-RestMethod -Uri "[http://13.220.188.139/index.html](http://13.220.188.139/index.html)" -Method Get | Out-Null }
```
*Cách xác minh:* Cột `% CPU` và `NET I/O` của `nginx_gateway` và `web_frontend` trên bảng stats sẽ tăng tương ứng theo từng đợt gửi request.

- **Bước 3:** Nhấn `Ctrl + C` trên Terminal EC2 để thoát bảng giám sát.

---

## 8. Lệnh dọn dẹp và khôi phục trạng thái chuẩn

Sau khi thực hiện xong các kịch bản thử nghiệm, chạy lệnh sau trên Terminal EC2 để đưa toàn bộ hệ thống về cụm 8 container nguyên bản:
```bash
cd ~/microservices-cloud-app
sudo docker compose up -d --scale product-service=1 --remove-orphans
sudo docker restart nginx_gateway web_frontend
sudo docker compose ps
```
