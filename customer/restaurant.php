<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('/customer/dashboard.php');
}

$stmt = $pdo->prepare('SELECT r.*, u.name AS owner_name FROM restaurants r JOIN users u ON r.user_id = u.id WHERE r.id = ? AND r.status = "active" LIMIT 1');
$stmt->execute([$id]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$restaurant) {
    redirect('/customer/dashboard.php');
}

$stmt = $pdo->prepare('SELECT mi.*, c.name AS category_name FROM menu_items mi JOIN categories c ON mi.category_id = c.id WHERE c.restaurant_id = ? ORDER BY mi.created_at DESC');
$stmt->execute([$id]);
$menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cartCount = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant['name']) ?> · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header restaurant-header">
        <a class="back-link" href="/customer/dashboard.php">← Back</a>
        <a class="pill-button" href="/customer/cart.php">🛒 <span class="cart-count"><?= $cartCount ?></span></a>
    </header>
    <main class="page-content">
        <section class="restaurant-hero">
            <div class="hero-image">🫓</div>
            <div class="restaurant-info">
                <h1><?= htmlspecialchars($restaurant['name']) ?></h1>
                <p><?= htmlspecialchars($restaurant['cuisine_type']) ?> • <?= htmlspecialchars($restaurant['location']) ?></p>
                <div class="restaurant-stats">
                    <span>⭐ <?= number_format($restaurant['rating'], 1) ?></span>
                    <span>38 mins</span>
                    <span>300 Birr</span>
                </div>
            </div>
        </section>

        <section class="menu-section">
            <h2>Recommended</h2>
            <?php if (empty($menuItems)): ?>
                <p class="empty-state">No menu items available yet.</p>
            <?php endif; ?>
            <?php foreach ($menuItems as $item): ?>
                <div class="menu-item-card">
                    <div>
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p><?= htmlspecialchars($item['description']) ?></p>
                        <strong><?= format_price($item['price']) ?></strong>
                    </div>
                    <button class="secondary-btn add-to-cart" data-id="<?= $item['id'] ?>">ADD</button>
                </div>
            <?php endforeach; ?>
        </section>
    </main>
    <footer class="bottom-bar">
        <a href="/customer/dashboard.php">🏠 Home</a>
        <a href="/customer/cart.php" data-cart-link>🛒 Cart<?= $cartCount ? ' (' . $cartCount . ')' : '' ?></a>
        <a href="/customer/orders.php">📄 Orders</a>
        <a href="/customer/profile.php">👤 Profile</a>
    </footer>
    <script src="/assets/js/script.js"></script>
    <script>
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            const orig = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinning">↻</span>';
            try {
                const res  = await fetch('/api/add-to-cart.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'menu_item_id=' + encodeURIComponent(id) + '&quantity=1'
                });
                const data = await res.json();
                if (data.success) {
                    updateCartBadge(data.count);
                    showToast('Added to cart!', 'success');
                    // Replace button with qty control
                    this.outerHTML = `<div class="qty-ctrl" data-id="${id}">
                        <button onclick="cartQty(this,-1,${id})">−</button>
                        <span>1</span>
                        <button onclick="cartQty(this,1,${id})">+</button>
                    </div>`;
                } else {
                    showToast(data.message || 'Could not add item', 'error');
                    this.disabled = false;
                    this.innerHTML = orig;
                }
            } catch {
                showToast('Network error', 'error');
                this.disabled = false;
                this.innerHTML = orig;
            }
        });
    });

    async function cartQty(btn, delta, id) {
        const ctrl = btn.closest('.qty-ctrl');
        const span = ctrl.querySelector('span');
        const qty  = Math.max(0, parseInt(span.textContent) + delta);
        const res  = await fetch('/api/update-cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'menu_item_id=' + id + '&quantity=' + qty
        });
        const data = await res.json();
        if (data.success) {
            updateCartBadge(data.count);
            if (qty === 0) {
                ctrl.outerHTML = `<button class="secondary-btn add-to-cart" data-id="${id}">ADD</button>`;
                // re-attach listener
                document.querySelector(`.add-to-cart[data-id="${id}"]`)
                    ?.dispatchEvent(new Event('init'));
            } else {
                span.textContent = qty;
            }
        }
    }
    </script>
</body>
</html>
