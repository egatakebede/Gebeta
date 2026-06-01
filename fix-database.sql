-- Fix Database Schema - Add missing tables and columns
-- Run this to ensure all required tables exist

USE gebeta;

-- Ensure otps table exists with correct structure
CREATE TABLE IF NOT EXISTS otps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    code VARCHAR(6) NOT NULL,
    purpose ENUM('register', 'login', 'reset') NOT NULL,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_purpose (email, purpose),
    INDEX idx_expires (expires_at)
);

-- Ensure registration_pending table exists
CREATE TABLE IF NOT EXISTS registration_pending (
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
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
);

-- Add missing columns to users table if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,8) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS longitude DECIMAL(11,8) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS location_name VARCHAR(255) DEFAULT NULL;

-- Add missing columns to restaurants table if they don't exist
ALTER TABLE restaurants 
ADD COLUMN IF NOT EXISTS delivery_time VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS delivery_fee DECIMAL(10,2) DEFAULT 0.00;

-- Clean up expired OTPs and pending registrations (older than 1 hour)
DELETE FROM otps WHERE expires_at < NOW();
DELETE FROM registration_pending WHERE expires_at < NOW();

-- Ensure admin user exists (password: Admin@123)
INSERT IGNORE INTO users (id, name, email, phone, password, role, status, email_verified) 
VALUES (1, 'Admin User', 'admin@gebeta.com', '+251911111111', 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'admin', 'active', TRUE);

SELECT 'Database schema fixed successfully!' as message;
