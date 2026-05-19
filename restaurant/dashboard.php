<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['restaurant']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE user_id = ? LIMIT 1');
$stmt->execute([$_SESSION['user']['id']]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$restaurant) {
    // New restaurant owner - redirect to setup
    redirect('/restaurant/setup.php');
}

// If restaurant is pending or suspended, show limited dashboard
if ($restaurant['status'] === 'pending' || $restaurant['status'] === 'suspended') {
    include __DIR__ . '/pending-dashboard.php';
    exit;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND DATE(created_at) = CURDATE()');
$stmt->execute([$restaurant['id']]);
$todayOrders = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE restaurant_id = ? AND DATE(created_at) = CURDATE()');
$stmt->execute([$restaurant['id']]);
$todayRevenues = $stmt->fetchColumn();
$todayRevenues = $todayRevenues ? $todayRevenues : 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND status = "pending"');
$stmt->execute([$restaurant['id']]);
$pendingOrders = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT id, order_number, status, created_at FROM orders WHERE restaurant_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$restaurant['id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Dashboard · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>🏪 <?= htmlspecialchars($restaurant['name']) ?></h1>
        <div style="display:flex;gap:8px;">
            <span class="status-badge" style="background:<?= $restaurant['status'] === 'active' ? '#E8F5E9' : '#FFF3E0' ?>;color:<?= $restaurant['status'] === 'active' ? '#2E7D32' : '#F57C00' ?>"><?= ucfirst($restaurant['status']) ?></span>
            <a class="pill-button" href="/restaurant/menu.php">Menu</a>
        </div>
    </header>
    <main class="page-content">
        <section class="stats-grid">
            <div class="stat-card"><strong><?= $todayOrders ?></strong><span>Today's orders</span></div>
            <div class="stat-card"><strong><?= number_format($todayRevenues, 2) ?> Birr</strong><span>Revenue</span></div>
            <div class="stat-card"><strong><?= $pendingOrders ?></strong><span>Pending orders</span></div>
            <div class="stat-card"><strong><?= number_format($restaurant['rating'], 1) ?> ⭐</strong><span>Rating</span></div>
        </section>
        <section class="orders-list">
            <div class="section-header"><h2>Recent orders</h2></div>
            <?php if (empty($orders)): ?>
                <div class="empty-state">No recent orders yet.</div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <a class="order-card" href="/restaurant/order-detail.php?id=<?= $order['id'] ?>">
                        <div>
                            <h3>#<?= htmlspecialchars($order['order_number']) ?></h3>
                            <p><?= htmlspecialchars($order['created_at']) ?></p>
                        </div>
                        <span class="status-badge"><?= htmlspecialchars(str_replace('_', ' ', $order['status'])) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
    <footer class="bottom-bar">
        <a href="/restaurant/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <span>🏠</span>
            <span>Dashboard</span>
        </a>
        <a href="/restaurant/menu.php" class="<?= basename($_SERVER['PHP_SELF']) === 'menu.php' ? 'active' : '' ?>">
            <span>🍽️</span>
            <span>Menu</span>
        </a>
        <a href="/restaurant/posts.php" class="<?= basename($_SERVER['PHP_SELF']) === 'posts.php' ? 'active' : '' ?>">
            <span>📱</span>
            <span>Posts</span>
        </a>
        <a href="/restaurant/profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
            <span>👤</span>
            <span>Profile</span>
        </a>
    </footer>
</body>
</html>
