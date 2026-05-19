<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
require_once __DIR__ . '/../includes/db.php';

$userId = $_SESSION['user']['id'];
$userName = $_SESSION['user']['name'];
$userInitials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
$stmt->execute([$userId]);
$ordersCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(DISTINCT restaurant_id) FROM orders WHERE user_id = ?');
$stmt->execute([$userId]);
$savedCount = (int)$stmt->fetchColumn();

$points = $ordersCount * 30;
$walletBalance = 0.00;

$stmt = $pdo->prepare('SELECT DISTINCT cuisine_type FROM restaurants WHERE status = ? AND cuisine_type <> "" ORDER BY cuisine_type LIMIT 8');
$stmt->execute(['active']);
$categories = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'cuisine_type');
if (empty($categories)) {
    $categories = ['Ethiopian', 'Wat', 'Tibs', 'Coffee', 'Pizza', 'Desserts', 'Salads', 'Snacks'];
}

$stmt = $pdo->prepare('SELECT id, name, description, cuisine_type, location, rating FROM restaurants WHERE status = ? ORDER BY rating DESC LIMIT 6');
$stmt->execute(['active']);
$topRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT id, name, description, cuisine_type, location, rating FROM restaurants WHERE status = ? ORDER BY created_at DESC LIMIT 6');
$stmt->execute(['active']);
$newRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT r.id, r.name, r.cuisine_type, r.location, r.rating, COUNT(o.id) AS order_count
    FROM restaurants r
    JOIN orders o ON r.id = o.restaurant_id
    WHERE o.user_id = ?
    GROUP BY r.id, r.name, r.cuisine_type, r.location, r.rating
    ORDER BY order_count DESC, MAX(o.created_at) DESC
    LIMIT 4');
$stmt->execute([$userId]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT r.id, r.name, r.cuisine_type, r.location, r.rating, MAX(o.created_at) AS last_ordered_at
    FROM restaurants r
    JOIN orders o ON r.id = o.restaurant_id
    WHERE o.user_id = ?
    GROUP BY r.id, r.name, r.cuisine_type, r.location, r.rating
    ORDER BY last_ordered_at DESC
    LIMIT 4');
$stmt->execute([$userId]);
$recentRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT id FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
$stmt->execute([$userId]);
$lastOrderId = $stmt->fetchColumn();

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
                <div class="subtitle">Bole, Addis Ababa</div>
                <h1>Good afternoon, <?= htmlspecialchars(explode(' ', $userName)[0]) ?>!</h1>
            </div>
            <div class="header-actions">
                <button type="button" class="icon-button" aria-label="Notifications">🔔<span class="notification-badge">3</span></button>
                <a class="pill-button" href="/customer/profile.php" aria-label="Profile"><?= $userInitials ?></a>
            </div>
        </div>
        <div class="search-box">
            <form onsubmit="return false;">
                <input id="search-input" type="search" name="q" placeholder="Search restaurants, dishes or cuisine" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </form>
            <div id="search-results" class="search-results"></div>
        </div>
    </header>

    <main class="page-content">
        <section class="quick-actions">
            <?php if ($lastOrderId): ?>
                <a class="quick-action-card" href="/customer/order-detail.php?id=<?= $lastOrderId ?>">
                    <span class="action-icon">🔄</span>
                    <div class="action-title">Reorder last</div>
                    <div class="action-note">Repeat your most recent meal.</div>
                </a>
            <?php else: ?>
                <div class="quick-action-card disabled">
                    <span class="action-icon">🔄</span>
                    <div class="action-title">Reorder last</div>
                    <div class="action-note">Place your first order to enable this.</div>
                </div>
            <?php endif; ?>
            <a class="quick-action-card" href="#favorites">
                <span class="action-icon">❤️</span>
                <div class="action-title">Favorites</div>
                <div class="action-note"><?= $savedCount ?> saved restaurants</div>
            </a>
            <a class="quick-action-card" href="#offers">
                <span class="action-icon">🎟️</span>
                <div class="action-title">Offers</div>
                <div class="action-note">View current promos and discounts.</div>
            </a>
        </section>

        <section class="stats-grid">
            <div class="stat-card">
                <span class="stat-label">Orders</span>
                <span class="stat-value"><?= $ordersCount ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Saved</span>
                <span class="stat-value"><?= $savedCount ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Points</span>
                <span class="stat-value"><?= number_format($points) ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Wallet</span>
                <span class="stat-value"><?= number_format($walletBalance, 2) ?> Birr</span>
            </div>
        </section>

        <section class="carousel-section">
            <?php foreach ($categories as $category): ?>
                <button type="button" class="category-pill" data-search="<?= htmlspecialchars($category) ?>">
                    <span class="category-emoji"><img src="/assets/images/food/coffee.jpg" alt="<?= htmlspecialchars($category) ?>"></span>
                    <?= htmlspecialchars($category) ?>
                </button>
            <?php endforeach; ?>
        </section>

        <section id="offers" class="offer-card">
            <div>
                <p class="eyebrow">Special offer</p>
                <h2>20% off on first order</h2>
                <p>Use code <strong>WELCOME20</strong> at checkout for a limited time.</p>
            </div>
            <a class="primary-btn" href="/customer/cart.php">Apply code</a>
        </section>

        <section class="restaurants-section">
            <div class="section-header">
                <h2>Top rated near you</h2>
                <a class="section-link" href="#">See all</a>
            </div>
            <div class="cards-grid">
                <?php if (empty($topRestaurants)): ?>
                    <div class="empty-state">No restaurants found near you right now.</div>
                <?php endif; ?>
                <?php foreach ($topRestaurants as $restaurant): ?>
                    <a class="restaurant-card" href="/customer/restaurant.php?id=<?= htmlspecialchars($restaurant['id']) ?>">
                        <div class="restaurant-image">
                            <img src="/assets/images/food/doro-wat.jpg" alt="<?= htmlspecialchars($restaurant['name']) ?>">
                            <div class="restaurant-overlay"></div>
                            <div class="restaurant-labels">
                                <span class="rating-pill">Rating <?= number_format($restaurant['rating'], 1) ?></span>
                            </div>
                        </div>
                        <div class="restaurant-card-content">
                            <h3><?= htmlspecialchars($restaurant['name']) ?></h3>
                            <p><?= htmlspecialchars($restaurant['cuisine_type']) ?> • <?= htmlspecialchars($restaurant['location']) ?></p>
                            <div class="restaurant-details">
                                <span>30-40 min</span>
                                <span>Free delivery</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="restaurants-section">
            <div class="section-header">
                <h2>New restaurants</h2>
                <a class="section-link" href="#">See all</a>
            </div>
            <div class="cards-grid">
                <?php if (empty($newRestaurants)): ?>
                    <div class="empty-state">No new restaurants available at the moment.</div>
                <?php endif; ?>
                <?php foreach ($newRestaurants as $restaurant): ?>
                    <a class="restaurant-card" href="/customer/restaurant.php?id=<?= htmlspecialchars($restaurant['id']) ?>">
                        <div class="restaurant-image">
                            <img src="/assets/images/food/doro-wat.jpg" alt="<?= htmlspecialchars($restaurant['name']) ?>">
                            <div class="restaurant-overlay"></div>
                            <div class="restaurant-labels">
                                <span class="offer-pill">New</span>
                            </div>
                        </div>
                        <div class="restaurant-card-content">
                            <h3><?= htmlspecialchars($restaurant['name']) ?></h3>
                            <p><?= htmlspecialchars($restaurant['cuisine_type']) ?> • <?= htmlspecialchars($restaurant['location']) ?></p>
                            <div class="restaurant-details">
                                <span>35-45 min</span>
                                <span>4.8k+ ratings</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="favorites" class="restaurants-section">
            <div class="section-header">
                <h2>Your favorites</h2>
                <a class="section-link" href="#">View all</a>
            </div>
            <div class="cards-grid">
                <?php if (empty($favorites)): ?>
                    <div class="empty-state">No favorites yet. Order from a restaurant to save it here.</div>
                <?php endif; ?>
                <?php foreach ($favorites as $restaurant): ?>
                    <a class="restaurant-card" href="/customer/restaurant.php?id=<?= htmlspecialchars($restaurant['id']) ?>">
                        <div class="restaurant-image">
                            <img src="/assets/images/food/doro-wat.jpg" alt="<?= htmlspecialchars($restaurant['name']) ?>">
                            <div class="restaurant-overlay"></div>
                            <div class="restaurant-labels">
                                <span class="rating-pill">Rating <?= number_format($restaurant['rating'], 1) ?></span>
                            </div>
                        </div>
                        <div class="restaurant-card-content">
                            <h3><?= htmlspecialchars($restaurant['name']) ?></h3>
                            <p><?= htmlspecialchars($restaurant['cuisine_type']) ?> • <?= htmlspecialchars($restaurant['location']) ?></p>
                            <div class="restaurant-details">
                                <span>Saved by you</span>
                                <span><?= $restaurant['order_count'] ?> orders</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="restaurants-section">
            <div class="section-header">
                <h2>Recently ordered from</h2>
                <a class="section-link" href="/customer/orders.php">See all</a>
            </div>
            <div class="cards-grid">
                <?php if (empty($recentRestaurants)): ?>
                    <div class="empty-state">No recent orders yet.</div>
                <?php endif; ?>
                <?php foreach ($recentRestaurants as $restaurant): ?>
                    <a class="restaurant-card" href="/customer/restaurant.php?id=<?= htmlspecialchars($restaurant['id']) ?>">
                        <div class="restaurant-image">
                            <img src="/assets/images/food/doro-wat.jpg" alt="<?= htmlspecialchars($restaurant['name']) ?>">
                            <div class="restaurant-overlay"></div>
                            <div class="restaurant-labels">
                                <span class="rating-pill">Rating <?= number_format($restaurant['rating'], 1) ?></span>
                            </div>
                        </div>
                        <div class="restaurant-card-content">
                            <h3><?= htmlspecialchars($restaurant['name']) ?></h3>
                            <p><?= htmlspecialchars($restaurant['cuisine_type']) ?> • <?= htmlspecialchars($restaurant['location']) ?></p>
                            <div class="restaurant-details">
                                <span>Recent order</span>
                                <span>Quick reorder</span>
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
