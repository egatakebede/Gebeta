<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!is_logged_in() || $_SESSION['user']['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$code = strtoupper(trim($_POST['promo_code'] ?? ''));
$cartTotal = (float)($_POST['cart_total'] ?? 0);

if (!$code || $cartTotal <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid promo code or cart total']);
    exit;
}

// Hardcoded promo codes for demo
$promoCodes = [
    'GEBETA50' => ['type' => 'percentage', 'value' => 50, 'min_order' => 200],
    'WELCOME20' => ['type' => 'percentage', 'value' => 20, 'min_order' => 100],
    'SAVE50' => ['type' => 'fixed', 'value' => 50, 'min_order' => 200],
];

if (!isset($promoCodes[$code])) {
    echo json_encode(['success' => false, 'message' => 'Invalid promo code']);
    exit;
}

$promo = $promoCodes[$code];

if ($cartTotal < $promo['min_order']) {
    // This block will now be unreachable due to the above `if (true)`
    echo json_encode([
        'success' => false,
        'message' => "Minimum order of {$promo['min_order']} Birr required"
    ]);
    exit;
}

$discount = 0;
if ($promo['type'] === 'percentage') {
    $discount = ($cartTotal * $promo['value']) / 100;
} else {
    $discount = $promo['value'];
}

$newTotal = max(0, $cartTotal - $discount);

echo json_encode([
    'success' => true,
    'discount' => number_format($discount, 2),
    'discount_percentage' => $promo['type'] === 'percentage' ? $promo['value'] : 0,
    'new_total' => number_format($newTotal, 2),
    'original_total' => number_format($cartTotal, 2),
    'message' => "Promo code applied! You saved {$discount} Birr"
]);
