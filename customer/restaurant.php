<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('/customer/dashboard.php');

$stmt = $pdo->prepare('SELECT r.*, u.name AS owner_name FROM restaurants r JOIN users u ON r.user_id = u.id WHERE r.id = ? AND r.status = ? LIMIT 1');
$stmt->execute([$id, 'active']);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$restaurant) redirect('/customer/dashboard.php');

$stmt = $pdo->prepare('SELECT mi.*, c.name AS category_name FROM menu_items mi JOIN categories c ON mi.category_id = c.id WHERE c.restaurant_id = ? ORDER BY mi.created_at DESC');
$stmt->execute([$id]);
$menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cartCount = get_cart_count();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant['name']) ?> · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .restaurant-hero {
            background: linear-gradient(135deg, #FFF5ED 0%, #FFE8D6 100%);
            border-radius: 20px;
            padding: 30px 20px;
            margin: 0 20px 24px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(252,128,25,0.15);
        }
        .hero-image { font-size: 64px; margin-bottom: 16px; }
        .menu-section { padding: 0 20px; }
        .menu-section h2 {
            font-size: 20px;
            margin: 20px 0;
            color: var(--primary-orange);
        }
    </style>
</head>
<body>
    <header class="page-header">
        <div style="display:flex;justify-content:space-between;align-items:center;width:100%;gap:8px;">
            <a class="pill-button" href="/customer/dashboard.php">← Back</a>
            <a class="pill-button" href="/customer/restaurant-feed.php?id=<?= $id ?>">Food Feed</a>
            <a class="pill-button" href="/customer/cart.php" style="background:var(--primary-orange);color:#fff;position:relative;">
                Cart <span class="cart-count nav-badge" style="position:relative;top:auto;right:auto;display:inline-flex;margin-left:4px;"><?= $cartCount ?: '' ?></span>
            </a>
        </div>
    </header>

    <main class="page-content" style="padding-top:0;padding-bottom:80px;">
        <section class="restaurant-hero">
            <div class="hero-image"><img src="/assets/images/food/doro-wat.jpg" alt="<?= htmlspecialchars($restaurant['name']) ?>" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; box-shadow: 0 8px 24px rgba(0,0,0,0.15);"></div>
            <div class="restaurant-info">
                <h1 style="font-size:24px;margin-bottom:8px;"><?= htmlspecialchars($restaurant['name']) ?></h1>
                <p style="color:var(--gray-text);font-size:14px;margin:6px 0 12px;"><?= htmlspecialchars($restaurant['cuisine_type']) ?> • <?= htmlspecialchars($restaurant['location']) ?></p>
                <div class="restaurant-stats" style="justify-content:center;">
                    <span style="background:linear-gradient(135deg,#FFF5ED,#FFE8D6);color:var(--primary-orange);font-weight:700;">Rating <?= number_format($restaurant['rating'], 1) ?></span>
                    <span>38 mins</span>
                    <span>Min 300 Birr</span>
                </div>
            </div>
        </section>

        <section class="menu-section">
            <h2>Injera Menu</h2>
            <?php if (empty($menuItems)): ?>
                <div class="empty-state">No menu items available yet.</div>
            <?php else: ?>
                <div style="display:grid;gap:12px;margin-bottom:20px;">
                <?php foreach ($menuItems as $item): ?>
                    <div class="menu-item-card" style="display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;padding:14px;border-radius:16px;">
                        <div style="min-width:0;">
                            <h3 style="font-size:15px;margin-bottom:4px;"><?= htmlspecialchars($item['name']) ?></h3>
                            <p style="font-size:12px;color:var(--gray-text);margin-bottom:8px;line-height:1.4;"><?= htmlspecialchars(mb_substr($item['description'], 0, 60)) . (mb_strlen($item['description']) > 60 ? '...' : '') ?></p>
                            <strong style="color:var(--primary-orange);font-size:14px;"><?= format_price($item['price']) ?></strong>
                        </div>
                        <button class="secondary-btn add-to-cart" data-id="<?= $item['id'] ?>" style="white-space:nowrap;padding:10px 14px;font-size:13px;font-weight:700;">ADD</button>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php $active_nav = 'home'; include __DIR__ . '/../includes/bottom-nav.php'; ?>

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
                    showToast('Added to cart! ✓', 'success');
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
                ctrl.outerHTML = `<button class="secondary-btn add-to-cart" data-id="${id}" style="white-space:nowrap;padding:10px 14px;font-size:13px;font-weight:700;">ADD</button>`;
                document.querySelector(`.add-to-cart[data-id="${id}"]`)?.addEventListener('click', arguments.callee);
            } else {
                span.textContent = qty;
            }
        }
    }
    </script>
</body>
</html>
