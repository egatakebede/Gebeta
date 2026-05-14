<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['restaurant']);
require_once __DIR__ . '/../includes/db.php';

$orderId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT o.*, u.name AS customer_name, r.name AS restaurant_name, r.user_id AS restaurant_user FROM orders o JOIN users u ON o.user_id = u.id JOIN restaurants r ON o.restaurant_id = r.id WHERE o.id = ? LIMIT 1');
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order || $order['restaurant_user'] !== $_SESSION['user']['id']) {
    redirect('/restaurant/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $status = $_POST['status'] ?? $order['status'];
    $allowed = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'cancelled'];
    if (in_array($status, $allowed, true)) {
        $update = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $update->execute([$status, $orderId]);
        redirect('/restaurant/order-detail.php?id=' . $orderId);
    }
}

$itemStmt = $pdo->prepare('SELECT oi.*, mi.name FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id WHERE oi.order_id = ?');
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
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
        <a class="pill-button" href="/restaurant/dashboard.php">Back</a>
    </header>
    <main class="page-content">
        <section class="detail-card">
            <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>
            <p><strong>Payment:</strong> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['payment_method']))) ?></p>
        </section>
        <section class="detail-card">
            <h2>Items ordered</h2>
            <?php foreach ($items as $item): ?>
                <div class="detail-line">
                    <span><?= $item['quantity'] ?>x <?= htmlspecialchars($item['name']) ?></span>
                    <strong><?= format_price($item['price'] * $item['quantity']) ?></strong>
                </div>
            <?php endforeach; ?>
            <hr>
            <div class="detail-line"><span>Total</span><strong><?= format_price($order['total_amount'] + $order['delivery_fee']) ?></strong></div>
        </section>
        <section class="detail-card">
            <h2>Update status</h2>
            <form method="post">
                <?= csrf_field() ?>
                    <?php foreach (['pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled'] as $status): ?>
                        <option value="<?= $status ?>" <?= $status === $order['status'] ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $status)) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="primary-btn" type="submit">Update status</button>
            </form>
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
