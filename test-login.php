<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing login flow...<br>";

try {
    require_once __DIR__ . '/includes/auth.php';
    echo "✓ Auth loaded<br>";
    
    require_once __DIR__ . '/includes/db.php';
    echo "✓ DB loaded<br>";
    
    // Test database connection
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✓ Database connected. Users count: " . $result['count'] . "<br>";
    
    // Test session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "✓ Session started<br>";
    
    echo "<br>All systems operational!";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}
