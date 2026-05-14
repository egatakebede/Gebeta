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

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
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
