# 🚀 Quick Start Guide - Gebeta

Get Gebeta running on your local machine in 5 minutes!

---

## ⚡ Quick Setup (5 Minutes)

### Step 1: Prerequisites Check
```bash
# Check PHP version (need 8.0+)
php -v

# Check MySQL version (need 8.0+)
mysql --version
```

### Step 2: Clone & Setup
```bash
# Clone the repository
git clone https://github.com/yourusername/gebeta.git
cd gebeta

# Create environment file
cp .env.local.example .env
```

### Step 3: Database Setup
```bash
# Login to MySQL
mysql -u root -p

# Create database and import schema
CREATE DATABASE gebeta;
USE gebeta;
SOURCE gebeta.sql;
exit;
```

Or use phpMyAdmin:
1. Open http://localhost/phpmyadmin
2. Create database `gebeta`
3. Import `gebeta.sql` file

### Step 4: Configure Environment
Edit `.env` file:
```env
DB_HOST=localhost
DB_NAME=gebeta
DB_USER=root
DB_PASS=your_mysql_password

# Optional: Add email service later
BREVO_API_KEY=
BREVO_SENDER_EMAIL=noreply@gebeta.com
```

### Step 5: Create Upload Directories
```bash
mkdir -p uploads/restaurants uploads/menu
chmod 755 uploads/restaurants uploads/menu
```

### Step 6: Start Server
```bash
# Option 1: PHP Built-in Server
php -S localhost:8000

# Option 2: XAMPP/WAMP
# Copy project to htdocs/gebeta
# Access at http://localhost/gebeta
```

### Step 7: Access Application
Open browser and go to:
```
http://localhost:8000
```

---

## 🔑 Test Accounts

### Admin Dashboard
```
URL: http://localhost:8000/admin/dashboard.php
Email: admin@gebeta.com
Password: password123
```

### Restaurant Owner Dashboard
```
URL: http://localhost:8000/restaurant/dashboard.php
Email: yod@restaurant.com
Password: password123
```

### Customer Dashboard
```
URL: http://localhost:8000/customer/dashboard.php
Email: customer@test.com
Password: password123
```

---

## ✅ Verify Installation

### Test Checklist
- [ ] Landing page loads (http://localhost:8000)
- [ ] Can open login modal
- [ ] Can login with test accounts
- [ ] Customer dashboard shows restaurants
- [ ] Restaurant dashboard shows orders
- [ ] Admin dashboard shows statistics

### Common Issues

**"Database connection failed"**
- Check MySQL is running
- Verify credentials in `.env`
- Ensure database `gebeta` exists

**"Page not found"**
- Check you're using correct URL
- Verify web server is running
- Check file permissions

**"Cannot write to uploads/"**
```bash
chmod -R 755 uploads/
```

---

## 🎯 Next Steps

### 1. Configure Email (Optional)
Sign up for free Brevo account:
1. Go to https://www.brevo.com/
2. Get API key from Settings
3. Add to `.env`:
```env
BREVO_API_KEY=xkeysib-your-key-here
BREVO_SENDER_EMAIL=noreply@gebeta.com
```

### 2. Add Google OAuth (Optional)
1. Create project at https://console.cloud.google.com/
2. Enable Google+ API
3. Create OAuth credentials
4. Add to `.env`:
```env
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
```

### 3. Add Sample Data
```sql
-- Add more restaurants
INSERT INTO restaurants (user_id, name, description, cuisine_type, location, phone, opening_time, closing_time, rating, status) VALUES
(2, 'Kategna Restaurant', 'Traditional Ethiopian dishes', 'Ethiopian, Tibs, Kitfo', 'Megenagna, Addis Ababa', '+251911000004', '08:00:00', '23:00:00', 4.5, 'active'),
(2, 'Lucy Restaurant', 'Modern Ethiopian fusion', 'Ethiopian, International', 'Bole, Addis Ababa', '+251911000005', '10:00:00', '22:00:00', 4.7, 'active');

-- Add more menu items
INSERT INTO categories (restaurant_id, name, display_order) VALUES
(2, 'Signature Dishes', 1),
(2, 'Drinks', 2);

INSERT INTO menu_items (category_id, name, description, price, is_available) VALUES
(4, 'Special Tibs', 'Grilled beef with vegetables', 300.00, 1),
(4, 'Shiro Wat', 'Chickpea stew with injera', 180.00, 1),
(5, 'Fresh Mango Juice', 'Freshly squeezed mango juice', 60.00, 1);
```

### 4. Customize Design
Edit `assets/css/style.css` to change:
- Primary color (default: #FC8019)
- Fonts
- Spacing
- Button styles

### 5. Add Your Logo
Replace placeholder logo:
```bash
# Add your logo image
cp your-logo.png assets/images/logo.png
```

---

## 📚 Learn More

- [Full Documentation](README.md)
- [API Reference](docs/api-reference.md)
- [Database Schema](docs/erd.md)
- [Deployment Guide](DEPLOYMENT.md)

---

## 🆘 Need Help?

- Check [Troubleshooting Guide](README.md#troubleshooting)
- Open an issue on GitHub
- Email: support@gebeta.com

---

## 🎉 You're Ready!

Start building your food delivery empire! 🍽️

**Happy Coding!** 🚀
