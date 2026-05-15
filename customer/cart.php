<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/food-images.php';

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
        <div class="header-row">
            <h1>🛒 Your cart</h1>
            <a class="pill-button" href="/customer/dashboard.php">← Back</a>
        </div>
    </header>
    <main class="page-content">
        <?php if (empty($items)): ?>
            <div class="empty-cart-state">
                <div class="empty-icon">🛒</div>
                <h2>Your cart is empty</h2>
                <p>Add items from a restaurant to start</p>
                <a class="primary-btn" href="/customer/dashboard.php">Browse restaurants</a>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($items as $item): ?>
                    <div class="cart-item-card">
                        <div class="item-details">
                            <div class="item-avatar">
                                <img src="<?= get_food_image($item['name']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            </div>
                            <div class="item-info">
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
            </div>
            
            <div class="summary-card">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span><?= format_price($subtotal) ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery fee</span>
                    <span class="free-badge">FREE</span>
                </div>
                <div class="summary-total">
                    <strong>Total</strong>
                    <strong class="total-amount"><?= format_price($subtotal + $deliveryFee) ?></strong>
                </div>
                <a class="primary-btn" href="/customer/checkout.php">Proceed to checkout</a>
            </div>
        <?php endif; ?>
    </main>
    <?php $active_nav = 'cart'; include __DIR__ . '/../includes/bottom-nav.php'; ?>
    <script src="/assets/js/script.js"></script>
    <script>
    document.querySelectorAll('.quantity-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id     = this.dataset.id;
            const action = this.dataset.action;
            const row    = this.closest('.cart-item-card');
            const qEl    = row.querySelector('.quantity-controls span');
            let qty = Math.max(0, parseInt(qEl.textContent) + (action === 'increase' ? 1 : -1));

            try {
                const res  = await fetch('/api/update-cart.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'menu_item_id=' + id + '&quantity=' + qty
                });
                const data = await res.json();
                if (data.success) {
                    if (qty === 0) {
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(40px)';
                        setTimeout(() => location.reload(), 300);
                    } else {
                        qEl.textContent = qty;
                        setTimeout(() => location.reload(), 300);
                    }
                    updateCartBadge(data.count);
                } else {
                    showToast(data.message || 'Could not update cart', 'error');
                }
            } catch {
                showToast('Network error', 'error');
            }
        });
    });
    </script>
</body>
</html>
