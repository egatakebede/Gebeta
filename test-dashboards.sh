#!/bin/bash

echo "=== Testing All Dashboards ==="
echo ""

# Test Admin Dashboard
echo "1. Testing Admin Dashboard..."
php -l /home/e/Gebeta/admin/dashboard.php
if [ $? -eq 0 ]; then
    echo "✅ Admin dashboard: No syntax errors"
else
    echo "❌ Admin dashboard: Syntax errors found"
fi
echo ""

# Test Restaurant Dashboard
echo "2. Testing Restaurant Dashboard..."
php -l /home/e/Gebeta/restaurant/dashboard.php
if [ $? -eq 0 ]; then
    echo "✅ Restaurant dashboard: No syntax errors"
else
    echo "❌ Restaurant dashboard: Syntax errors found"
fi
echo ""

# Test Restaurant Pending Dashboard
echo "3. Testing Restaurant Pending Dashboard..."
php -l /home/e/Gebeta/restaurant/pending-dashboard.php
if [ $? -eq 0 ]; then
    echo "✅ Restaurant pending dashboard: No syntax errors"
else
    echo "❌ Restaurant pending dashboard: Syntax errors found"
fi
echo ""

# Test Customer Dashboard
echo "4. Testing Customer Dashboard..."
php -l /home/e/Gebeta/customer/dashboard.php
if [ $? -eq 0 ]; then
    echo "✅ Customer dashboard: No syntax errors"
else
    echo "❌ Customer dashboard: Syntax errors found"
fi
echo ""

# Test Index Redirect Logic
echo "5. Testing Index Redirect Logic..."
php -l /home/e/Gebeta/index.php
if [ $? -eq 0 ]; then
    echo "✅ Index page: No syntax errors"
else
    echo "❌ Index page: Syntax errors found"
fi
echo ""

echo "=== Dashboard Test Summary ==="
echo "All dashboards checked for syntax errors."
echo "Manual testing required for:"
echo "  - Admin login and dashboard access"
echo "  - Restaurant owner (active) dashboard"
echo "  - Restaurant owner (pending) dashboard"
echo "  - Customer dashboard"
