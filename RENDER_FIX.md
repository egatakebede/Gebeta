# 🚀 Render.com 500 Error - Fix Guide

## ✅ **FIXED: Customer Dashboard Error Handling**

### 🔧 What Was Fixed:

1. **✅ Added comprehensive error handling** to customer dashboard
2. **✅ Created health-check.php** for diagnostics
3. **✅ Fixed SQL query** for NULL cuisine_type values
4. **✅ Added fallback values** if database queries fail

---

## 🔍 **Diagnose the Issue on Render:**

### Step 1: Check Health Endpoint

Visit: `https://gebeta-52mf.onrender.com/health-check.php`

This will show:
- ✅ PHP version
- ✅ Database connection status
- ✅ Table counts
- ✅ Session status
- ✅ Directory permissions
- ❌ Any errors with details

### Step 2: Check Render Logs

1. Go to Render Dashboard
2. Click on your service
3. Click "Logs" tab
4. Look for PHP errors

---

## 🐛 **Common Render.com Issues:**

### Issue 1: Database Not Connected

**Symptoms:** 500 error, health check shows database error

**Fix:**
```bash
# In Render Dashboard → Environment
# Verify these variables are set:
DB_HOST=gebeta-db-gebeta.a.aivencloud.com:23863
DB_NAME=defaultdb
DB_USER=avnadmin
DB_PASS=your_password
```

### Issue 2: Missing Tables

**Symptoms:** Health check shows "table doesn't exist"

**Fix:**
```bash
# Connect to Aiven database
mysql -h gebeta-db-gebeta.a.aivencloud.com -P 23863 -u avnadmin -p defaultdb

# Import schema
source aiven-import.sql
```

### Issue 3: Session Directory Not Writable

**Symptoms:** Session errors in logs

**Fix:** Already handled in code with `@session_start()`

### Issue 4: Upload Directories Missing

**Symptoms:** Image upload fails

**Fix:** Add to `render.yaml`:
```yaml
buildCommand: |
  mkdir -p uploads/restaurants uploads/menu
  chmod 755 uploads/restaurants uploads/menu
```

---

## 📝 **Render.com Environment Variables:**

Make sure these are set in Render Dashboard:

```env
# Database (Aiven)
DB_HOST=your_aiven_host:port
DB_NAME=defaultdb
DB_USER=avnadmin
DB_PASS=your_aiven_password

# Email (Brevo)
BREVO_API_KEY=your_brevo_api_key_here
BREVO_SENDER_EMAIL=your_email@domain.com

# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
```

---

## 🔄 **Deploy the Fix:**

```bash
# Push to GitHub
cd /home/e/Gebeta
git push origin main

# Render will auto-deploy
# Wait 2-3 minutes for deployment
```

---

## ✅ **Verify the Fix:**

1. **Check Health:**
   ```
   https://gebeta-52mf.onrender.com/health-check.php
   ```

2. **Try Login:**
   ```
   https://gebeta-52mf.onrender.com
   Email: customer@test.com
   Password: password123
   ```

3. **Check Dashboard:**
   ```
   https://gebeta-52mf.onrender.com/customer/dashboard.php
   ```

---

## 🆘 **If Still Getting 500 Error:**

### Check Render Logs:

1. Render Dashboard → Your Service → Logs
2. Look for PHP errors
3. Common errors:
   - `PDOException` - Database connection issue
   - `Fatal error` - Missing file or function
   - `Warning` - Non-critical issue

### Enable Debug Mode:

Add to Render environment:
```env
PHP_DISPLAY_ERRORS=1
PHP_ERROR_REPORTING=E_ALL
```

Then check the page again - errors will show.

### Manual Database Check:

```bash
# Connect to Aiven
mysql -h gebeta-db-gebeta.a.aivencloud.com -P 23863 -u avnadmin -p defaultdb

# Check tables
SHOW TABLES;

# Check users
SELECT email, role FROM users;

# Check restaurants
SELECT id, name, status FROM restaurants;
```

---

## 📊 **What the Fix Does:**

1. **Wraps all queries in try-catch** - Prevents 500 errors
2. **Sets default values** - Dashboard loads even if queries fail
3. **Shows error message** - User sees friendly error instead of 500
4. **Logs errors** - Helps debug in Render logs
5. **Checks function exists** - Prevents undefined function errors

---

## 🎯 **Expected Behavior After Fix:**

- ✅ Dashboard loads (even if some data is missing)
- ✅ Shows error message if database issue
- ✅ Doesn't crash with 500 error
- ✅ Health check shows detailed status

---

**Push the fix and check health-check.php on Render!** 🚀
