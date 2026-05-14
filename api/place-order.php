<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
if (!is_logged_in() || $_SESSION['user']['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$deliveryAddress = sanitize($_POST['delivery_address'] ?? '');
$paymentMethod = in_array($_POST['payment_method'] ?? 'cash', ['cash', 'bank_transfer', 'telebirr', 'mpesa', 'wallet'], true) ? $_POST['payment_method'] : 'cash';

if (!$deliveryAddress) {
    echo json_encode(['success' => false, 'message' => 'Delivery address is required']);
    exit;
}

$cartItems = get_cart_items();
if (empty($cartItems)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

$restaurantIds = array_unique(array_column($cartItems, 'restaurant_id'));
if (count($restaurantIds) > 1) {
    echo json_encode(['success' => false, 'message' => 'Please order from one restaurant at a time.']);
    exit;
}
$restaurantId = $restaurantIds[0];
$totalAmount = get_cart_total();
$orderNumber = 'GB' . time() . random_int(10, 99);
$deliveryFee = 20.00;

$stmt = $pdo->prepare('INSERT INTO orders (order_number, user_id, restaurant_id, delivery_address, payment_method, total_amount, delivery_fee, payment_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, "pending", "pending")');
$stmt->execute([$orderNumber, $_SESSION['user']['id'], $restaurantId, $deliveryAddress, $paymentMethod, $totalAmount, $deliveryFee]);
$orderId = $pdo->lastInsertId();

$stmtItem = $pdo->prepare('INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)');
foreach ($cartItems as $item) {
    $stmtItem->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
}

$_SESSION['cart'] = [];

echo json_encode(['success' => true, 'order_id' => $orderId, 'redirect' => '/customer/order-detail.php?id=' . $orderId]);
