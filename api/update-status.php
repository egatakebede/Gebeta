<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

$orderId = (int)($_POST['order_id'] ?? 0);
$status = $_POST['status'] ?? '';
$allowed = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'cancelled'];
if ($orderId <= 0 || !in_array($status, $allowed, true)) {
    flash_set('error', 'Invalid order status');
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

$orderStmt = $pdo->prepare('SELECT o.*, r.user_id AS restaurant_user_id FROM orders o JOIN restaurants r ON o.restaurant_id = r.id WHERE o.id = ?');
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    flash_set('error', 'Order not found');
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

if ($_SESSION['user']['role'] === 'restaurant' && $_SESSION['user']['id'] !== $order['restaurant_user_id']) {
    flash_set('error', 'Unauthorized');
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

$update = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
$update->execute([$status, $orderId]);
flash_set('success', 'Order status updated.');
redirect($_SERVER['HTTP_REFERER'] ?? '/');
