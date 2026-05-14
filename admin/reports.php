<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query('SELECT DATE(created_at) AS day, COUNT(*) AS orders, SUM(total_amount + delivery_fee) AS revenue FROM orders GROUP BY DATE(created_at) ORDER BY DATE(created_at) DESC LIMIT 7');
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>Revenue Reports</h1>
        <a class="pill-button" href="/admin/dashboard.php">Dashboard</a>
    </header>
    <main class="page-content">
        <?php if (empty($reports)): ?>
            <div class="empty-state">No report data available.</div>
        <?php else: ?>
            <?php foreach ($reports as $row): ?>
                <div class="report-line">
                    <span><?= htmlspecialchars($row['day']) ?></span>
                    <span><?= $row['orders'] ?> orders</span>
                    <strong><?= number_format($row['revenue'], 2) ?> Birr</strong>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
