<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
require_once __DIR__ . '/../includes/db.php';

$items = get_cart_items();
$subtotal = get_cart_total();
$deliveryFee = 0;
$cartCount = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>Cart</h1>
        <a class="pill-button" href="/customer/dashboard.php">Continue</a>
    </header>
    <main class="page-content">
        <?php if (empty($items)): ?>
            <div class="empty-state">Your cart is empty. Browse restaurants to add items.</div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <div class="cart-item-card">
                    <div class="item-details">
                        <div class="item-avatar">🍲</div>
                        <div>
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p><?= htmlspecialchars($item['restaurant_name']) ?></p>
                            <strong><?= format_price($item['price']) ?></strong>
                        </div>
                    </div>
                    <div class="quantity-controls">
                        <button class="quantity-btn" data-id="<?= $item['id'] ?>" data-action="decrease">−</button>
                        <span><?= $item['quantity'] ?></span>
                        <button class="quantity-btn" data-id="<?= $item['id'] ?>" data-action="increase">+</button>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="summary-card">
                <div><span>Subtotal</span><span><?= format_price($subtotal) ?></span></div>
                <div><span>Delivery fee</span><span>FREE ✓</span></div>
                <div class="summary-total"><strong>Total</strong><strong><?= format_price($subtotal + $deliveryFee) ?></strong></div>
                <a class="primary-btn" href="/customer/checkout.php">Proceed to checkout</a>
            </div>
        <?php endif; ?>
    </main>
    <footer class="bottom-bar">
        <a href="/customer/dashboard.php">🏠 Home</a>
        <a href="/customer/cart.php">🛒 Cart (<?= $cartCount ?>)</a>
        <a href="/customer/orders.php">📄 Orders</a>
        <a href="/customer/profile.php">👤 Profile</a>
    </footer>
    <script>
        document.querySelectorAll('.quantity-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                const id = button.dataset.id;
                const action = button.dataset.action;
                const row = button.closest('.cart-item-card');
                const quantityEl = row.querySelector('.quantity-controls span');
                let quantity = parseInt(quantityEl.textContent, 10);
                if (action === 'decrease') {
                    quantity = Math.max(0, quantity - 1);
                } else {
                    quantity += 1;
                }
                fetch('/api/update-cart.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'menu_item_id=' + encodeURIComponent(id) + '&quantity=' + encodeURIComponent(quantity)
                }).then(res => res.json()).then(data => {
                    if (data.success) {
                        if (quantity === 0) {
                            row.remove();
                        } else {
                            quantityEl.textContent = quantity;
                        }
                        location.reload();
                    } else {
                        alert(data.message || 'Unable to update cart');
                    }
                });
            });
        });
    </script>
</body>
</html>
