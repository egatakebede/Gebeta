<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(array('customer'));
require_once __DIR__ . '/../includes/db.php';

$userId = $_SESSION['user']['id'];
$userName = $_SESSION['user']['name'];

// Get customer stats
$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
$stmt->execute(array($userId));
$totalOrders = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE user_id = ?');
$stmt->execute(array($userId));
$totalSpent = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare('SELECT COUNT(DISTINCT restaurant_id) FROM orders WHERE user_id = ?');
$stmt->execute(array($userId));
$restaurantsOrdered = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = ?');
$stmt->execute(array($userId, 'delivered'));
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
$stmt->execute(array($userId));
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
$stmt->execute(array($userId));
$favoriteRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recommended restaurants - FIXED
$stmt = $pdo->prepare('
    SELECT id, name, cuisine_type, location, rating
    FROM restaurants
    WHERE status = ?
    ORDER BY rating DESC
    LIMIT 6
');
$stmt->execute(['active']);
$recommendedRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle search - FIXED
$searchResults = [];
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($searchQuery !== '') {
    $stmt = $pdo->prepare('
        SELECT id, name, cuisine_type, location, rating
        FROM restaurants
        WHERE (name LIKE ? OR cuisine_type LIKE ? OR location LIKE ?)
        AND status = ?
        ORDER BY rating DESC
    ');
    $searchParam = "%{$searchQuery}%";
    $stmt->execute([$searchParam, $searchParam, $searchParam, 'active']);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard · Gebeta</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #F9FAFB;
        }
        
        body.night {
            background: #111827;
            color: #F3F4F6;
        }
        
        body.night .admin-sidebar,
        body.night .admin-header,
        body.night .kpi-card,
        body.night .data-table-container,
        body.night .restaurant-card,
        body.night .no-results {
            background: #1F2937;
            border-color: #374151;
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 260px;
            background: white;
            border-right: 1px solid #E5E7EB;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 100;
        }
        
        .admin-sidebar.collapsed {
            width: 80px;
        }
        
        .admin-sidebar.collapsed .sidebar-title,
        .admin-sidebar.collapsed .nav-item span:not(.nav-item-icon),
        .admin-sidebar.collapsed .nav-section-title {
            display: none;
        }
        
        .admin-main {
            flex: 1;
            margin-left: 260px;
            transition: all 0.3s;
        }
        
        .admin-main.expanded {
            margin-left: 80px;
        }
        
        .admin-header {
            background: white;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #E5E7EB;
            position: sticky;
            top: 0;
            z-index: 99;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .header-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
        }
        
        .header-search {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #F3F4F6;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            flex: 1;
            max-width: 400px;
        }
        
        .header-search-input {
            border: none;
            background: none;
            outline: none;
            flex: 1;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .header-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #FC8019, #E56B0F);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .sidebar-logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #FC8019, #E56B0F);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-weight: bold;
            font-size: 1.25rem;
        }
        
        .sidebar-title {
            font-size: 1.25rem;
            font-weight: bold;
        }
        
        .sidebar-nav {
            padding: 1rem;
        }
        
        .nav-section {
            margin-bottom: 1.5rem;
        }
        
        .nav-section-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #9CA3AF;
            margin-bottom: 0.5rem;
            padding: 0 0.75rem;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            color: #4B5563;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        .nav-item:hover {
            background: #F3F4F6;
        }
        
        .nav-item.active {
            background: #FEF3C7;
            color: #FC8019;
        }
        
        .nav-item-badge {
            margin-left: auto;
            background: #FC8019;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
        }
        
        .admin-content {
            padding: 2rem;
        }
        
        .content-header {
            margin-bottom: 2rem;
        }
        
        .content-title {
            font-size: 1.875rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .kpi-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border: 1px solid #E5E7EB;
        }
        
        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .kpi-label {
            font-size: 0.875rem;
            color: #6B7280;
        }
        
        .kpi-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .data-table-container {
            background: white;
            border-radius: 1rem;
            border: 1px solid #E5E7EB;
            overflow-x: auto;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .table-title {
            font-size: 1.125rem;
            font-weight: bold;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .data-table th {
            background: #F9FAFB;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-badge.pending { background: #FEF3C7; color: #D97706; }
        .status-badge.confirmed { background: #DBEAFE; color: #2563EB; }
        .status-badge.preparing { background: #DBEAFE; color: #2563EB; }
        .status-badge.ready { background: #DBEAFE; color: #2563EB; }
        .status-badge.out_for_delivery { background: #DBEAFE; color: #2563EB; }
        .status-badge.delivered { background: #D1FAE5; color: #059669; }
        .status-badge.cancelled { background: #FEE2E2; color: #DC2626; }
        
        .action-btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-decoration: none;
        }
        
        .action-btn-primary {
            background: #FC8019;
            color: white;
        }
        
        .action-btn-secondary {
            background: #F3F4F6;
            color: #4B5563;
        }
        
        .btn-refresh {
            background: linear-gradient(135deg, #FC8019, #E56B0F);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }
        
        .restaurant-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid #E5E7EB;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
            overflow: hidden;
        }
        
        .restaurant-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(-4px);
        }
        
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .mt-8 {
            margin-top: 2rem;
        }
        
        .mb-6 {
            margin-bottom: 1.5rem;
        }
        
        .text-2xl {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .dark-mode-toggle {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
        }
        
        .search-results-section {
            margin-bottom: 2rem;
        }
        
        .search-results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .no-results {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 1rem;
            border: 1px solid #E5E7EB;
        }
        
        .search-btn {
            background: #FC8019;
            color: white;
            border: none;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-260px);
                transition: transform 0.3s;
            }
            
            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: block;
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 0.5rem;
            }
            
            .header-toggle {
                display: none;
            }
            
            .admin-header {
                padding: 1rem;
            }
            
            .admin-content {
                padding: 1rem;
            }
            
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .grid-3, .search-results-grid {
                grid-template-columns: 1fr;
            }
            
            .content-title {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .mobile-menu-btn {
            display: none;
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 99;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
    </style>
</head>
<body id="adminThemeRoot">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="admin-layout">
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
                        <span class="nav-item-badge"><?php echo $totalOrders; ?></span>
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
        
        <main class="admin-main" id="mainContent">
            <header class="admin-header">
                <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
                <button class="header-toggle" id="sidebarToggle">☰</button>
                
                <form class="header-search" method="GET" action="" style="display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    <span>🔍</span>
                    <input type="text" name="search" class="header-search-input" placeholder="Search restaurants..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button type="submit" class="search-btn">Go</button>
                </form>
                
                <div class="header-actions">
                    <button class="dark-mode-toggle" id="darkModeToggle">🌙</button>
                    <button class="btn-refresh" onclick="location.reload()">🔄 Refresh</button>
                    
                    <div class="header-profile">
                        <div class="header-avatar"><?php echo strtoupper(substr($userName, 0, 1)); ?></div>
                        <div>
                            <div><?php echo htmlspecialchars($userName); ?></div>
                            <div style="font-size: 12px; color: #6B7280;">Customer</div>
                        </div>
                    </div>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($searchQuery !== ''): ?>
                <div class="search-results-section">
                    <h2 class="text-2xl mb-6">Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h2>
                    <?php if (!empty($searchResults)): ?>
                    <div class="search-results-grid">
                        <?php foreach ($searchResults as $restaurant): ?>
                        <a href="/customer/restaurant.php?id=<?php echo $restaurant['id']; ?>" class="restaurant-card">
                            <div style="height: 160px; background: linear-gradient(135deg, #FEF3C7, #FDE68A); display: flex; align-items: center; justify-content: center; font-size: 4rem;">🍽️</div>
                            <div style="padding: 1rem;">
                                <h3 style="font-weight: bold; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                                <p style="font-size: 0.75rem; color: #6B7280; margin-bottom: 0.75rem;"><?php echo htmlspecialchars($restaurant['cuisine_type']); ?></p>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>📍 <?php echo htmlspecialchars($restaurant['location']); ?></span>
                                    <span style="color: #EA580C; font-weight: 600;">⭐ <?php echo number_format($restaurant['rating'], 1); ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="no-results">
                        <p>No restaurants found matching "<?php echo htmlspecialchars($searchQuery); ?>"</p>
                        <p style="margin-top: 1rem; color: #6B7280;">Try searching by name, cuisine type, or location</p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                
                <div class="content-header">
                    <h1 class="content-title">Welcome back, <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>! 👋</h1>
                    <p>Discover delicious food from your favorite restaurants</p>
                </div>
                
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Orders</span>
                            <span>📦</span>
                        </div>
                        <div class="kpi-value"><?php echo number_format($totalOrders); ?></div>
                        <div><?php echo $completedOrders; ?> completed</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Spent</span>
                            <span>💰</span>
                        </div>
                        <div class="kpi-value"><?php echo number_format($totalSpent, 0); ?> Birr</div>
                        <div>Lifetime spending</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Restaurants</span>
                            <span>🏪</span>
                        </div>
                        <div class="kpi-value"><?php echo number_format($restaurantsOrdered); ?></div>
                        <div>Unique restaurants</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Rewards Points</span>
                            <span>⭐</span>
                        </div>
                        <div class="kpi-value"><?php echo number_format($totalOrders * 10); ?></div>
                        <div>Earn more points!</div>
                    </div>
                </div>
                
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
                                <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($order['restaurant_name']); ?><br>
                                    <small><?php echo htmlspecialchars($order['cuisine_type']); ?></small>
                                </td>
                                <td><strong><?php echo number_format($order['total_amount'], 2); ?> Birr</strong></td>
                                <td><span class="status-badge <?php echo $order['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td><a href="/customer/order-detail.php?id=<?php echo $order['id']; ?>" class="action-btn action-btn-primary">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($favoriteRestaurants)): ?>
                <div class="mt-8">
                    <h2 class="text-2xl mb-6">Your Favorite Restaurants</h2>
                    <div class="grid-3">
                        <?php foreach ($favoriteRestaurants as $restaurant): ?>
                        <a href="/customer/restaurant.php?id=<?php echo $restaurant['id']; ?>" class="restaurant-card">
                            <div style="padding: 1.5rem;">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #FB923C, #EA580C); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 1.75rem;">🍽️</div>
                                    <div>
                                        <h3 style="font-weight: bold;"><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                                        <p style="font-size: 0.75rem; color: #6B7280;"><?php echo htmlspecialchars($restaurant['cuisine_type']); ?></p>
                                    </div>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>⭐ <?php echo number_format($restaurant['rating'], 1); ?></span>
                                    <span style="color: #EA580C; font-weight: 600;"><?php echo $restaurant['order_count']; ?> orders</span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mt-8">
                    <h2 class="text-2xl mb-6">Recommended for You</h2>
                    <div class="grid-3">
                        <?php foreach ($recommendedRestaurants as $restaurant): ?>
                        <a href="/customer/restaurant.php?id=<?php echo $restaurant['id']; ?>" class="restaurant-card">
                            <div style="height: 160px; background: linear-gradient(135deg, #FEF3C7, #FDE68A); display: flex; align-items: center; justify-content: center; font-size: 4rem;">🍽️</div>
                            <div style="padding: 1rem;">
                                <h3 style="font-weight: bold; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                                <p style="font-size: 0.75rem; color: #6B7280; margin-bottom: 0.75rem;"><?php echo htmlspecialchars($restaurant['cuisine_type']); ?></p>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>📍 <?php echo htmlspecialchars($restaurant['location']); ?></span>
                                    <span style="color: #EA580C; font-weight: 600;">⭐ <?php echo number_format($restaurant['rating'], 1); ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        var sidebar = document.getElementById('sidebar');
        var main = document.getElementById('mainContent');
        var toggleBtn = document.getElementById('sidebarToggle');
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                main.classList.toggle('expanded');
            });
        }
        
        var mobileBtn = document.getElementById('mobileMenuBtn');
        var overlay = document.getElementById('sidebarOverlay');
        
        function closeMobileMenu() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        }
        
        function openMobileMenu() {
            sidebar.classList.add('mobile-open');
            overlay.classList.add('active');
        }
        
        if (mobileBtn) {
            mobileBtn.addEventListener('click', openMobileMenu);
        }
        
        if (overlay) {
            overlay.addEventListener('click', closeMobileMenu);
        }
        
        var themeRoot = document.getElementById('adminThemeRoot');
        var darkToggle = document.getElementById('darkModeToggle');
        var themeKey = 'gebeta_customer_theme';
        
        function applyTheme(theme) {
            if (theme === 'night') {
                themeRoot.classList.add('night');
                darkToggle.textContent = '☀️';
            } else {
                themeRoot.classList.remove('night');
                darkToggle.textContent = '🌙';
            }
        }
        
        var saved = localStorage.getItem(themeKey);
        if (saved === 'night') {
            applyTheme('night');
        }
        
        if (darkToggle) {
            darkToggle.addEventListener('click', function() {
                var isNight = themeRoot.classList.contains('night');
                var newTheme = isNight ? 'day' : 'night';
                localStorage.setItem(themeKey, newTheme);
                applyTheme(newTheme);
            });
        }
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });
    </script>
</body>
</html>