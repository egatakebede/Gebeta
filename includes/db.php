<?php
require_once __DIR__ . '/config.php';

$pdo = null;

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    if (php_sapi_name() !== 'cli') {
        http_response_code(503);
        die('Database connection failed. Please check your configuration.');
    }
}
