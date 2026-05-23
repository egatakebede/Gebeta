<?php
header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

try {
    // Check PHP version
    $health['checks']['php'] = [
        'status' => 'ok',
        'version' => PHP_VERSION
    ];
    
    // Check if config loads
    require_once __DIR__ . '/includes/config.php';
    $health['checks']['config'] = ['status' => 'ok'];
    
    // Check database connection
    require_once __DIR__ . '/includes/db.php';
    $pdo->query('SELECT 1');
    $health['checks']['database'] = [
        'status' => 'ok',
        'host' => DB_HOST,
        'name' => DB_NAME
    ];
    
    // Check tables exist
    $tables = ['users', 'restaurants', 'orders', 'menu_items'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $count = $stmt->fetchColumn();
        $health['checks']['tables'][$table] = $count;
    }
    
    // Check sessions
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $health['checks']['session'] = ['status' => 'ok'];
    
    // Check writable directories
    $dirs = ['uploads/restaurants', 'uploads/menu'];
    foreach ($dirs as $dir) {
        if (file_exists($dir)) {
            $health['checks']['directories'][$dir] = is_writable($dir) ? 'writable' : 'not writable';
        } else {
            $health['checks']['directories'][$dir] = 'not exists';
        }
    }
    
} catch (Exception $e) {
    $health['status'] = 'error';
    $health['error'] = $e->getMessage();
    $health['file'] = $e->getFile();
    $health['line'] = $e->getLine();
    http_response_code(500);
}

echo json_encode($health, JSON_PRETTY_PRINT);
