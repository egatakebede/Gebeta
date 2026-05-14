<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
if (!is_logged_in() || $_SESSION['user']['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$menuItemId = (int)($_POST['menu_item_id'] ?? 0);
$quantity = max(0, (int)($_POST['quantity'] ?? 1));
if ($menuItemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item']);
    exit;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

foreach ($_SESSION['cart'] as $idx => $cartItem) {
    if ($cartItem['menu_item_id'] === $menuItemId) {
        if ($quantity === 0) {
            unset($_SESSION['cart'][$idx]);
        } else {
            $_SESSION['cart'][$idx]['quantity'] = $quantity;
        }
        break;
    }
}

$_SESSION['cart'] = array_values($_SESSION['cart']);

$total = 0;
$count = 0;
foreach ($_SESSION['cart'] as $cartItem) {
    $stmt = $pdo->prepare('SELECT price FROM menu_items WHERE id = ?');
    $stmt->execute([$cartItem['menu_item_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $total += $row['price'] * $cartItem['quantity'];
        $count += $cartItem['quantity'];
    }
}

echo json_encode(['success' => true, 'count' => $count, 'total' => number_format($total, 2)]);
