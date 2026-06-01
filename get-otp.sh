#!/bin/bash
# Get latest OTP codes from database
# Usage: ./get-otp.sh [email]

echo "🔐 Gebeta OTP Retrieval Tool"
echo "============================"
echo ""

if [ -z "$1" ]; then
    echo "📋 Latest 5 OTP codes (all users):"
    echo ""
    mysql -u root -p gebeta -e "
        SELECT 
            email, 
            code, 
            purpose, 
            CASE WHEN expires_at > NOW() THEN '✅ Valid' ELSE '❌ Expired' END as status,
            created_at 
        FROM otps 
        WHERE used = 0 
        ORDER BY created_at DESC 
        LIMIT 5;" 2>/dev/null
else
    echo "📋 OTP codes for: $1"
    echo ""
    mysql -u root -p gebeta -e "
        SELECT 
            code, 
            purpose, 
            CASE WHEN expires_at > NOW() THEN '✅ Valid' ELSE '❌ Expired' END as status,
            created_at,
            expires_at
        FROM otps 
        WHERE email = '$1' AND used = 0 
        ORDER BY created_at DESC 
        LIMIT 3;" 2>/dev/null
fi

echo ""
echo "💡 Tip: OTP codes expire in 15 minutes"
echo "💡 To watch for new OTPs: tail -f /var/log/apache2/error.log | grep 'Gebeta OTP'"
