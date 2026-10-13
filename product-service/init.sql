CREATE DATABASE IF NOT EXISTS product_db;
USE product_db;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO products (name, description, price, stock) VALUES
('Giày chạy bộ Nike Air Zoom', 'Giày thể thao chạy bộ êm ái thoáng khí', 2500000, 50),
('Áo bóng đá CLB Real Madrid', 'Áo đấu thể thao chính hãng mùa giải 2026', 450000, 100),
('Quả bóng đá thi đấu FIFA', 'Bóng da cao cấp đạt tiêu chuẩn thi đấu', 650000, 20);
