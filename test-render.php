<?php
// Test script to debug Render deployment
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "\n";

require_once __DIR__ . '/includes/config.php';
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_PORT: " . DB_PORT . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "BREVO_API_KEY: " . (BREVO_API_KEY ? 'Set' : 'Not set') . "\n";
echo "\n";

try {
    require_once __DIR__ . '/includes/db.php';
    echo "Database connection: SUCCESS\n";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM otps WHERE Field = 'purpose'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "OTP purpose ENUM: " . $result['Type'] . "\n";
} catch (Exception $e) {
    echo "Database connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
}
