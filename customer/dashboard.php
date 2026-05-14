<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query('SELECT id, name, description, cuisine_type, location, rating FROM restaurants WHERE status = "active" ORDER BY rating DESC LIMIT 8');
$restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cartCount = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="pull-refresh"></div>
    <header class="page-header">
        <div class="header-row">
            <div>
                <div class="subtitle">📍 Bole</div>
                <h1>Addis Ababa, Ethiopia</h1>
            </div>
            <a class="pill-button" href="/customer/profile.php">AB</a>
        </div>
        <div class="search-box">
            <input id="search-input" type="search" name="q" placeholder="Search for restaurants..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <div id="search-results" class="search-results"></div>
        </div>
    </header>

    <main class="page-content">
        <section class="carousel-section">
            <div class="category-card">🫓 Ethiopian</div>
            <div class="category-card">🍲 Platters</div>
            <div class="category-card">🥘 Special</div>
            <div class="category-card">🍹 Drinks</div>
        </section>

        <section class="restaurants-section">
            <div class="section-header">
                <h2>Top restaurants</h2>
                <a href="#">See all &gt;</a>
            </div>
            <div class="cards-grid">
                <?php foreach ($restaurants as $restaurant): ?>
                    <a class="restaurant-card" href="/customer/restaurant.php?id=<?= $restaurant['id'] ?>">
                        <div class="restaurant-image">🍽️</div>
                        <div class="restaurant-meta">
                            <h3><?= htmlspecialchars($restaurant['name']) ?></h3>
                            <p><?= htmlspecialchars($restaurant['cuisine_type']) ?> • <?= htmlspecialchars($restaurant['location']) ?></p>
                            <div class="restaurant-stats">
                                <span>⭐ <?= number_format($restaurant['rating'], 1) ?></span>
                                <span>38 mins</span>
                                <span>300 Birr</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
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
    initPullToRefresh(() => location.reload());
    </script>
</body>
</html>
