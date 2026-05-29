cat > /home/e/Gebeta/customer/dashboard.php << 'EOF'
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

// Favorite restaurants
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

$rewardPoints = $totalOrders * 10;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#FC8019">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Gebeta - Food Delivery</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #F8FAFC;
            color: #1E293B;
            padding-bottom: 70px;
        }
        
        /* Header */
        .header {
            background: #FFFFFF;
            padding: 16px;
            border-bottom: 1px solid #E2E8F0;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, #FC8019, #F97316);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header-icons {
            display: flex;
            gap: 16px;
        }
        
        .header-icon {
            font-size: 22px;
            cursor: pointer;
            padding: 8px;
            min-width: 44px;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #FC8019, #F97316);
            margin: 16px;
            padding: 20px;
            border-radius: 20px;
            color: white;
        }
        
        .welcome-banner h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .welcome-banner p {
            font-size: 13px;
            opacity: 0.9;
        }
        
        /* Stats Cards - Horizontal Scroll on Mobile */
        .stats-scroll {
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            padding: 0 16px;
            margin-bottom: 20px;
            -webkit-overflow-scrolling: touch;
        }
        
        .stats-grid {
            display: inline-flex;
            gap: 12px;
        }
        
        .stat-card {
            background: white;
            padding: 16px;
            border-radius: 16px;
            border: 1px solid #E2E8F0;
            width: 160px;
            display: inline-block;
            white-space: normal;
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 800;
            color: #1E293B;
        }
        
        .stat-trend {
            font-size: 10px;
            color: #10B981;
            margin-top: 6px;
        }
        
        /* Rewards Card */
        .reward-card {
            background: linear-gradient(135deg, #1E293B, #0F172A);
            margin: 0 16px 20px;
            padding: 16px;
            border-radius: 16px;
            color: white;
        }
        
        .reward-points {
            font-size: 28px;
            font-weight: 800;
        }
        
        .reward-progress {
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            height: 8px;
            margin: 12px 0;
            overflow: hidden;
        }
        
        .reward-progress-bar {
            background: #FC8019;
            height: 100%;
            border-radius: 10px;
            width: <?php echo ($rewardPoints % 100); ?>%;
        }
        
        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 0 16px;
            margin-bottom: 12px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
        }
        
        .section-link {
            font-size: 13px;
            color: #FC8019;
            text-decoration: none;
        }
        
        /* Restaurant Grid - Mobile Optimized */
        .restaurants-scroll {
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            padding: 0 16px;
            margin-bottom: 24px;
            -webkit-overflow-scrolling: touch;
        }
        
        .restaurants-grid {
            display: inline-flex;
            gap: 12px;
        }
        
        .restaurant-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #E2E8F0;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            width: 280px;
            display: inline-block;
            white-space: normal;
            transition: transform 0.2s;
        }
        
        .restaurant-card:active {
            transform: scale(0.98);
        }
        
        .restaurant-image {
            height: 140px;
            background: linear-gradient(135deg, #FEF3C7, #FDE68A);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }
        
        .restaurant-info {
            padding: 12px;
        }
        
        .restaurant-name {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 4px;
            white-space: normal;
            word-wrap: break-word;
        }
        
        .restaurant-cuisine {
            font-size: 12px;
            color: #64748B;
            margin-bottom: 8px;
        }
        
        .restaurant-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }
        
        .rating {
            color: #F59E0B;
        }
        
        /* Recent Orders Table - Mobile Optimized */
        .orders-card {
            background: white;
            margin: 0 16px 20px;
            border-radius: 16px;
            border: 1px solid #E2E8F0;
            overflow: hidden;
        }
        
        .order-item {
            padding: 14px 16px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-info {
            flex: 1;
        }
        
        .order-number {
            font-weight: 600;
            font-size: 14px;
        }
        
        .order-restaurant {
            font-size: 12px;
            color: #64748B;
            margin-top: 2px;
        }
        
        .order-amount {
            font-weight: 700;
            margin-right: 12px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-pending { background: #FEF3C7; color: #D97706; }
        .status-confirmed { background: #DBEAFE; color: #2563EB; }
        .status-preparing { background: #DBEAFE; color: #2563EB; }
        .status-ready { background: #D1FAE5; color: #059669; }
        .status-delivered { background: #D1FAE5; color: #059669; }
        .status-cancelled { background: #FEE2E2; color: #DC2626; }
        
        /* Bottom Navigation - Mobile First */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(10px);
            border-top: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-around;
            padding: 8px 16px 12px;
            z-index: 100;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #94A3B8;
            font-size: 10px;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 12px;
            transition: all 0.2s;
            min-width: 60px;
        }
        
        .nav-item.active {
            color: #FC8019;
            background: #FEF3C7;
        }
        
        .nav-icon {
            font-size: 22px;
        }
        
        /* Dark Mode */
        body.night {
            background: #0F172A;
        }
        
        body.night .header,
        body.night .stat-card,
        body.night .restaurant-card,
        body.night .orders-card,
        body.night .bottom-nav {
            background: #1E293B;
            border-color: #334155;
        }
        
        body.night .stat-value,
        body.night .restaurant-name,
        body.night .order-number {
            color: #F1F5F9;
        }
        
        body.night .stat-label,
        body.night .restaurant-cuisine,
        body.night .order-restaurant {
            color: #94A3B8;
        }
        
        /* Loading State */
        .loading {
            display: flex;
            justify-content: center;
            padding: 40px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #E2E8F0;
            border-top-color: #FC8019;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Refresh Button */
        .refresh-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
        }
        
        /* Responsive */
        @media (min-width: 768px) {
            body {
                padding-bottom: 0;
            }
            .bottom-nav {
                display: none;
            }
            .stats-scroll {
                overflow-x: visible;
                white-space: normal;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
            }
            .restaurants-scroll {
                overflow-x: visible;
                white-space: normal;
            }
            .restaurants-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }
    </style>
</head>
<body id="themeRoot">
    <!-- Header -->
    <div class="header">
        <div class="header-top">
            <div class="logo">🍽️ Gebeta</div>
            <div class="header-icons">
                <span class="header-icon" onclick="location.reload()">🔄</span>
                <span class="header-icon" id="darkModeToggle">🌙</span>
                <span class="header-icon" onclick="window.location='/customer/profile.php'">👤</span>
            </div>
        </div>
    </div>
    
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <h1>Hey, <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>! 👋</h1>
        <p>What would you like to eat today?</p>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-scroll">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header"><span class="stat-label">Orders</span><span>📦</span></div>
                <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
                <div class="stat-trend"><?php echo $completedOrders; ?> completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><span class="stat-label">Spent</span><span>💰</span></div>
                <div class="stat-value"><?php echo number_format($totalSpent, 0); ?> Br</div>
                <div class="stat-trend">Lifetime</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><span class="stat-label">Restaurants</span><span>🏪</span></div>
                <div class="stat-value"><?php echo number_format($restaurantsOrdered); ?></div>
                <div class="stat-trend">Unique places</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><span class="stat-label">Points</span><span>⭐</span></div>
                <div class="stat-value"><?php echo number_format($rewardPoints); ?></div>
                <div class="stat-trend">Rewards</div>
            </div>
        </div>
    </div>
    
    <!-- Rewards Progress -->
    <div class="reward-card">
        <div>⭐ Rewards Points</div>
        <div class="reward-points"><?php echo number_format($rewardPoints); ?> points</div>
        <div class="reward-progress"><div class="reward-progress-bar"></div></div>
        <div style="font-size: 12px;">🎯 <?php echo 100 - ($rewardPoints % 100); ?> more points for 100 Br discount!</div>
    </div>
    
    <!-- Recent Orders -->
    <?php if(!empty($recentOrders)): ?>
    <div class="section-header">
        <div class="section-title">🕐 Recent Orders</div>
        <a href="/customer/orders.php" class="section-link">See all →</a>
    </div>
    <div class="orders-card">
        <?php foreach($recentOrders as $order): ?>
        <div class="order-item" onclick="window.location='/customer/order-detail.php?id=<?php echo $order['id']; ?>'">
            <div class="order-info">
                <div class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                <div class="order-restaurant"><?php echo htmlspecialchars($order['restaurant_name']); ?></div>
            </div>
            <div class="order-amount"><?php echo number_format($order['total_amount'], 0); ?> Br</div>
            <span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Favorite Restaurants -->
    <?php if(!empty($favoriteRestaurants)): ?>
    <div class="section-header">
        <div class="section-title">❤️ Your Favorites</div>
        <a href="/customer/restaurant-feed.php" class="section-link">More →</a>
    </div>
    <div class="restaurants-scroll">
        <div class="restaurants-grid">
            <?php foreach($favoriteRestaurants as $r): ?>
            <a href="/customer/restaurant.php?id=<?php echo $r['id']; ?>" class="restaurant-card">
                <div class="restaurant-image">🍽️</div>
                <div class="restaurant-info">
                    <div class="restaurant-name"><?php echo htmlspecialchars($r['name']); ?></div>
                    <div class="restaurant-cuisine"><?php echo htmlspecialchars($r['cuisine_type']); ?></div>
                    <div class="restaurant-meta">
                        <span class="rating">⭐ <?php echo number_format($r['rating'],1); ?></span>
                        <span><?php echo $r['order_count']; ?> orders</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recommended Restaurants -->
    <div class="section-header">
        <div class="section-title">✨ Recommended for You</div>
        <a href="/customer/restaurant-feed.php" class="section-link">More →</a>
    </div>
    <div class="restaurants-scroll">
        <div class="restaurants-grid">
            <?php foreach($recommendedRestaurants as $r): ?>
            <a href="/customer/restaurant.php?id=<?php echo $r['id']; ?>" class="restaurant-card">
                <div class="restaurant-image">🍽️</div>
                <div class="restaurant-info">
                    <div class="restaurant-name"><?php echo htmlspecialchars($r['name']); ?></div>
                    <div class="restaurant-cuisine"><?php echo htmlspecialchars($r['cuisine_type']); ?></div>
                    <div class="restaurant-meta">
                        <span class="rating">⭐ <?php echo number_format($r['rating'],1); ?></span>
                        <span>📍 <?php echo htmlspecialchars($r['location']); ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Bottom Navigation - Mobile First -->
    <div class="bottom-nav">
        <a href="/customer/dashboard.php" class="nav-item active">
            <span class="nav-icon">🏠</span>
            <span>Home</span>
        </a>
        <a href="/customer/orders.php" class="nav-item">
            <span class="nav-icon">📦</span>
            <span>Orders</span>
        </a>
        <a href="/customer/cart.php" class="nav-item">
            <span class="nav-icon">🛒</span>
            <span>Cart</span>
        </a>
        <a href="/customer/profile.php" class="nav-item">
            <span class="nav-icon">👤</span>
            <span>Profile</span>
        </a>
    </div>
    
    <script>
        // Dark Mode Toggle
        const darkToggle = document.getElementById('darkModeToggle');
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('night');
            darkToggle.textContent = '☀️';
        }
        darkToggle.addEventListener('click', () => {
            document.body.classList.toggle('night');
            const isDark = document.body.classList.contains('night');
            localStorage.setItem('darkMode', isDark);
            darkToggle.textContent = isDark ? '☀️' : '🌙';
        });
    </script>
</body>
</html>
EOF