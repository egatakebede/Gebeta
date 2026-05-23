# 🔧 Gebeta Troubleshooting Guide

## 🚨 Common Issues & Solutions

---

## Issue: "An error occurred during login. Please try again."

### Possible Causes:

1. **Database Connection Failed**
2. **Missing Database Tables**
3. **Incorrect Password Hash**
4. **Session Issues**

### Solutions:

#### Step 1: Test Database Connection

Visit: `http://localhost:8000/test-db.php`

This will show:
- ✅ Database connection status
- ✅ List of users in database
- ✅ Table counts
- ✅ Test credentials

#### Step 2: Verify Database Setup

```bash
# Check if MySQL is running
sudo systemctl status mysql

# Or on macOS
brew services list | grep mysql

# Login to MySQL
mysql -u root -p

# Check if database exists
SHOW DATABASES;

# Use the database
USE gebeta;

# Check tables
SHOW TABLES;

# Check users
SELECT id, name, email, role FROM users;
```

#### Step 3: Import Database (if missing)

```bash
# Import the schema
mysql -u root -p < gebeta.sql

# Or if using specific database
mysql -u root -p gebeta < gebeta.sql
```

#### Step 4: Reset Test User Passwords

```sql
-- Login to MySQL
mysql -u root -p

USE gebeta;

-- Reset admin password
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'admin@gebeta.com';

-- Reset restaurant password
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'yod@restaurant.com';

-- Reset customer password
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'customer@test.com';
```

All passwords will be: `password123`

#### Step 5: Check .env File

```bash
# Verify .env exists
ls -la .env

# Check contents
cat .env
```

Should contain:
```env
DB_HOST=localhost:3306
DB_NAME=gebeta
DB_USER=root
DB_PASS=your_password
```

#### Step 6: Check PHP Error Logs

```bash
# Check PHP error log
tail -f /var/log/php_errors.log

# Or Apache error log
tail -f /var/log/apache2/error.log

# Or check PHP built-in server output
# (errors will show in terminal where you ran php -S)
```

---

## Issue: "Database Connection Failed"

### Solutions:

1. **Check MySQL is Running:**
   ```bash
   sudo systemctl start mysql
   # or
   brew services start mysql
   ```

2. **Verify Credentials:**
   - Check `.env` file has correct DB_USER and DB_PASS
   - Test connection: `mysql -u root -p`

3. **Check Port:**
   - Default MySQL port is 3306
   - Verify in `.env`: `DB_HOST=localhost:3306`

4. **For Aiven Cloud Database:**
   ```env
   DB_HOST=your-host.aivencloud.com:23863
   DB_NAME=defaultdb
   DB_USER=avnadmin
   DB_PASS=your_password
   ```

---

## Issue: "Email or password is incorrect"

### Solutions:

1. **Verify User Exists:**
   ```sql
   SELECT email, role FROM users WHERE email = 'admin@gebeta.com';
   ```

2. **Reset Password:**
   ```sql
   UPDATE users 
   SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
   WHERE email = 'admin@gebeta.com';
   ```
   Password will be: `password123`

3. **Check Account Status:**
   ```sql
   SELECT email, status FROM users WHERE email = 'admin@gebeta.com';
   ```
   Status should be `active`, not `suspended`

---

## Issue: Location Permission Not Working

### Solutions:

1. **Use HTTPS:**
   - Geolocation requires HTTPS in production
   - For local testing, `localhost` is allowed

2. **Check Browser Permissions:**
   - Chrome: Settings → Privacy → Site Settings → Location
   - Firefox: Preferences → Privacy & Security → Permissions → Location

3. **Test Manually:**
   ```javascript
   // Open browser console (F12)
   navigator.geolocation.getCurrentPosition(
       (pos) => console.log(pos.coords),
       (err) => console.error(err)
   );
   ```

---

## Issue: Restaurant Dashboard Shows "Pending Approval"

### Solutions:

1. **Approve Restaurant (as Admin):**
   ```sql
   UPDATE restaurants SET status = 'active' WHERE user_id = 2;
   ```

2. **Or via Admin Panel:**
   - Login as admin
   - Go to Restaurants
   - Click Approve

---

## Issue: Delivery Partner Can't Login

### Solutions:

1. **Verify Delivery Partner:**
   ```sql
   UPDATE delivery_partners SET verified = 1 WHERE user_id = X;
   ```

2. **Check User Role:**
   ```sql
   SELECT email, role FROM users WHERE email = 'driver@test.com';
   ```
   Role should be `delivery`

---

## Issue: Images Not Loading

### Solutions:

1. **Create Upload Directories:**
   ```bash
   mkdir -p uploads/restaurants uploads/menu
   chmod 755 uploads/restaurants uploads/menu
   ```

2. **Check Permissions:**
   ```bash
   ls -la uploads/
   ```

3. **Verify .htaccess:**
   - Should allow access to uploads directory

---

## Issue: Session Not Persisting

### Solutions:

1. **Check Session Directory:**
   ```bash
   # Check PHP session path
   php -i | grep session.save_path
   
   # Ensure it's writable
   sudo chmod 777 /var/lib/php/sessions
   ```

2. **Clear Sessions:**
   ```bash
   rm -rf /var/lib/php/sessions/*
   ```

3. **Check PHP Configuration:**
   ```bash
   php -i | grep session
   ```

---

## Issue: "Cannot modify header information"

### Solutions:

1. **Check for Output Before Headers:**
   - No echo/print before redirect()
   - No whitespace before `<?php`

2. **Enable Output Buffering:**
   ```php
   // Add to top of file
   ob_start();
   ```

---

## Quick Diagnostic Commands

```bash
# Test database connection
php test-db.php

# Check PHP version (need 8.0+)
php -v

# Check MySQL version (need 8.0+)
mysql --version

# Check if port 8000 is available
lsof -i :8000

# Start PHP server
php -S localhost:8000

# Check error logs
tail -f /var/log/apache2/error.log
```

---

## Test Accounts

After importing `gebeta.sql`, these accounts are available:

```
Admin:
  Email: admin@gebeta.com
  Password: password123
  
Restaurant Owner:
  Email: yod@restaurant.com
  Password: password123
  
Customer:
  Email: customer@test.com
  Password: password123
```

---

## Still Having Issues?

1. **Run Database Test:**
   ```
   http://localhost:8000/test-db.php
   ```

2. **Check PHP Error Display:**
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

3. **Enable Debug Mode:**
   - Check `login.php` - errors are logged
   - Check browser console (F12)
   - Check network tab for AJAX errors

4. **Verify File Permissions:**
   ```bash
   chmod -R 755 /path/to/gebeta
   chmod -R 775 uploads/
   ```

---

## Contact Support

If issues persist:
- Check GitHub Issues: https://github.com/egatakebede/Gebeta/issues
- Email: support@gebeta.com

---

**Most Common Fix:** Import the database!
```bash
mysql -u root -p < gebeta.sql
```
