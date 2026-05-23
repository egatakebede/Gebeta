-- Migration Script for Aiven Database
-- Run this to add delivery partner tables to existing database

-- Add delivery role to users if not exists
ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'restaurant', 'admin', 'delivery') DEFAULT 'customer';

-- Add location columns to restaurants if not exists
ALTER TABLE restaurants 
ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,8) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS longitude DECIMAL(11,8) DEFAULT NULL,
ADD INDEX IF NOT EXISTS idx_location (latitude, longitude);

-- Add updated_at to orders if not exists
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create delivery_addresses table
CREATE TABLE IF NOT EXISTS delivery_addresses (
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

-- Create delivery_partners table
CREATE TABLE IF NOT EXISTS delivery_partners (
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

-- Create order_deliveries table
CREATE TABLE IF NOT EXISTS order_deliveries (
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

-- Create delivery_ratings table
CREATE TABLE IF NOT EXISTS delivery_ratings (
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

-- Verify migration
SELECT 'Migration completed successfully!' AS status;
SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME;
