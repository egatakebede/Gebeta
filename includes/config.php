<?php
// Load .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $trimmed = trim($line);
        if (empty($trimmed) || $trimmed[0] === '#' || strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

function env_get(string $key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

// Parse DB_HOST to handle host:port format
$rawDbHost = getenv('DB_HOST');
$dbHost = $rawDbHost !== false ? $rawDbHost : 'localhost';
$dbPort = 3306;
if ($dbHost && strpos($dbHost, ':') !== false) {
    [$dbHost, $dbPort] = explode(':', $dbHost, 2);
}

define('DB_HOST', $dbHost);
define('DB_HOST_RAW', $rawDbHost);
define('DB_PORT', (int)$dbPort);
define('DB_NAME', env_get('DB_NAME', 'gebeta'));
define('DB_NAME_RAW', getenv('DB_NAME'));
define('DB_USER', env_get('DB_USER', 'root'));
define('DB_USER_RAW', getenv('DB_USER'));
define('DB_PASS', env_get('DB_PASS', ''));

define('BREVO_API_KEY',      getenv('BREVO_API_KEY')      ?: '');
define('BREVO_SENDER_EMAIL', getenv('BREVO_SENDER_EMAIL') ?: 'noreply@gebeta.com');
define('BREVO_SENDER_NAME',  'Gebeta');

define('GOOGLE_CLIENT_ID',     getenv('GOOGLE_CLIENT_ID')     ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');

define('SITE_NAME', 'Gebeta');
define('BASE_URL',  '');
define('UPLOAD_DIR_RESTAURANTS', 'uploads/restaurants/');
define('UPLOAD_DIR_MENU',        'uploads/menu/');
