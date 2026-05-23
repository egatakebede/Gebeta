<?php
// Simple database connection test
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>DB Connection Test</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;}.error{color:red;}pre{background:#f5f5f5;padding:15px;border-radius:5px;}</style></head><body>";

echo "<h1>🔌 Database Connection Test</h1>";

// Test 1: Check if .env exists
echo "<h2>1. Environment File</h2>";
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "<p class='success'>✓ .env file exists</p>";
} else {
    echo "<p class='error'>✗ .env file not found</p>";
}

// Test 2: Load environment variables
echo "<h2>2. Environment Variables</h2>";
$host = getenv('DB_HOST') ?: 'gebeta-db-gebeta.a.aivencloud.com:23863';
$name = getenv('DB_NAME') ?: 'defaultdb';
$user = getenv('DB_USER') ?: 'avnadmin';
$pass = getenv('DB_PASS') ?: 'AVNS_AcTxZFvGTBqvOJcYhPY';

// Parse host:port
$port = 3306;
if (strpos($host, ':') !== false) {
    list($host, $port) = explode(':', $host, 2);
}

echo "<ul>";
echo "<li>Host: <strong>" . htmlspecialchars($host) . "</strong></li>";
echo "<li>Port: <strong>" . htmlspecialchars($port) . "</strong></li>";
echo "<li>Database: <strong>" . htmlspecialchars($name) . "</strong></li>";
echo "<li>User: <strong>" . htmlspecialchars($user) . "</strong></li>";
echo "<li>Password: <strong>" . ($pass ? '***SET***' : 'EMPTY') . "</strong></li>";
echo "</ul>";

// Test 3: Check PDO extension
echo "<h2>3. PDO Extension</h2>";
if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
    echo "<p class='success'>✓ PDO and PDO_MySQL extensions loaded</p>";
} else {
    echo "<p class='error'>✗ PDO extensions not loaded</p>";
    echo "</body></html>";
    exit;
}

// Test 4: Attempt connection
echo "<h2>4. Database Connection</h2>";
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
    echo "<p>DSN: <code>$dsn</code></p>";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::ATTR_TIMEOUT => 10
    ];
    
    echo "<p>Attempting connection...</p>";
    $startTime = microtime(true);
    $pdo = new PDO($dsn, $user, $pass, $options);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "<p class='success'>✓ Connection successful! (took {$duration}ms)</p>";
    
    // Test 5: Query test
    echo "<h2>5. Query Test</h2>";
    try {
        $stmt = $pdo->query("SELECT VERSION() as version");
        $result = $stmt->fetch();
        echo "<p class='success'>✓ MySQL Version: " . htmlspecialchars($result['version']) . "</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "<p class='success'>✓ Users table accessible: " . $result['count'] . " users found</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM restaurants");
        $result = $stmt->fetch();
        echo "<p class='success'>✓ Restaurants table accessible: " . $result['count'] . " restaurants found</p>";
        
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ Connection failed</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    
    echo "<h3>Troubleshooting Tips:</h3>";
    echo "<ul>";
    echo "<li>Verify database credentials in Render environment variables</li>";
    echo "<li>Check if Aiven database is running</li>";
    echo "<li>Verify firewall/IP whitelist settings</li>";
    echo "<li>Check if SSL certificate is valid</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='/'>← Back to Home</a> | <a href='/debug.php'>Full Debug Info</a></p>";
echo "</body></html>";
?>
