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
    <style>
        .quantity-controls {
            border-radius: 10px !important;
            background: linear-gradient(135deg, #FFF5ED, #FFE8D6) !important;
            border: none !important;
            padding: 8px !important;
        }
        .quantity-btn {
            color: var(--primary-orange) !important;
        }
        .summary-card {
            background: linear-gradient(135deg, #fff 0%, var(--bg-light) 100%) !important;
            border: 2px solid var(--border-gray) !important;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            font-size: 16px;
        }
        .empty-state::before {
            content: '🛒';
            display: block;
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header class="page-header">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <h1>🛒 My Cart</h1>
            <a class="pill-button" href="/customer/dashboard.php" style="font-weight: 700;">← Back</a>
        </div>
    </header>
    <main class="page-content">
        <?php if (empty($items)): ?>
            <div class="empty-state">
                <p style="color: var(--gray-text); font-size: 16px;">Your cart is empty</p>
                <p style="color: var(--light-gray); font-size: 14px; margin-top: 8px;">Browse restaurants to add items</p>
                <a class="primary-btn" href="/customer/dashboard.php" style="margin-top: 20px;">Start ordering</a>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 20px;">
                <?php foreach ($items as $item): ?>
                    <div class="cart-item-card" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 16px; margin-bottom: 12px; transition: all 0.3s ease;">
                        <div class="item-details" style="display: flex; align-items: center; gap: 12px; flex: 1;">
                            <div class="item-avatar" style="width: 80px; height: 80px; background: linear-gradient(135deg, #FFF5ED, #FFE8D6);">🍲</div>
                            <div>
                                <h3 style="font-size: 15px; margin-bottom: 4px;"><?= htmlspecialchars($item['name']) ?></h3>
                                <p style="font-size: 13px; color: var(--gray-text); margin-bottom: 8px;"><?= htmlspecialchars($item['restaurant_name']) ?></p>
                                <strong style="color: var(--primary-orange); font-size: 14px;"><?= format_price($item['price']) ?></strong>
                            </div>
                        </div>
                        <div class="quantity-controls">
                            <button class="quantity-btn" data-id="<?= $item['id'] ?>" data-action="decrease" style="padding: 6px 10px;">−</button>
                            <span style="padding: 0 10px; font-weight: 700;"><?= $item['quantity'] ?></span>
                            <button class="quantity-btn" data-id="<?= $item['id'] ?>" data-action="increase" style="padding: 6px 10px;">+</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="summary-card" style="padding: 20px; border-radius: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; font-size: 14px;">
                    <span style="color: var(--gray-text);">Subtotal</span>
                    <span style="font-weight: 700;"><?= format_price($subtotal) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; font-size: 14px;">
                    <span style="color: var(--gray-text);">Delivery fee</span>
                    <span style="color: var(--success-green); font-weight: 700;">FREE ✓</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-top: 2px solid var(--border-gray); margin-top: 12px; font-size: 16px;">
                    <strong>Total</strong>
                    <strong style="color: var(--primary-orange); font-size: 18px;"><?= format_price($subtotal + $deliveryFee) ?></strong>
                </div>
                <a class="primary-btn" href="/customer/checkout.php" style="width: 100%; margin-top: 16px; justify-content: center;">Proceed to checkout</a>
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
                        row.style.transition = 'opacity 0.3s, transform 0.3s';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(40px)';
                        setTimeout(() => row.remove(), 300);
                    } else {
                        qEl.textContent = qty;
                    }
                    updateCartBadge(data.count);
                    location.reload();
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
