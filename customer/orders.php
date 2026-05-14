<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->prepare('SELECT o.*, r.name AS restaurant_name FROM orders o JOIN restaurants r ON o.restaurant_id = r.id WHERE o.user_id = ? ORDER BY o.created_at DESC');
$stmt->execute([$_SESSION['user']['id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cartCount = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>Orders</h1>
        <a class="pill-button" href="/customer/dashboard.php">Browse</a>
    </header>
    <main class="page-content">
        <?php if (empty($orders)): ?>
            <div class="empty-state">You have no orders yet.</div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <a class="order-card" href="/customer/order-detail.php?id=<?= $order['id'] ?>">
                    <div>
                        <h3>Order #<?= htmlspecialchars($order['order_number']) ?></h3>
                        <p><?= htmlspecialchars($order['restaurant_name']) ?> • <?= htmlspecialchars($order['created_at']) ?></p>
                    </div>
                    <div>
                        <strong><?= format_price($order['total_amount'] + $order['delivery_fee']) ?></strong>
                        <span class="status-badge"><?= htmlspecialchars(str_replace('_', ' ', $order['status'])) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    <footer class="bottom-bar">
        <a href="/customer/dashboard.php">🏠 Home</a>
        <a href="/customer/cart.php">🛒 Cart (<?= $cartCount ?>)</a>
        <a href="/customer/orders.php">📄 Orders</a>
        <a href="/customer/profile.php">👤 Profile</a>
    </footer>
</body>
</html>
