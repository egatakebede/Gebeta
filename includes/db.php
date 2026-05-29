<?php
require_once __DIR__ . "/config.php";

function get_db() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 30
        ];
        
        if (strpos(DB_HOST, "aivencloud.com") !== false) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("DB connection failed: " . $e->getMessage());
        die("Database connection failed. Please check your configuration.");
    }
}

$pdo = get_db();
?>
