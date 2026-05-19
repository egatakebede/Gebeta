<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['restaurant']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE user_id = ? LIMIT 1');
$stmt->execute([$_SESSION['user']['id']]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$restaurant) {
    redirect('/restaurant/setup.php');
}

if ($restaurant['status'] === 'pending' || $restaurant['status'] === 'suspended') {
    include __DIR__ . '/pending-dashboard.php';
    exit;
}

// TODAY'S PERFORMANCE METRICS
$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND DATE(created_at) = CURDATE()');
$stmt->execute([$restaurant['id']]);
$todayOrders = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)');
$stmt->execute([$restaurant['id']]);
$yesterdayOrders = $stmt->fetchColumn();
$ordersChange = $yesterdayOrders > 0 ? round((($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100) : 0;

$stmt = $pdo->prepare('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE restaurant_id = ? AND status IN (?,?,?) AND DATE(created_at) = CURDATE()');
$stmt->execute([$restaurant['id'], 'delivered', 'ready', 'preparing']);
$todayRevenue = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND status = ?');
$stmt->execute([$restaurant['id'], 'pending']);
$pendingOrders = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND status = ? AND DATE(created_at) = CURDATE()');
$stmt->execute([$restaurant['id'], 'delivered']);
$completedToday = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND status = ? AND DATE(created_at) = CURDATE()');
$stmt->execute([$restaurant['id'], 'cancelled']);
$cancelledToday = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) FROM orders WHERE restaurant_id = ? AND status = ? AND DATE(created_at) = CURDATE()');
$stmt->execute([$restaurant['id'], 'delivered']);
$avgDeliveryTime = round($stmt->fetchColumn() ?: 0);

$stmt = $pdo->prepare('SELECT SUM(oi.quantity) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.restaurant_id = ? AND DATE(o.created_at) = CURDATE()');
$stmt->execute([$restaurant['id']]);
$itemsSold = $stmt->fetchColumn() ?: 0;

// GET ORDERS BY STATUS
$stmt = $pdo->prepare('SELECT o.*, u.name as customer_name, u.phone as customer_phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.restaurant_id = ? AND o.status = ? ORDER BY o.created_at DESC');
$stmt->execute([$restaurant['id'], 'pending']);
$newOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt->execute([$restaurant['id'], 'preparing']);
$preparingOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt->execute([$restaurant['id'], 'ready']);
$readyOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$recentOrders = array_merge($newOrders, $preparingOrders, $readyOrders);

// TOP SELLING ITEMS
$stmt = $pdo->prepare('SELECT mi.name, SUM(oi.quantity) as total_sold FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id JOIN orders o ON oi.order_id = o.id WHERE o.restaurant_id = ? AND DATE(o.created_at) = CURDATE() GROUP BY mi.id ORDER BY total_sold DESC LIMIT 5');
$stmt->execute([$restaurant['id']]);
$topItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// RECENT REVIEWS
$stmt = $pdo->prepare('SELECT rr.rating, rr.comment, u.name, rr.created_at FROM restaurant_ratings rr JOIN users u ON rr.user_id = u.id WHERE rr.restaurant_id = ? ORDER BY rr.created_at DESC LIMIT 3');
$stmt->execute([$restaurant['id']]);
$recentReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <h1>Hawassa <?= htmlspecialchars($restaurant['name']) ?></h1>
        <div style="display:flex;gap:8px;">
            <span class="status-badge" style="background:<?= $restaurant['status'] === 'active' ? '#E8F5E9' : '#FFF3E0' ?>;color:<?= $restaurant['status'] === 'active' ? '#2E7D32' : '#F57C00' ?>"><?= ucfirst($restaurant['status']) ?></span>
            <a class="pill-button" href="/restaurant/menu.php">Menu</a>
        </div>
    </header>
    <main class="page-content">
        <section class="stats-grid">
            <div class="stat-card"><strong><?= $todayOrders ?></strong><span>Today's orders</span></div>
            <div class="stat-card"><strong><?= number_format($todayRevenue, 2) ?> Birr</strong><span>Revenue</span></div>
            <div class="stat-card"><strong><?= $pendingOrders ?></strong><span>Pending orders</span></div>
            <div class="stat-card"><strong><?= $completedToday ?></strong><span>Completed today</span></div>
            <div class="stat-card"><strong><?= $cancelledToday ?></strong><span>Cancelled today</span></div>
            <div class="stat-card"><strong><?= $avgDeliveryTime ?> min</strong><span>Avg delivery time</span></div>
        </section>

        <section class="orders-list">
            <div class="section-header"><h2>Recent orders</h2></div>
            <?php if (empty($recentOrders)): ?>
                <div class="empty-state">No recent orders yet.</div>
            <?php else: ?>
                <?php foreach ($recentOrders as $order): ?>
                    <a class="order-card" href="/restaurant/order-detail.php?id=<?= $order['id'] ?>">
                        <div>
                            <h3>#<?= htmlspecialchars($order['order_number']) ?></h3>
                            <p><?= htmlspecialchars($order['customer_name']) ?> · <?= htmlspecialchars($order['created_at']) ?></p>
                        </div>
                        <span class="status-badge <?= $order['status'] === 'pending' ? '' : ($order['status'] === 'ready' ? 'active' : '') ?>"><?= htmlspecialchars(str_replace('_', ' ', $order['status'])) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="restaurants-section">
            <div class="section-header"><h2>Top selling items</h2></div>
            <?php if (empty($topItems)): ?>
                <div class="empty-state">No sales data yet for today.</div>
            <?php else: ?>
                <?php foreach ($topItems as $item): ?>
                    <div class="admin-card">
                        <div>
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p><?= htmlspecialchars($item['total_sold']) ?> sold today</p>
                        </div>
                        <span class="status-badge">Top seller</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="restaurants-section">
            <div class="section-header"><h2>Recent reviews</h2></div>
            <?php if (empty($recentReviews)): ?>
                <div class="empty-state">No reviews yet.</div>
            <?php else: ?>
                <?php foreach ($recentReviews as $review): ?>
                    <div class="admin-card">
                        <div>
                            <h3><?= htmlspecialchars($review['name']) ?></h3>
                            <p><?= htmlspecialchars(substr($review['comment'], 0, 120)) ?><?= strlen($review['comment']) > 120 ? '…' : '' ?></p>
                        </div>
                        <span class="status-badge"><?= htmlspecialchars($review['rating']) ?> Rating</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
    <footer class="bottom-bar">
        <a href="/restaurant/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <span>Home</span>
            <span>Dashboard</span>
        </a>
        <a href="/restaurant/menu.php" class="<?= basename($_SERVER['PHP_SELF']) === 'menu.php' ? 'active' : '' ?>">
            <span>Menu</span>
            <span>Menu</span>
        </a>
        <a href="/restaurant/posts.php" class="<?= basename($_SERVER['PHP_SELF']) === 'posts.php' ? 'active' : '' ?>">
            <span>Online</span>
            <span>Posts</span>
        </a>
        <a href="/restaurant/profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
            <span>Profile</span>
            <span>Profile</span>
        </a>
    </footer>
</body>
</html>
