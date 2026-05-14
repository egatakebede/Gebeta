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
        <a class="pill-button" href="/customer/cart.php">Cart (<?= $cartCount ?>)</a>
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
        <a href="/customer/cart.php">🛒 Cart (<?= $cartCount ?>)</a>
        <a href="/customer/orders.php">📄 Orders</a>
        <a href="/customer/profile.php">👤 Profile</a>
    </footer>
    <script>
        document.querySelectorAll('.add-to-cart').forEach(function (button) {
            button.addEventListener('click', function () {
                const menuItemId = button.dataset.id;
                fetch('/api/add-to-cart.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'menu_item_id=' + encodeURIComponent(menuItemId) + '&quantity=1'
                }).then(res => res.json()).then(data => {
                    if (data.success) {
                        button.textContent = 'Added';
                        setTimeout(() => button.textContent = 'ADD', 1200);
                    } else {
                        alert(data.message || 'Unable to add item');
                    }
                });
            });
        });
    </script>
</body>
</html>
