CREATE DATABASE IF NOT EXISTS product_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE product_db;

DROP TABLE IF EXISTS products;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    image LONGTEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO products (id, name, description, price, stock, image) VALUES
(1, 'Giày chạy bộ Nike Air Zoom', 'Giày thể thao chạy bộ êm ái thoáng khí', 2500000.00, 50, NULL),
(2, 'Áo bóng đá CLB Real Madrid', 'Áo đấu thể thao chính hãng mùa giải 2026', 450000.00, 100, NULL),
(3, 'Quả bóng đá thi đấu FIFA', 'Bóng da cao cấp đạt tiêu chuẩn thi đấu quốc tế', 650000.00, 30, NULL),
(4, 'Vợt cầu lông Yonex Astrox 88D', 'Vợt công thủ toàn diện trợ lực carbon siêu nhẹ', 3200000.00, 25, NULL),
(5, 'Giày đá bóng sân cỏ nhân tạo Predator', 'Giày đinh TF bám sân kiểm soát bóng tối ưu', 1850000.00, 40, NULL),
(6, 'Thảm tập Yoga định tuyến TPE 8mm', 'Thảm tập thể dục thể thao chống trơn trượt', 350000.00, 60, NULL),
(7, 'Bộ tạ tay đa năng điều chỉnh 20kg', 'Tạ gang bọc cao su tập gym tại nhà', 1200000.00, 15, NULL),
(8, 'Bình nước thể thao Lock&Lock 800ml', 'Bình nước giữ nhiệt inox 304 có quai xách', 280000.00, 80, NULL),
(9, 'Túi trống thể thao Gym & Du lịch', 'Túi đựng đồ tập gym chống nước có ngăn để giày riêng', 490000.00, 45, NULL);
