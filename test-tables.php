<?php
require_once __DIR__ . '/includes/db.php';

$tables = ['users', 'restaurants', 'orders', 'dishes', 'categories', 'order_items', 'reviews', 'delivery_requests', 'support_tickets', 'system_logs'];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✓ $table: $count rows\n";
    } catch (PDOException $e) {
        echo "✗ $table: " . $e->getMessage() . "\n";
    }
}
?>
