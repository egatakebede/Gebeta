-- Gebeta Database Schema and Sample Data

CREATE DATABASE IF NOT EXISTS gebeta DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gebeta;

DROP TABLE IF EXISTS otps;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS restaurants;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'restaurant', 'admin') DEFAULT 'customer',
    status ENUM('active', 'suspended') DEFAULT 'active',
    latitude DECIMAL(10,7) DEFAULT NULL,
    longitude DECIMAL(10,7) DEFAULT NULL,
    location_name VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE restaurants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    cuisine_type VARCHAR(200),
    location VARCHAR(200) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    opening_time TIME,
    closing_time TIME,
    image VARCHAR(255),
    rating DECIMAL(2,1) DEFAULT 0.0,
    status ENUM('pending', 'active', 'suspended') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

CREATE TABLE menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    is_available BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    delivery_address TEXT NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'telebirr', 'mpesa', 'wallet') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
);

CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    rating INT CHECK(rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

CREATE TABLE otps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    code VARCHAR(6) NOT NULL,
    purpose ENUM('register', 'login') NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_purpose (email, purpose)
);

CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100),
    payment_proof VARCHAR(255),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

INSERT INTO users (name, email, phone, password, role, status) VALUES
('Admin User', 'admin@gebeta.com', '+251911000001', '$2y$10$Z2nVTfsIoG5oDsJN8vnwQOtqDDwqUD6dlfFxzFqqOGbXPY8CaX/ai', 'admin', 'active'),
('Yod Restaurant', 'yod@restaurant.com', '+251911000002', '$2y$10$eNDh14nZSQl7qKXnfBnoQezl8rVlKqxlj9grohwTkQaihcs87sZFC', 'restaurant', 'active'),
('Test Customer', 'customer@test.com', '+251911000003', '$2y$10$iTE5bq9RdPVCtv/N1u0QluUZdvuLFXakYIpsJYkz3l82XX/n4sUKm', 'customer', 'active');

INSERT INTO restaurants (user_id, name, description, cuisine_type, location, phone, opening_time, closing_time, rating, status) VALUES
(2, 'Yod Abyssinia', 'Authentic Ethiopian food with traditional recipes.', 'Ethiopian, Injera, Doro Wat', 'Bole, Addis Ababa', '+251911000002', '09:00:00', '22:00:00', 4.3, 'active');

INSERT INTO categories (restaurant_id, name, display_order) VALUES
(1, 'Main dishes', 1),
(1, 'Beverages', 2),
(1, 'Desserts', 3);

INSERT INTO menu_items (category_id, name, description, price, is_available) VALUES
(1, 'Doro Wat with Injera', 'Spicy chicken stew served with injera.', 250.00, 1),
(1, 'Kitfo', 'Minced raw beef seasoned with spices.', 280.00, 1),
(1, 'Tibs', 'Sauteed beef with onions and spices.', 220.00, 1),
(2, 'Ethiopian Coffee', 'Freshly brewed coffee traditional style.', 50.00, 1),
(2, 'Fresh Juice', 'Seasonal fruit juice.', 40.00, 1);

INSERT INTO orders (order_number, user_id, restaurant_id, delivery_address, payment_method, payment_status, total_amount, delivery_fee, status) VALUES
('GB100001', 3, 1, 'Bole, Addis Ababa, Building 12, Apt 5A', 'cash', 'pending', 530.00, 0.00, 'pending');

INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES
(1, 1, 1, 250.00),
(1, 2, 1, 280.00);
