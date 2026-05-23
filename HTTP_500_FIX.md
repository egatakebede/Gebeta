# 🔧 HTTP 500 Error - Quick Fix Guide

## Immediate Steps to Debug

### 1. Check Debug Page
Visit: `https://gebeta-52mf.onrender.com/debug.php`

This will show:
- PHP version and extensions
- Database connection status
- Configuration values
- File permissions

### 2. Check Health Page
Visit: `https://gebeta-52mf.onrender.com/health.php`

Should return: `{"status":"ok","timestamp":...}`

---

## Common Causes & Solutions

### ❌ Database Connection Failed

**Symptoms**: Can't connect to Aiven database

**Solutions**:
1. Verify environment variables in Render dashboard:
   ```
   DB_HOST=your-aiven-host.aivencloud.com:port
   DB_NAME=defaultdb
   DB_USER=avnadmin
   DB_PASS=your_database_password_here
   ```

2. Check if Aiven database is running
3. Verify SSL settings (we have `SSL_VERIFY_SERVER_CERT => false`)

### ❌ Session Issues

**Symptoms**: Session errors, can't start session

**Solutions**:
1. Check if `/tmp` directory is writable
2. Verify session.save_path in PHP config
3. Add to Dockerfile:
   ```dockerfile
   RUN mkdir -p /tmp && chmod 777 /tmp
   ```

### ❌ Missing PHP Extensions

**Symptoms**: Call to undefined function

**Solutions**:
Check Dockerfile has all required extensions:
```dockerfile
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd
```

### ❌ File Permission Issues

**Symptoms**: Can't write to uploads directory

**Solutions**:
```dockerfile
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads
```

---

## Step-by-Step Debugging

### Step 1: Enable Error Display (Already Done)
`.htaccess` now shows errors temporarily

### Step 2: Check Render Logs
```bash
# In Render dashboard:
1. Go to your service
2. Click "Logs" tab
3. Look for PHP errors or database connection errors
```

### Step 3: Test Database Connection
```bash
# SSH into Render container (if available) or use debug.php
mysql -h gebeta-db-gebeta.a.aivencloud.com -P 23863 -u avnadmin -p defaultdb
```

### Step 4: Verify Environment Variables
In Render dashboard:
- Settings → Environment
- Make sure all variables are set
- Click "Manual Deploy" after changes

---

## Quick Fixes Applied

✅ Added error handling to `index.php`
✅ Improved error messages in `db.php`
✅ Created `debug.php` for diagnostics
✅ Enabled error display in `.htaccess`
✅ Added connection timeout to database

---

## Next Steps

1. **Visit debug.php** to see exact error
2. **Check Render logs** for detailed error messages
3. **Verify database credentials** in Render environment variables
4. **Redeploy** if environment variables were changed

---

## If Database Connection Fails

### Option A: Use Local MySQL (Development)
Update `.env`:
```env
DB_HOST=localhost
DB_NAME=gebeta
DB_USER=root
DB_PASS=
```

### Option B: Verify Aiven Connection
1. Login to Aiven console
2. Check if database is running
3. Verify connection details
4. Check if IP whitelist allows Render IPs

### Option C: Test Connection Manually
```php
<?php
$host = 'your-aiven-host.aivencloud.com';
$port = 23863;
$db = 'defaultdb';
$user = 'avnadmin';
$pass = 'your_password_here';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ]);
    echo "Connected!";
} catch (PDOException $e) {
    echo "Failed: " . $e->getMessage();
}
?>
```

---

## Disable Error Display (After Fixing)

Once fixed, update `.htaccess`:
```apache
<IfModule mod_php.c>
    php_flag display_errors Off
    php_flag log_errors On
</IfModule>
```

---

## Contact Support

If issue persists:
1. Share output from `/debug.php`
2. Share Render logs
3. Share exact error message

---

**Last Updated**: January 2025
