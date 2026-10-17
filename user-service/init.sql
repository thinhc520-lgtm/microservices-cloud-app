CREATE DATABASE IF NOT EXISTS user_db;
USE user_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role VARCHAR(50) DEFAULT 'USER',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, email, role) VALUES
('admin', '$2y$10$Yis7XSHix7/7964NAuihGu.L.cWPMmmnHP5bvkF4iomXG6sZ/IZIy', 'admin123@gmail.com', 'ADMIN'),
('thinhz', '$2y$10$dfMwHoEqKdtYtgRr/xfOaOWDkbI6z4yPghT.2Y6gu/4UmtKp2cLW6', 'thinhz@gmail.com', 'USER')
ON DUPLICATE KEY UPDATE 
    password = VALUES(password),
    email = VALUES(email),
    role = VALUES(role);
