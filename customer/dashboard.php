<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
require_once __DIR__ . '/../includes/db.php';

$userId = $_SESSION['user']['id'];
$userName = $_SESSION['user']['name'];

// Get customer stats
$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
$stmt->execute([$userId]);
$totalOrders = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE user_id = ?');
$stmt->execute([$userId]);
$totalSpent = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare('SELECT COUNT(DISTINCT restaurant_id) FROM orders WHERE user_id = ?');
$stmt->execute([$userId]);
$restaurantsOrdered = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = "delivered"');
$stmt->execute([$userId]);
$completedOrders = $stmt->fetchColumn();

// Recent orders
$stmt = $pdo->prepare('
    SELECT o.id, o.order_number, o.status, o.total_amount, o.created_at,
           r.name AS restaurant_name, r.cuisine_type
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
');
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top restaurants
$stmt = $pdo->prepare('
    SELECT r.id, r.name, r.cuisine_type, r.location, r.rating,
           COUNT(o.id) AS order_count
    FROM restaurants r
    JOIN orders o ON r.id = o.restaurant_id
    WHERE o.user_id = ?
    GROUP BY r.id
    ORDER BY order_count DESC
    LIMIT 6
');
$stmt->execute([$userId]);
$favoriteRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recommended restaurants
$stmt = $pdo->query('
    SELECT id, name, cuisine_type, location, rating
    FROM restaurants
    WHERE status = "active"
    ORDER BY rating DESC
    LIMIT 6
');
$recommendedRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/admin-layout.css">
    <link rel="stylesheet" href="/assets/css/admin-components.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">G</div>
                <div class="sidebar-title">Gebeta</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Menu</div>
                    <a href="/customer/dashboard.php" class="nav-item active">
                        <span class="nav-item-icon">🏠</span>
                        <span>Home</span>
                    </a>
                    <a href="/customer/orders.php" class="nav-item">
                        <span class="nav-item-icon">📦</span>
                        <span>My Orders</span>
                        <?php if ($totalOrders > 0): ?>
                        <span class="nav-item-badge"><?= $totalOrders ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/customer/cart.php" class="nav-item">
                        <span class="nav-item-icon">🛒</span>
                        <span>Cart</span>
                    </a>
                    <a href="/customer/addresses.php" class="nav-item">
                        <span class="nav-item-icon">📍</span>
                        <span>Addresses</span>
                    </a>
                    <a href="/customer/profile.php" class="nav-item">
                        <span class="nav-item-icon">👤</span>
                        <span>Profile</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Account</div>
                    <a href="/logout.php" class="nav-item">
                        <span class="nav-item-icon">🚪</span>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main" id="mainContent">
            <!-- Header -->
            <header class="admin-header">
                <button class="header-toggle" id="sidebarToggle">
                    <span>☰</span>
                </button>
                
                <div class="header-search">
                    <span class="header-search-icon">🔍</span>
                    <input type="text" class="header-search-input" placeholder="Search restaurants, dishes..." id="globalSearch">
                </div>
                
                <div class="header-actions">
                    <button class="header-action-btn">
                        <span>🔔</span>
                    </button>
                    
                    <div class="header-profile">
                        <div class="header-avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
                        <div class="header-profile-info">
                            <div class="header-profile-name"><?= htmlspecialchars($userName) ?></div>
                            <div class="header-profile-role">Customer</div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <div class="admin-content">
                <div class="content-header">
                    <h1 class="content-title">Welcome back, <?= htmlspecialchars(explode(' ', $userName)[0]) ?>! 👋</h1>
                    <p class="content-subtitle">Discover delicious food from your favorite restaurants</p>
                </div>
                
                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Orders</span>
                            <div class="kpi-icon">📦</div>
                        </div>
                        <div class="kpi-value"><?= number_format($totalOrders) ?></div>
                        <div class="kpi-trend">
                            <span><?= $completedOrders ?> completed</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Spent</span>
                            <div class="kpi-icon">💰</div>
                        </div>
                        <div class="kpi-value"><?= number_format($totalSpent, 0) ?> Birr</div>
                        <div class="kpi-trend">
                            <span>Lifetime spending</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Restaurants</span>
                            <div class="kpi-icon">🏪</div>
                        </div>
                        <div class="kpi-value"><?= number_format($restaurantsOrdered) ?></div>
                        <div class="kpi-trend">
                            <span>Unique restaurants</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Rewards Points</span>
                            <div class="kpi-icon">⭐</div>
                        </div>
                        <div class="kpi-value"><?= number_format($totalOrders * 10) ?></div>
                        <div class="kpi-trend positive">
                            <span>Earn more points!</span>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <?php if (!empty($recentOrders)): ?>
                <div class="data-table-container">
                    <div class="table-header">
                        <h2 class="table-title">Recent Orders</h2>
                        <a href="/customer/orders.php" class="action-btn action-btn-secondary">View All</a>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Restaurant</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                <td>
                                    <div><?= htmlspecialchars($order['restaurant_name']) ?></div>
                                    <small style="color: var(--gray-500);"><?= htmlspecialchars($order['cuisine_type']) ?></small>
                                </td>
                                <td><strong><?= number_format($order['total_amount'], 2) ?> Birr</strong></td>
                                <td><span class="status-badge <?= $order['status'] ?>"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></span></td>
                                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <a href="/customer/order-detail.php?id=<?= $order['id'] ?>" class="action-btn action-btn-primary">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- Favorite Restaurants -->
                <?php if (!empty($favoriteRestaurants)): ?>
                <div style="margin-top: var(--space-8);">
                    <h2 style="font-size: var(--text-2xl); font-weight: var(--font-bold); margin-bottom: var(--space-6);">Your Favorite Restaurants</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: var(--space-6);">
                        <?php foreach ($favoriteRestaurants as $restaurant): ?>
                        <a href="/customer/restaurant.php?id=<?= $restaurant['id'] ?>" style="text-decoration: none; color: inherit;">
                            <div style="background: white; border-radius: var(--radius-2xl); padding: var(--space-6); box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); transition: all var(--transition-base);" onmouseover="this.style.boxShadow='var(--shadow-lg)'; this.style.transform='translateY(-4px)';" onmouseout="this.style.boxShadow='var(--shadow-sm)'; this.style.transform='translateY(0)';">
                                <div style="display: flex; align-items: center; gap: var(--space-4); margin-bottom: var(--space-4);">
                                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--orange-400), var(--orange-600)); border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl);">
                                        🍽️
                                    </div>
                                    <div style="flex: 1;">
                                        <h3 style="font-size: var(--text-lg); font-weight: var(--font-bold); margin-bottom: var(--space-1);"><?= htmlspecialchars($restaurant['name']) ?></h3>
                                        <p style="font-size: var(--text-sm); color: var(--gray-600);"><?= htmlspecialchars($restaurant['cuisine_type']) ?></p>
                                    </div>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: var(--text-sm); color: var(--gray-600);">⭐ <?= number_format($restaurant['rating'], 1) ?></span>
                                    <span style="font-size: var(--text-sm); font-weight: var(--font-semibold); color: var(--orange-600);"><?= $restaurant['order_count'] ?> orders</span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Recommended Restaurants -->
                <div style="margin-top: var(--space-8);">
                    <h2 style="font-size: var(--text-2xl); font-weight: var(--font-bold); margin-bottom: var(--space-6);">Recommended for You</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: var(--space-6);">
                        <?php foreach ($recommendedRestaurants as $restaurant): ?>
                        <a href="/customer/restaurant.php?id=<?= $restaurant['id'] ?>" style="text-decoration: none; color: inherit;">
                            <div style="background: white; border-radius: var(--radius-2xl); overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); transition: all var(--transition-base);" onmouseover="this.style.boxShadow='var(--shadow-lg)'; this.style.transform='translateY(-4px)';" onmouseout="this.style.boxShadow='var(--shadow-sm)'; this.style.transform='translateY(0)';">
                                <div style="height: 180px; background: linear-gradient(135deg, var(--orange-100), var(--orange-200)); display: flex; align-items: center; justify-content: center; font-size: 64px;">
                                    🍽️
                                </div>
                                <div style="padding: var(--space-4);">
                                    <h3 style="font-size: var(--text-lg); font-weight: var(--font-bold); margin-bottom: var(--space-2);"><?= htmlspecialchars($restaurant['name']) ?></h3>
                                    <p style="font-size: var(--text-sm); color: var(--gray-600); margin-bottom: var(--space-3);"><?= htmlspecialchars($restaurant['cuisine_type']) ?></p>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-size: var(--text-sm); color: var(--gray-600);">📍 <?= htmlspecialchars($restaurant['location']) ?></span>
                                        <span style="font-size: var(--text-sm); font-weight: var(--font-semibold); color: var(--orange-600);">⭐ <?= number_format($restaurant['rating'], 1) ?></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Sidebar toggle
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainContent').classList.toggle('expanded');
        });
        
        // Search functionality
        document.getElementById('globalSearch').addEventListener('input', function(e) {
            const query = e.target.value;
            if (query.length > 2) {
                // Implement search
                console.log('Searching for:', query);
            }
        });
    </script>
</body>
</html>
