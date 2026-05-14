<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['restaurant']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE user_id = ? LIMIT 1');
$stmt->execute([$_SESSION['user']['id']]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$restaurant) {
    redirect('/');
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ?');
$stmt->execute([$restaurant['id']]);
$totalOrders = $stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE restaurant_id = ?');
$stmt->execute([$restaurant['id']]);
$revenue = $stmt->fetchColumn() ?: 0;
$stmt = $pdo->prepare('SELECT status, COUNT(*) AS count FROM orders WHERE restaurant_id = ? GROUP BY status');
$stmt->execute([$restaurant['id']]);
$statusBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cartCount = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>Analytics</h1>
        <a class="pill-button" href="/restaurant/dashboard.php">Dashboard</a>
    </header>
    <main class="page-content">
        <section class="stats-grid">
            <div class="stat-card"><strong><?= $totalOrders ?></strong><span>Total orders</span></div>
            <div class="stat-card"><strong><?= number_format($revenue, 2) ?> Birr</strong><span>Revenue</span></div>
        </section>
        <section class="report-card">
            <h2>Order status breakdown</h2>
            <?php foreach ($statusBreakdown as $row): ?>
                <div class="detail-line"><span><?= htmlspecialchars(str_replace('_', ' ', $row['status'])) ?></span><span><?= $row['count'] ?></span></div>
            <?php endforeach; ?>
        </section>
    </main>
    <footer class="bottom-bar">
        <a href="/restaurant/dashboard.php">🏠 Dashboard</a>
        <a href="/restaurant/menu.php">🍽️ Menu</a>
        <a href="/restaurant/analytics.php">📊 Analytics</a>
        <a href="/restaurant/profile.php">👤 Profile</a>
    </footer>
</body>
</html>
