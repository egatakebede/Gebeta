<?php
// Debug page - Remove in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Gebeta Debug Info</h1>";

// PHP Version
echo "<h2>PHP Version</h2>";
echo "<p>" . phpversion() . "</p>";

// Check if .env file exists
echo "<h2>Environment File</h2>";
$envFile = __DIR__ . '/.env';
echo "<p>.env exists: " . (file_exists($envFile) ? 'Yes' : 'No') . "</p>";

// Load config
try {
    require_once __DIR__ . '/includes/config.php';
    echo "<h2>Configuration Loaded</h2>";
    echo "<ul>";
    echo "<li>DB_HOST: " . htmlspecialchars(DB_HOST) . "</li>";
    echo "<li>DB_PORT: " . htmlspecialchars(DB_PORT) . "</li>";
    echo "<li>DB_NAME: " . htmlspecialchars(DB_NAME) . "</li>";
    echo "<li>DB_USER: " . htmlspecialchars(DB_USER) . "</li>";
    echo "<li>DB_PASS: " . (DB_PASS ? '***SET***' : 'EMPTY') . "</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error loading config: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::ATTR_TIMEOUT => 5
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "<p style='color:green'>✓ Database connection successful!</p>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>Users in database: " . $result['count'] . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Database connection failed:</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

// Check session
echo "<h2>Session Status</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Logged in: " . (isset($_SESSION['user']) ? 'Yes' : 'No') . "</p>";

// Check required extensions
echo "<h2>PHP Extensions</h2>";
$required = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session'];
echo "<ul>";
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? '✓' : '✗';
    $color = $loaded ? 'green' : 'red';
    echo "<li style='color:$color'>$status $ext</li>";
}
echo "</ul>";

// Check file permissions
echo "<h2>File Permissions</h2>";
$dirs = ['uploads', 'uploads/restaurants', 'uploads/menu'];
echo "<ul>";
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path) ? '✓' : '✗';
        echo "<li>$dir: $perms ($writable writable)</li>";
    } else {
        echo "<li style='color:red'>$dir: Does not exist</li>";
    }
}
echo "</ul>";

echo "<hr>";
echo "<p><a href='/'>← Back to Home</a></p>";
?>
<style>
body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; }
h1 { color: #FC8019; }
h2 { color: #333; border-bottom: 2px solid #FC8019; padding-bottom: 5px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
ul { list-style: none; padding-left: 0; }
li { padding: 5px 0; }
</style>
