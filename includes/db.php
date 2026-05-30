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
            PDO::ATTR_TIMEOUT => 30,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        // Add SSL for Aiven cloud connection
        if (strpos(DB_HOST, 'aivencloud.com') !== false) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = null;
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Ensure DB timezone matches PHP timezone for OTP expiry checks
        $pdo->exec("SET time_zone = '+03:00'"); // Africa/Addis_Ababa
        
        // Set timezone
        $pdo->exec("SET NAMES utf8mb4");
        
        return $pdo;
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        error_log('DB_HOST: ' . DB_HOST . ':' . DB_PORT);
        error_log('DB_NAME: ' . DB_NAME);
        error_log('DB_USER: ' . DB_USER);
        
        die('<!DOCTYPE html><html><head><title>Database Connection Error</title><style>
            body{font-family:Arial,sans-serif;background:#f5f5f5;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
            .error-box{background:#fff;border-radius:12px;padding:30px;max-width:500px;box-shadow:0 4px 20px rgba(0,0,0,0.1);border-left:4px solid #FC8019}
            h2{color:#FC8019;margin-bottom:10px}
            p{margin:10px 0;color:#666}
            .details{background:#f9f9f9;padding:15px;border-radius:8px;margin-top:15px;font-family:monospace;font-size:12px}
        </style></head>
        <body>
            <div class="error-box">
                <h2>🔌 Database Connection Failed</h2>
                <p>Unable to connect to the database.</p>
                <div class="details">
                    <strong>Environment:</strong> ' . ENVIRONMENT . '<br>
                    <strong>Host:</strong> ' . DB_HOST . ':' . DB_PORT . '<br>
                    <strong>Database:</strong> ' . DB_NAME . '<br>
                    <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
                </div>
                <p><small>Please check your database configuration.</small></p>
            </div>
        </body></html>');
    }
}

$pdo = get_db();
?>
