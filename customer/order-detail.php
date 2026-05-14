<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT o.*, r.name AS restaurant_name, r.location AS restaurant_location FROM orders o JOIN restaurants r ON o.restaurant_id = r.id WHERE o.id = ? AND o.user_id = ? LIMIT 1');
$stmt->execute([$id, $_SESSION['user']['id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    redirect('/customer/orders.php');
}

$itemStmt = $pdo->prepare('SELECT oi.*, mi.name FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id WHERE oi.order_id = ?');
$itemStmt->execute([$id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
$states = ['pending' => 'Order placed', 'confirmed' => 'Order confirmed', 'preparing' => 'Preparing your order', 'ready' => 'Ready for delivery', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered'];
$cartCount = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= htmlspecialchars($order['order_number']) ?> · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>Order #<?= htmlspecialchars($order['order_number']) ?></h1>
        <a class="pill-button" href="/customer/orders.php">Back</a>
    </header>
    <main class="page-content">
        <section class="track-card">
            <h2><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></h2>
            <p>Estimated delivery within 20 minutes</p>
        </section>

        <section class="timeline-card">
            <?php foreach ($states as $key => $label): ?>
                <div class="timeline-item <?= $key === $order['status'] ? 'active' : ($key === 'delivered' && $order['status'] === 'delivered' ? 'active' : '') ?>">
                    <span class="timeline-dot"></span>
                    <div>
                        <strong><?= htmlspecialchars($label) ?></strong>
                        <p><?= $key === $order['status'] ? 'Current status' : 'Waiting update' ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="detail-card">
            <h2>Order details</h2>
            <?php foreach ($items as $item): ?>
                <div class="detail-line">
                    <span><?= $item['quantity'] ?>x <?= htmlspecialchars($item['name']) ?></span>
                    <strong><?= format_price($item['price'] * $item['quantity']) ?></strong>
                </div>
            <?php endforeach; ?>
            <hr>
            <div class="detail-line"><span>Delivery address</span><span><?= htmlspecialchars($order['delivery_address']) ?></span></div>
            <div class="detail-line"><span>Payment</span><span><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['payment_method']))) ?></span></div>
            <div class="detail-line"><span>Total</span><span><?= format_price($order['total_amount'] + $order['delivery_fee']) ?></span></div>
        </section>
    </main>
    <footer class="bottom-bar">
        <a href="/customer/dashboard.php">🏠 Home</a>
        <a href="/customer/cart.php">🛒 Cart (<?= $cartCount ?>)</a>
        <a href="/customer/orders.php">📄 Orders</a>
        <a href="/customer/profile.php">👤 Profile</a>
    </footer>
</body>
</html>
