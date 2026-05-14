<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

$totalRestaurants = $pdo->query('SELECT COUNT(*) FROM restaurants')->fetchColumn();
$totalOrders = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalUsers = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$stmt = $pdo->query('SELECT SUM(total_amount + delivery_fee) FROM orders');
$revenue = $stmt->fetchColumn() ?: 0;
$recentOrders = $pdo->query('SELECT o.order_number, o.status, r.name AS restaurant_name FROM orders o JOIN restaurants r ON o.restaurant_id = r.id ORDER BY o.created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
$topRestaurants = $pdo->query('SELECT name, rating FROM restaurants ORDER BY rating DESC LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>Admin Dashboard</h1>
        <a class="pill-button" href="/logout.php">Logout</a>
    </header>
    <main class="page-content">
        <section class="stats-grid">
            <div class="stat-card"><strong><?= $totalRestaurants ?></strong><span>Total restaurants</span></div>
            <div class="stat-card"><strong><?= $totalOrders ?></strong><span>Total orders</span></div>
            <div class="stat-card"><strong><?= $totalUsers ?></strong><span>Total users</span></div>
            <div class="stat-card"><strong><?= number_format($revenue, 2) ?> Birr</strong><span>Revenue</span></div>
        </section>
        <section class="report-card">
            <h2>Top restaurants</h2>
            <?php foreach ($topRestaurants as $row): ?>
                <div class="detail-line"><span><?= htmlspecialchars($row['name']) ?></span><span>⭐ <?= number_format($row['rating'], 1) ?></span></div>
            <?php endforeach; ?>
        </section>
        <section class="report-card">
            <h2>Recent activity</h2>
            <?php foreach ($recentOrders as $order): ?>
                <div class="detail-line"><span>Order <?= htmlspecialchars($order['order_number']) ?></span><span><?= htmlspecialchars(str_replace('_', ' ', $order['status'])) ?> at <?= htmlspecialchars($order['restaurant_name']) ?></span></div>
            <?php endforeach; ?>
        </section>
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
