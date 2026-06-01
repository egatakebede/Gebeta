#!/bin/bash
# Gebeta Application - Quick Test Script
# Run this to verify all fixes are working

echo "🔍 Gebeta Application - Testing Fixes"
echo "======================================"
echo ""

# Test 1: Check if database tables exist
echo "✓ Test 1: Checking database tables..."
mysql -u root -p gebeta -e "SHOW TABLES LIKE 'otps';" 2>/dev/null && echo "  ✅ otps table exists" || echo "  ❌ otps table missing - run fix-database.sql"
mysql -u root -p gebeta -e "SHOW TABLES LIKE 'registration_pending';" 2>/dev/null && echo "  ✅ registration_pending table exists" || echo "  ❌ registration_pending table missing - run fix-database.sql"
echo ""

# Test 2: Check if .env file exists and has required variables
echo "✓ Test 2: Checking .env configuration..."
if [ -f ".env" ]; then
    echo "  ✅ .env file exists"
    grep -q "BREVO_API_KEY=" .env && echo "  ✅ BREVO_API_KEY configured" || echo "  ⚠️  BREVO_API_KEY not found"
    grep -q "DB_HOST=" .env && echo "  ✅ DB_HOST configured" || echo "  ❌ DB_HOST not found"
    grep -q "DB_NAME=" .env && echo "  ✅ DB_NAME configured" || echo "  ❌ DB_NAME not found"
else
    echo "  ❌ .env file not found"
fi
echo ""

# Test 3: Check if CSRF protection is enabled
echo "✓ Test 3: Checking CSRF protection..."
grep -q "csrf_verify()" login.php && echo "  ✅ CSRF enabled in login.php" || echo "  ❌ CSRF missing in login.php"
grep -q "csrf_verify()" register.php && echo "  ✅ CSRF enabled in register.php" || echo "  ❌ CSRF missing in register.php"
echo ""

# Test 4: Check if email function is fixed
echo "✓ Test 4: Checking email function..."
grep -q "if (!$apiKey || $apiKey === '')" includes/functions.php && echo "  ✅ Email function has API key validation" || echo "  ❌ Email function missing validation"
echo ""

# Test 5: Check if session variables are correct
echo "✓ Test 5: Checking session variable fixes..."
grep -q "pending_email" api/resend-otp.php && echo "  ✅ Correct session variable in resend-otp.php" || echo "  ❌ Wrong session variable"
echo ""

# Test 6: Check if hardcoded credentials are removed
echo "✓ Test 6: Checking for hardcoded credentials..."
grep -q "AVNS_AcTxZFvGTBqvOJcYhPY" includes/config.php && echo "  ⚠️  Hardcoded password still in config.php" || echo "  ✅ No hardcoded credentials in config.php"
echo ""

# Test 7: Check if SQL queries use prepared statements
echo "✓ Test 7: Checking SQL injection protection..."
grep -c "->prepare(" admin/dashboard.php | grep -q "[5-9]\|[1-9][0-9]" && echo "  ✅ Admin dashboard uses prepared statements" || echo "  ⚠️  Check admin dashboard queries"
echo ""

echo "======================================"
echo "📋 Summary:"
echo "  - Review any ❌ or ⚠️  items above"
echo "  - Run fix-database.sql if tables are missing"
echo "  - Configure BREVO_API_KEY in .env for email"
echo "  - Test registration and login flows manually"
echo ""
echo "📖 See FIXES_APPLIED.md for complete details"
