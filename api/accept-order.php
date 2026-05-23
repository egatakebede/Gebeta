<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!is_logged_in() || $_SESSION['user']['role'] !== 'restaurant') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');
$action = trim($_POST['action'] ?? ''); // 'accept' or 'reject'

$validStatuses = ['confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'cancelled'];

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Get restaurant ID for this user
$stmt = $pdo->prepare('SELECT id FROM restaurants WHERE user_id = ? LIMIT 1');
$stmt->execute([$_SESSION['user']['id']]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$restaurant) {
    echo json_encode(['success' => false, 'message' => 'Restaurant not found']);
    exit;
}

// Verify order belongs to this restaurant
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND restaurant_id = ? LIMIT 1');
$stmt->execute([$orderId, $restaurant['id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Handle accept/reject actions
if ($action === 'accept') {
    $newStatus = 'confirmed';
} elseif ($action === 'reject') {
    $newStatus = 'cancelled';
}

if (!in_array($newStatus, $validStatuses, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Update order status
$stmt = $pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
$stmt->execute([$newStatus, $orderId]);

$message = $action === 'accept' ? 'Order accepted' : ($action === 'reject' ? 'Order rejected' : 'Order status updated');

echo json_encode([
    'success' => true,
    'message' => $message,
    'new_status' => $newStatus,
    'action' => $action
]);
