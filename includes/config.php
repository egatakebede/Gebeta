<?php
// Load .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

// Parse DB_HOST to handle host:port format
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = 3306;
if (str_contains($dbHost, ':')) {
    [$dbHost, $dbPort] = explode(':', $dbHost, 2);
}

define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_NAME', getenv('DB_NAME') ?: 'gebeta');
define('DB_USER', getenv('DB_USER') ?: 'gebeta');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('BREVO_API_KEY',      getenv('BREVO_API_KEY')      ?: '');
define('BREVO_SENDER_EMAIL', getenv('BREVO_SENDER_EMAIL') ?: 'noreply@gebeta.com');
define('BREVO_SENDER_NAME',  'Gebeta');

define('SITE_NAME', 'Gebeta');
define('BASE_URL',  '');
define('UPLOAD_DIR_RESTAURANTS', 'uploads/restaurants/');
define('UPLOAD_DIR_MENU',        'uploads/menu/');
