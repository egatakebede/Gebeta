# Gebeta Setup Guide

## Requirements
- PHP 8.x
- MySQL / MariaDB
- Web server (Apache, Nginx, XAMPP)
- `uploads/` folder writable by web server

## Installation
1. Copy the project to your web root.
2. Create a MySQL database named `gebeta`.
3. Import `gebeta.sql` into the database.
4. Update `includes/config.php` if your database credentials differ.
5. Ensure `uploads/restaurants/` and `uploads/menu/` are writable.
6. Open the app in your browser.

## Sample accounts
- Admin: `admin@gebeta.com` / `admin123`
- Restaurant: `yod@restaurant.com` / `restaurant123`
- Customer: `customer@test.com` / `customer123`

## Notes
- Login and registration use modal forms on `index.php`.
- Customers can browse restaurants, add to cart, and checkout.
- Restaurant owners can manage menu items and update order status.
- Admin users can view restaurants, users, orders, and reports.
