<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
if (!is_logged_in() || $_SESSION['user']['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$menuItemId = (int)($_POST['menu_item_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));
if ($menuItemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item']);
    exit;
}

$stmt = $pdo->prepare('SELECT mi.*, c.restaurant_id FROM menu_items mi JOIN categories c ON mi.category_id = c.id WHERE mi.id = ? AND mi.is_available = 1');
$stmt->execute([$menuItemId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not available']);
    exit;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$found = false;
foreach ($_SESSION['cart'] as &$cartItem) {
    if ($cartItem['menu_item_id'] === $menuItemId) {
        $cartItem['quantity'] += $quantity;
        $found = true;
        break;
    }
}
if (!$found) {
    $_SESSION['cart'][] = [
        'menu_item_id' => $menuItemId,
        'quantity' => $quantity,
        'restaurant_id' => $item['restaurant_id'],
    ];
}

$total = array_sum(array_map(function ($cartItem) use ($pdo) {
    $stmt = $pdo->prepare('SELECT price FROM menu_items WHERE id = ?');
    $stmt->execute([$cartItem['menu_item_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['price'] * $cartItem['quantity'] : 0;
}, $_SESSION['cart']));
$count = array_sum(array_column($_SESSION['cart'], 'quantity'));

echo json_encode(['success' => true, 'count' => $count, 'total' => number_format($total, 2)]);
