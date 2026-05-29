<?php
// Set default timezone for OTP calculations
date_default_timezone_set('Africa/Addis_Ababa');

// Load .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed) || $trimmed[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, '"\'');
        
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function env_get($key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

// Auto-detect environment
$hostname = gethostname();
$serverName = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? '';
$isLocal = (
    $serverName === 'localhost' || 
    $serverName === '127.0.0.1' || 
    strpos($hostname, 'HP-EliteBook') !== false ||
    strpos($hostname, 'DESKTOP') !== false ||
    strpos($hostname, 'LAPTOP') !== false ||
    file_exists('/home/e/Gebeta/.env.local')
);

// Choose database based on environment
if ($isLocal) {
    // Use local MySQL
    $rawDbHost = env_get('DB_HOST', 'localhost');
    $dbPort = (int)env_get('DB_PORT', 3306);
    $dbName = env_get('DB_NAME', 'gebeta');
    $dbUser = env_get('DB_USER', 'root');
    $dbPass = env_get('DB_PASS', '');
    $environment = 'development';
} else {
    // Use Aiven cloud
    $rawDbHost = env_get('DB_HOST_AIVEN', 'gebeta-db-gebeta.a.aivencloud.com:23863');
    $dbPort = (int)env_get('DB_PORT_AIVEN', 23863);
    $dbName = env_get('DB_NAME_AIVEN', 'defaultdb');
    $dbUser = env_get('DB_USER_AIVEN', 'avnadmin');
    $dbPass = env_get('DB_PASS_AIVEN', 'AVNS_AcTxZFvGTBqvOJcYhPY');
    $environment = 'production';
}

// Parse host:port format
$dbHost = $rawDbHost;
if (strpos($rawDbHost, ':') !== false) {
    $parts = explode(':', $rawDbHost, 2);
    $dbHost = $parts[0];
    $dbPort = (int)$parts[1];
}

// Database constants
define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('ENVIRONMENT', $environment);
define('SITE_NAME', 'Gebeta');
define('BASE_URL', env_get('BASE_URL', 'http://localhost:7844'));

// Brevo API Configuration
define('BREVO_API_KEY', env_get('BREVO_API_KEY', ''));

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', env_get('GOOGLE_CLIENT_ID', 'YOUR_ACTUAL_CLIENT_ID.apps.googleusercontent.com'));

// Directory constants for media management
define('ROOT_DIR', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_DIR . '/uploads/');
define('UPLOAD_DIR_POSTS', UPLOAD_DIR . 'posts/');
define('UPLOAD_DIR_RESTAURANTS', UPLOAD_DIR . 'restaurants/');
define('UPLOAD_DIR_MENU', UPLOAD_DIR . 'menu/');

// Log which database we're using
error_log("Using database: " . DB_HOST . ":" . DB_PORT . "/" . DB_NAME . " (" . ENVIRONMENT . ")");
?>
