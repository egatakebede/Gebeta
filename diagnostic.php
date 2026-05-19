<?php
// Diagnostic page to check deployment status
header('Content-Type: text/plain');

echo "=== Gebeta Deployment Diagnostics ===\n\n";

// Check PHP version
echo "PHP Version: " . PHP_VERSION . "\n";

// Check if files exist
$files = [
    '/restaurant/dashboard.php',
    '/restaurant/pending-dashboard.php',
    '/admin/restaurants.php',
    '/includes/db.php',
    '/includes/auth.php',
    '/includes/functions.php'
];

echo "\n=== File Check ===\n";
foreach ($files as $file) {
    $path = __DIR__ . $file;
    echo $file . ": " . (file_exists($path) ? "EXISTS" : "MISSING") . "\n";
}

// Check database connection
echo "\n=== Database Connection ===\n";
try {
    require_once __DIR__ . '/includes/config.php';
    echo "Config loaded: OK\n";
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_PORT: " . DB_PORT . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
    
    require_once __DIR__ . '/includes/db.php';
    echo "Database connection: SUCCESS\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM restaurants");
    $count = $stmt->fetchColumn();
    echo "Restaurants count: " . $count . "\n";
} catch (Exception $e) {
    echo "Database connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
}

// Check latest git commit
echo "\n=== Git Info ===\n";
if (file_exists(__DIR__ . '/.git/refs/heads/main')) {
    $commit = trim(file_get_contents(__DIR__ . '/.git/refs/heads/main'));
    echo "Latest commit: " . substr($commit, 0, 7) . "\n";
} else {
    echo "Git info: Not available\n";
}

echo "\n=== End Diagnostics ===\n";
