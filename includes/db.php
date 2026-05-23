<?php
require_once __DIR__ . '/config.php';

function get_db() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            PDO::ATTR_TIMEOUT => 5
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        error_log('DB_HOST: ' . DB_HOST . ':' . DB_PORT);
        error_log('DB_NAME: ' . DB_NAME);
        error_log('DB_USER: ' . DB_USER);
        
        http_response_code(503);
        die('<!DOCTYPE html><html><head><title>Service Unavailable</title><style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}h1{color:#d32f2f;}pre{background:#f5f5f5;padding:15px;border-radius:5px;overflow-x:auto;}</style></head><body><h1>Database Connection Failed</h1><p>Unable to connect to database. Please check your configuration.</p><details><summary>Error Details</summary><pre>' . htmlspecialchars($e->getMessage()) . '</pre></details><p><strong>Connection Info:</strong></p><ul><li>Host: ' . htmlspecialchars(DB_HOST) . ':' . DB_PORT . '</li><li>Database: ' . htmlspecialchars(DB_NAME) . '</li><li>User: ' . htmlspecialchars(DB_USER) . '</li></ul></body></html>');
    }
}

$pdo = get_db();
