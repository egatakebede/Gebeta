-- Gebeta Database Schema for Aiven (defaultdb)

DROP TABLE IF EXISTS delivery_ratings;
DROP TABLE IF EXISTS order_deliveries;
DROP TABLE IF EXISTS delivery_partners;
DROP TABLE IF EXISTS delivery_addresses;
DROP TABLE IF EXISTS otps;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS registration_pending;
DROP TABLE IF EXISTS restaurants;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    google_id VARCHAR(255) UNIQUE DEFAULT NULL,
    role ENUM('customer', 'restaurant', 'admin', 'delivery') DEFAULT 'customer',
    status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active',
    latitude DECIMAL(10,8) DEFAULT NULL,
    longitude DECIMAL(11,8) DEFAULT NULL,
    location_name VARCHAR(255) DEFAULT NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    verified_at DATETIME NULL,
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
    latitude DECIMAL(10,8) DEFAULT NULL,
    longitude DECIMAL(11,8) DEFAULT NULL,
    delivery_time VARCHAR(50) DEFAULT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_location (latitude, longitude)
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_purpose (email, purpose)
);

CREATE TABLE registration_pending (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'restaurant', 'delivery') NOT NULL,
    latitude DECIMAL(10,8) DEFAULT NULL,
    longitude DECIMAL(11,8) DEFAULT NULL,
    location_name VARCHAR(255) DEFAULT NULL,
    restaurant_name VARCHAR(150),
    cuisine_type VARCHAR(200),
    restaurant_address VARCHAR(200),
    delivery_time VARCHAR(50),
    delivery_fee DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    INDEX(email)
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

CREATE TABLE delivery_addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    address_name VARCHAR(50),
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    full_address VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_location (latitude, longitude)
);

CREATE TABLE delivery_partners (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    vehicle_type ENUM('bike', 'car', 'auto') DEFAULT 'bike',
    vehicle_number VARCHAR(20),
    vehicle_color VARCHAR(50),
    license_number VARCHAR(50) NOT NULL UNIQUE,
    license_expiry DATE,
    is_available BOOLEAN DEFAULT TRUE,
    status ENUM('offline', 'online', 'on_delivery') DEFAULT 'offline',
    current_latitude DECIMAL(10,8),
    current_longitude DECIMAL(11,8),
    rating DECIMAL(2,1) DEFAULT 0.0,
    total_deliveries INT DEFAULT 0,
    total_earnings DECIMAL(10,2) DEFAULT 0,
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_available (is_available),
    INDEX idx_status (status),
    INDEX idx_location (current_latitude, current_longitude)
);

CREATE TABLE order_deliveries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL UNIQUE,
    delivery_partner_id INT,
    restaurant_latitude DECIMAL(10,8) NOT NULL,
    restaurant_longitude DECIMAL(11,8) NOT NULL,
    customer_latitude DECIMAL(10,8) NOT NULL,
    customer_longitude DECIMAL(11,8) NOT NULL,
    assigned_at TIMESTAMP NULL,
    pickup_at TIMESTAMP NULL,
    in_transit_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    distance_km DECIMAL(5,2),
    delivery_fee DECIMAL(10,2),
    status ENUM('pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'cancelled') DEFAULT 'pending',
    current_latitude DECIMAL(10,8),
    current_longitude DECIMAL(11,8),
    last_location_update TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_partner_id) REFERENCES delivery_partners(id) ON DELETE SET NULL,
    INDEX idx_partner (delivery_partner_id),
    INDEX idx_status (status)
);

CREATE TABLE delivery_ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL UNIQUE,
    delivery_partner_id INT NOT NULL,
    customer_id INT NOT NULL,
    rating INT CHECK(rating BETWEEN 1 AND 5) NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_partner_id) REFERENCES delivery_partners(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_partner (delivery_partner_id)
);

INSERT INTO users (name, email, phone, password, role, status) VALUES
('Admin User', 'admin@gebeta.com', '+251911111111', '$2y$10$W9rUY3S7u9pHXxN3m5pC3eJ2q8kQ1aX5b8nL2pM3vJ9x5Y7z1A8Zq', 'admin', 'active'),
('Abebe Tadesse', 'abebe@customer.com', '+251922222222', '$2y$10$W9rUY3S7u9pHXxN3m5pC3eJ2q8kQ1aX5b8nL2pM3vJ9x5Y7z1A8Zq', 'customer', 'active'),
('Yodit Abyssinia Restaurant', 'yod@restaurant.com', '+251911777777', '$2y$10$W9rUY3S7u9pHXxN3m5pC3eJ2q8kQ1aX5b8nL2pM3vJ9x5Y7z1A8Zq', 'restaurant', 'active'),
('Abebe Driver', 'abebe.driver@delivery.com', '+251921222222', '$2y$10$W9rUY3S7u9pHXxN3m5pC3eJ2q8kQ1aX5b8nL2pM3vJ9x5Y7z1A8Zq', 'delivery', 'active');

INSERT INTO restaurants (user_id, name, description, cuisine_type, location, phone, opening_time, closing_time, rating, delivery_time, delivery_fee, status) VALUES
(3, 'Yod Abyssinia', 'Authentic Ethiopian food with traditional recipes.', 'Ethiopian, Injera, Doro Wat', 'Piassa, Hawassa', '+251911777777', '09:00:00', '22:00:00', 4.8, '25-35', 0.00, 'active');

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
('GB100001', 3, 1, 'Piassa, Hawassa, Building 12, Apt 5A', 'cash', 'pending', 530.00, 0.00, 'pending');

INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES
(1, 1, 1, 250.00),
(1, 2, 1, 280.00);
