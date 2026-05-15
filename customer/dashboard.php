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
                <div class="subtitle">📍 Hawassa</div>
                <h1>Hawassa, Ethiopia</h1>
            </div>
            <a class="pill-button" href="/customer/profile.php">AB</a>
        </div>
        <div class="search-box">
            <form onsubmit="return false;">
                <input id="search-input" type="search" name="q" placeholder="Search restaurants, dishes or cuisine" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </form>
            <div id="search-results" class="search-results"></div>
        </div>
    </header>

    <main class="page-content">
        <section class="carousel-section">
            <button type="button" class="category-pill">
                <span class="category-emoji">🫓</span>
                Ethiopian
            </button>
            <button type="button" class="category-pill">
                <span class="category-emoji">🍲</span>
                Platters
            </button>
            <button type="button" class="category-pill">
                <span class="category-emoji">🥘</span>
                Special
            </button>
            <button type="button" class="category-pill">
                <span class="category-emoji">🍹</span>
                Drinks
            </button>
            <button type="button" class="category-pill">
                <span class="category-emoji">🍰</span>
                Desserts
            </button>
        </section>

        <section class="restaurants-section">
            <div class="section-header">
                <h2>Top rated restaurants</h2>
                <a class="section-link" href="#">See all</a>
            </div>
            <div class="cards-grid">
                <?php foreach ($restaurants as $restaurant): ?>
                    <a class="restaurant-card" href="/customer/restaurant.php?id=<?= $restaurant['id'] ?>">
                        <div class="restaurant-image">
                            <img src="/assets/images/food/doro-wat.jpg" alt="<?= htmlspecialchars($restaurant['name']) ?>">
                            <div class="restaurant-overlay"></div>
                            <div class="restaurant-labels">
                                <span class="rating-pill">⭐ <?= number_format($restaurant['rating'], 1) ?></span>
                                <span class="offer-pill">20% OFF</span>
                            </div>
                        </div>
                        <div class="restaurant-card-content">
                            <h3><?= htmlspecialchars($restaurant['name']) ?></h3>
                            <p><?= htmlspecialchars($restaurant['cuisine_type']) ?> • 30-40 min</p>
                            <div class="restaurant-details">
                                <span>Free delivery</span>
                                <span>4.9k+ ratings</span>
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
