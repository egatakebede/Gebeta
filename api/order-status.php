<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT status FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$id, $_SESSION['user']['id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['error' => 'Not found']);
    exit;
}

$labels = [
    'pending'          => 'Order placed',
    'confirmed'        => 'Order confirmed',
    'preparing'        => 'Preparing your order',
    'ready'            => 'Ready for delivery',
    'out_for_delivery' => 'Out for delivery',
    'delivered'        => 'Delivered',
];

echo json_encode([
    'status' => $order['status'],
    'label'  => $labels[$order['status']] ?? $order['status'],
]);
