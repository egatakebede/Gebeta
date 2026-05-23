<?php
// Database Connection Test
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Gebeta Database Connection Test</h1>";

try {
    require_once __DIR__ . '/includes/config.php';
    echo "<p>✅ Config loaded</p>";
    
    echo "<h2>Database Configuration:</h2>";
    echo "<ul>";
    echo "<li>Host: " . DB_HOST . "</li>";
    echo "<li>Port: " . DB_PORT . "</li>";
    echo "<li>Database: " . DB_NAME . "</li>";
    echo "<li>User: " . DB_USER . "</li>";
    echo "</ul>";
    
    require_once __DIR__ . '/includes/db.php';
    echo "<p>✅ Database connected successfully!</p>";
    
    // Test query
    echo "<h2>Test Accounts:</h2>";
    $stmt = $pdo->query("SELECT id, name, email, role, status FROM users ORDER BY id LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<p>⚠️ No users found in database. Please import gebeta.sql</p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Test Login Credentials:</h3>";
        echo "<ul>";
        echo "<li><strong>Admin:</strong> admin@gebeta.com / password123</li>";
        echo "<li><strong>Restaurant:</strong> yod@restaurant.com / password123</li>";
        echo "<li><strong>Customer:</strong> customer@test.com / password123</li>";
        echo "</ul>";
    }
    
    // Check tables
    echo "<h2>Database Tables:</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $count = $countStmt->fetchColumn();
        echo "<li><strong>$table</strong>: $count rows</li>";
    }
    echo "</ul>";
    
    echo "<p style='color: green; font-weight: bold;'>✅ All checks passed! Database is working correctly.</p>";
    echo "<p><a href='/index.php'>← Back to Home</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ol>";
    echo "<li>Check if MySQL is running</li>";
    echo "<li>Verify .env file exists and has correct credentials</li>";
    echo "<li>Import gebeta.sql: <code>mysql -u root -p &lt; gebeta.sql</code></li>";
    echo "<li>Check database exists: <code>mysql -u root -p -e 'SHOW DATABASES;'</code></li>";
    echo "</ol>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
