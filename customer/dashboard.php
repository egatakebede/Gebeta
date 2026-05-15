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
            <a class="pill-button" href="/customer/profile.php" style="background: linear-gradient(135deg, #FC8019, #E67E22); color: #fff; font-weight: 700;">AB</a>
        </div>
        <div class="search-box">
            <form onsubmit="return false;">
                <input id="search-input" type="search" name="q" placeholder="🔍 Search for restaurants..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </form>
            <div id="search-results" class="search-results"></div>
        </div>
    </header>

    <main class="page-content">
        <section class="carousel-section">
            <div class="category-card">
                <div style="font-size: 28px; margin-bottom: 6px;">🫓</div>
                <strong>Ethiopian</strong>
            </div>
            <div class="category-card">
                <div style="font-size: 28px; margin-bottom: 6px;">🍲</div>
                <strong>Platters</strong>
            </div>
            <div class="category-card">
                <div style="font-size: 28px; margin-bottom: 6px;">🥘</div>
                <strong>Special</strong>
            </div>
            <div class="category-card">
                <div style="font-size: 28px; margin-bottom: 6px;">🍹</div>
                <strong>Drinks</strong>
            </div>
            <div class="category-card">
                <div style="font-size: 28px; margin-bottom: 6px;">🍰</div>
                <strong>Desserts</strong>
            </div>
        </section>

        <section class="restaurants-section" style="padding: 0 20px;">
            <div class="section-header">
                <h2>⭐ Top Rated Restaurants</h2>
                <a href="#" style="color: var(--primary-orange); font-weight: 700; font-size: 14px;">See all →</a>
            </div>
            <div class="cards-grid">
                <?php foreach ($restaurants as $restaurant): ?>
                    <a class="restaurant-card" href="/customer/restaurant.php?id=<?= $restaurant['id'] ?>" style="display: grid; padding: 0; overflow: hidden;">
                        <div style="background: linear-gradient(135deg, #FFF5ED, #FFE8D6); height: 120px; display: grid; place-items: center; font-size: 48px;">🍽️</div>
                        <div style="padding: 14px;">
                            <h3 style="font-size: 15px; margin-bottom: 4px;"><?= htmlspecialchars($restaurant['name']) ?></h3>
                            <p style="font-size: 12px; color: var(--gray-text); margin-bottom: 8px;"><?= htmlspecialchars($restaurant['cuisine_type']) ?></p>
                            <div class="restaurant-stats">
                                <span style="background: linear-gradient(135deg, #FFF5ED, #FFE8D6); color: var(--primary-orange); font-weight: 700;">⭐ <?= number_format($restaurant['rating'], 1) ?></span>
                                <span>⏱️ 38 min</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <?php $active_nav = 'home'; include __DIR__ . '/../includes/bottom-nav.php'; ?>
    <script src="/assets/js/script.js"></script>
    <script>
    initPullToRefresh(() => location.reload());
    </script>
</body>
</html>
