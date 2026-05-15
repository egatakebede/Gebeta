<?php
require_once __DIR__ . '/config.php';

function get_db() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(503);
        die('<!DOCTYPE html><html><head><title>Service Unavailable</title></head><body><h1>Database Connection Failed</h1><p>Unable to connect to database. Please contact support.</p><pre>' . htmlspecialchars($e->getMessage()) . '</pre></body></html>');
    }
}

$pdo = get_db();
