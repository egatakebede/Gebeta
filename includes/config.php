<?php
// Load .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed) || $trimmed[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remove quotes if present
        $value = trim($value, '"\'');
        
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function env_get(string $key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

// Parse DB_HOST to handle host:port format
$rawDbHost = env_get('DB_HOST');
$dbHost = $rawDbHost !== false ? $rawDbHost : 'localhost';
$dbPort = 3306;

if ($dbHost && strpos($dbHost, ':') !== false) {
    $parts = explode(':', $dbHost, 2);
    $dbHost = $parts[0];
    $dbPort = (int)$parts[1];
}

// Database constants - Check if not already defined
if (!defined('DB_HOST')) define('DB_HOST', $dbHost);
if (!defined('DB_HOST_RAW')) define('DB_HOST_RAW', $rawDbHost);
if (!defined('DB_PORT')) define('DB_PORT', $dbPort);
if (!defined('DB_NAME')) define('DB_NAME', env_get('DB_NAME', 'gebeta'));
if (!defined('DB_NAME_RAW')) define('DB_NAME_RAW', getenv('DB_NAME'));
if (!defined('DB_USER')) define('DB_USER', env_get('DB_USER', 'root'));
if (!defined('DB_USER_RAW')) define('DB_USER_RAW', getenv('DB_USER'));
if (!defined('DB_PASS')) define('DB_PASS', env_get('DB_PASS', ''));

// Brevo Email API
if (!defined('BREVO_API_KEY')) define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: '');
if (!defined('BREVO_SENDER_EMAIL')) define('BREVO_SENDER_EMAIL', getenv('BREVO_SENDER_EMAIL') ?: 'noreply@gebeta.com');
if (!defined('BREVO_SENDER_NAME')) define('BREVO_SENDER_NAME', 'Gebeta');

// Google OAuth - Check if not already defined
if (!defined('GOOGLE_CLIENT_ID')) define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
if (!defined('GOOGLE_CLIENT_SECRET')) define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');

// Site constants
if (!defined('SITE_NAME')) define('SITE_NAME', 'Gebeta');
if (!defined('BASE_URL')) define('BASE_URL', '');
if (!defined('UPLOAD_DIR_RESTAURANTS')) define('UPLOAD_DIR_RESTAURANTS', 'uploads/restaurants/');
if (!defined('UPLOAD_DIR_MENU')) define('UPLOAD_DIR_MENU', 'uploads/menu/');
if (!defined('UPLOAD_DIR_POSTS')) define('UPLOAD_DIR_POSTS', 'uploads/posts/');
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'development');
?>
