-- Default Test Users for Gebeta
-- All passwords are: password123

-- Insert test users
INSERT INTO users (name, email, phone, password, role, status, latitude, longitude, location_name) VALUES
-- Admin
('Admin User', 'admin@gebeta.com', '+251911000001', '$2y$10$Z2nVTfsIoG5oDsJN8vnwQOtqDDwqUD6dlfFxzFqqOGbXPY8CaX/ai', 'admin', 'active', 7.0621, 38.4760, 'Hawassa, Ethiopia'),

-- Restaurant Owner
('Yod Restaurant', 'yod@restaurant.com', '+251911000002', '$2y$10$eNDh14nZSQl7qKXnfBnoQezl8rVlKqxlj9grohwTkQaihcs87sZFC', 'restaurant', 'active', 7.0621, 38.4760, 'Hawassa, Ethiopia'),

-- Customer
('Test Customer', 'customer@test.com', '+251911000003', '$2y$10$iTE5bq9RdPVCtv/N1u0QluUZdvuLFXakYIpsJYkz3l82XX/n4sUKm', 'customer', 'active', 7.0621, 38.4760, 'Hawassa, Ethiopia'),

-- Delivery Partners
('Abebe Kebede', 'delivery1@gebeta.com', '+251911000004', '$2y$10$Z2nVTfsIoG5oDsJN8vnwQOtqDDwqUD6dlfFxzFqqOGbXPY8CaX/ai', 'delivery', 'active', 7.0621, 38.4760, 'Hawassa, Ethiopia'),
('Tigist Alemu', 'delivery2@gebeta.com', '+251911000005', '$2y$10$Z2nVTfsIoG5oDsJN8vnwQOtqDDwqUD6dlfFxzFqqOGbXPY8CaX/ai', 'delivery', 'active', 7.0621, 38.4760, 'Hawassa, Ethiopia'),
('Dawit Tesfaye', 'delivery3@gebeta.com', '+251911000006', '$2y$10$Z2nVTfsIoG5oDsJN8vnwQOtqDDwqUD6dlfFxzFqqOGbXPY8CaX/ai', 'delivery', 'active', 7.0621, 38.4760, 'Hawassa, Ethiopia');

-- Insert delivery partner details (assuming user IDs 4, 5, 6 for delivery partners)
INSERT INTO delivery_partners (user_id, phone, vehicle_type, vehicle_number, vehicle_color, license_number, license_expiry, is_available, status, rating, total_deliveries, verified) VALUES
(4, '+251911000004', 'bike', 'AA-12345', 'Red', 'DL-2024-001', '2026-12-31', TRUE, 'online', 4.8, 156, TRUE),
(5, '+251911000005', 'auto', 'AA-67890', 'Blue', 'DL-2024-002', '2027-06-30', TRUE, 'online', 4.6, 89, TRUE),
(6, '+251911000006', 'car', 'AA-11223', 'White', 'DL-2024-003', '2025-12-31', TRUE, 'offline', 4.9, 234, TRUE);
