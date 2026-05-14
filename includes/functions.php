<?php
require_once __DIR__ . '/db.php';

function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function format_price($amount) {
    return number_format((float)$amount, 2) . ' Birr';
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function flash_set($key, $message) {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['flash'][$key] = $message;
}

function flash_get($key) {
    if (!isset($_SESSION)) {
        session_start();
    }
    if (!empty($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    return null;
}

function get_cart_count() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

function get_cart_items() {
    global $pdo;

    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return [];
    }

    $cartItems = [];
    $ids = array_column($_SESSION['cart'], 'menu_item_id');
    if (empty($ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT mi.*, c.restaurant_id, r.name AS restaurant_name FROM menu_items mi JOIN categories c ON mi.category_id = c.id JOIN restaurants r ON c.restaurant_id = r.id WHERE mi.id IN ($placeholders) AND mi.is_available = 1");
    $stmt->execute($ids);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $itemsById = [];
    foreach ($items as $item) {
        $itemsById[$item['id']] = $item;
    }

    foreach ($_SESSION['cart'] as $cartItem) {
        if (isset($itemsById[$cartItem['menu_item_id']])) {
            $item = $itemsById[$cartItem['menu_item_id']];
            $item['quantity'] = max(1, (int)$cartItem['quantity']);
            $item['subtotal'] = $item['price'] * $item['quantity'];
            $cartItems[] = $item;
        }
    }
    return $cartItems;
}

function get_cart_total() {
    $items = get_cart_items();
    $total = 0;
    foreach ($items as $item) {
        $total += $item['subtotal'];
    }
    return $total;
}
