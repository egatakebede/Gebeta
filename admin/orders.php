<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query('SELECT o.order_number, o.status, o.total_amount, u.name AS customer_name, r.name AS restaurant_name FROM orders o JOIN users u ON o.user_id = u.id JOIN restaurants r ON o.restaurant_id = r.id ORDER BY o.created_at DESC LIMIT 20');
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Orders · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>All Orders</h1>
        <a class="pill-button" href="/admin/dashboard.php">Dashboard</a>
    </header>
    <main class="page-content">
        <?php foreach ($orders as $order): ?>
            <div class="admin-card">
                <div>
                    <h3><?= htmlspecialchars($order['order_number']) ?></h3>
                    <p><?= htmlspecialchars($order['customer_name']) ?> → <?= htmlspecialchars($order['restaurant_name']) ?></p>
                    <p><?= htmlspecialchars(str_replace('_', ' ', $order['status'])) ?></p>
                </div>
                <div class="admin-actions">
                    <strong><?= format_price($order['total_amount']) ?></strong>
                </div>
            </div>
        <?php endforeach; ?>
    </main>
    <footer class="bottom-bar">
        <a href="/admin/dashboard.php">Dashboard</a>
        <a href="/admin/restaurants.php">Restaurants</a>
        <a href="/admin/users.php">Users</a>
        <a href="/admin/orders.php">Orders</a>
        <a href="/admin/reports.php">Reports</a>
    </footer>
</body>
</html>
