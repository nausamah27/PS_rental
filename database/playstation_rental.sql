-- PlayStation Rental Database
CREATE DATABASE IF NOT EXISTS playstation_rental;
USE playstation_rental;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Consoles table
CREATE TABLE consoles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    price_per_day DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
    image VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rentals table
CREATE TABLE rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    console_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (console_id) REFERENCES consoles(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin112)
INSERT INTO users (username, password, name, email, phone, address, role) VALUES
('admin', '$2y$10$krRdAbShrUp19wrOpUEeov9NITKsPXG34HqxK7lQ3X1vnXfn5K', 'Administrator', 'admin@playstation.com', '081234567890', 'Admin Address', 'admin');

-- Insert sample consoles
INSERT INTO consoles (name, type, description, price_per_day, image) VALUES
('PlayStation 5 Standard', 'PS5', 'Latest PlayStation 5 with 4K gaming and ultra-fast SSD', 50000.00, 'ps5-standard.jpg'),
('PlayStation 5 Digital', 'PS5', 'PlayStation 5 Digital Edition without disc drive', 45000.00, 'ps5-digital.jpg'),
('PlayStation 4 Pro', 'PS4', 'PlayStation 4 Pro with enhanced performance', 35000.00, 'ps4-pro.jpg'),
('PlayStation 4 Slim', 'PS4', 'Compact PlayStation 4 Slim console', 30000.00, 'ps4-slim.jpg'),
('PlayStation 3 Super Slim', 'PS3', 'Classic PlayStation 3 with backward compatibility', 25000.00, 'ps3-slim.jpg'),
('PlayStation 2 Slim', 'PS2', 'Retro PlayStation 2 for classic gaming', 20000.00, 'ps2-slim.jpg');
