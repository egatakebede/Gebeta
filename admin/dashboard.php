cat > /home/e/Gebeta/admin/dashboard.php << 'EOF'
<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

// ============================================
// USER STATS
// ============================================
$stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "customer"');
$totalCustomers = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "restaurant"');
$totalRestaurantOwners = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "admin"');
$totalAdmins = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM users');
$totalUsers = $stmt->fetchColumn();

// ============================================
// RESTAURANT STATS
// ============================================
$stmt = $pdo->query('SELECT COUNT(*) FROM restaurants WHERE status = "active"');
$activeRestaurants = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM restaurants WHERE status = "pending"');
$pendingRestaurants = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM restaurants WHERE status = "suspended"');
$suspendedRestaurants = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM restaurants');
$totalRestaurants = $stmt->fetchColumn();

// ============================================
// DELIVERY PARTNER STATS
// ============================================
$stmt = $pdo->query('SELECT COUNT(*) FROM delivery_partners');
$totalDeliveryPartners = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM delivery_partners WHERE status = "online"');
$onlineDeliveryPartners = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM delivery_partners WHERE is_available = 1');
$availableDeliveryPartners = $stmt->fetchColumn();

// ============================================
// ORDER STATS
// ============================================
$stmt = $pdo->query('SELECT COUNT(*) FROM orders');
$totalOrders = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "pending"');
$pendingOrders = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "confirmed"');
$confirmedOrders = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "preparing"');
$preparingOrders = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "ready"');
$readyOrders = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "out_for_delivery"');
$outForDeliveryOrders = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "delivered"');
$deliveredOrders = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "cancelled"');
$cancelledOrders = $stmt->fetchColumn();

// ============================================
// REVENUE STATS
// ============================================
$stmt = $pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders');
$totalRevenue = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = "delivered"');
$completedRevenue = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE()');
$todayRevenue = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(SUM(delivery_fee), 0) FROM orders');
$totalDeliveryFees = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(AVG(total_amount), 0) FROM orders');
$avgOrderValue = $stmt->fetchColumn();

// ============================================
// TODAY'S STATS
// ============================================
$stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()');
$todayOrders = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())');
$monthRevenue = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())');
$monthOrders = $stmt->fetchColumn();

// ============================================
// UNIQUE CUSTOMERS
// ============================================
$stmt = $pdo->query('SELECT COUNT(DISTINCT user_id) FROM orders');
$uniqueCustomers = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(DISTINCT restaurant_id) FROM orders');
$activeRestaurantsWithOrders = $stmt->fetchColumn();

// ============================================
// DELIVERY REQUESTS (order_deliveries)
// ============================================
$stmt = $pdo->query('SELECT COUNT(*) FROM order_deliveries WHERE status = "pending"');
$pendingDeliveries = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM order_deliveries WHERE status = "assigned"');
$assignedDeliveries = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM order_deliveries WHERE status = "delivered"');
$completedDeliveries = $stmt->fetchColumn();

// ============================================
// PAYMENTS
// ============================================
$stmt = $pdo->query('SELECT COUNT(*) FROM payments WHERE status = "pending"');
$pendingPayments = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = "completed"');
$completedPayments = $stmt->fetchColumn();

// ============================================
// MENU & CATEGORIES
// ============================================
$stmt = $pdo->query('SELECT COUNT(*) FROM menu_items');
$totalMenuItems = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM menu_items WHERE is_available = 1');
$availableMenuItems = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM categories');
$totalCategories = $stmt->fetchColumn();

// ============================================
// REVIEWS
// ============================================
$stmt = $pdo->query('SELECT COUNT(*) FROM reviews');
$totalReviews = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE rating > 0');
$avgRating = $stmt->fetchColumn();

// ============================================
// RECENT ORDERS
// ============================================
$stmt = $pdo->query('
    SELECT o.id, o.order_number, o.status, o.total_amount, o.delivery_fee, o.created_at,
           u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
           r.name AS restaurant_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN restaurants r ON o.restaurant_id = r.id
    ORDER BY o.created_at DESC
    LIMIT 15
');
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// RECENT CUSTOMERS
// ============================================
$stmt = $pdo->query('
    SELECT id, name, email, phone, created_at
    FROM users
    WHERE role = "customer"
    ORDER BY created_at DESC
    LIMIT 10
');
$recentCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// TOP RESTAURANTS
// ============================================
$stmt = $pdo->query('
    SELECT r.id, r.name, r.cuisine_type, r.rating, r.location,
           COUNT(o.id) AS order_count,
           COALESCE(SUM(o.total_amount), 0) AS total_revenue
    FROM restaurants r
    LEFT JOIN orders o ON r.id = o.restaurant_id
    GROUP BY r.id
    ORDER BY order_count DESC
    LIMIT 10
');
$topRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// TOP CUSTOMERS
// ============================================
$stmt = $pdo->query('
    SELECT u.id, u.name, u.email, u.phone,
           COUNT(o.id) AS order_count,
           COALESCE(SUM(o.total_amount), 0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role = "customer"
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 10
');
$topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// TOP DISHES (menu_items)
// ============================================
$stmt = $pdo->query('
    SELECT m.id, m.name, m.price, r.name AS restaurant_name,
           COALESCE(SUM(oi.quantity), 0) AS total_quantity,
           COALESCE(COUNT(oi.id), 0) AS sold_count
    FROM menu_items m
    JOIN restaurants r ON m.restaurant_id = r.id
    LEFT JOIN order_items oi ON m.id = oi.menu_item_id
    GROUP BY m.id
    ORDER BY total_quantity DESC
    LIMIT 10
');
$topDishes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// MONTHLY REVENUE (last 12 months)
// ============================================
$stmt = $pdo->query('
    SELECT 
        DATE_FORMAT(created_at, "%Y-%m") as month,
        DATE_FORMAT(created_at, "%M %Y") as month_name,
        COALESCE(SUM(total_amount), 0) as revenue,
        COUNT(*) as orders,
        COALESCE(AVG(total_amount), 0) as avg_order
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, "%Y-%m")
    ORDER BY month ASC
');
$monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// DAILY REVENUE (last 30 days)
// ============================================
$stmt = $pdo->query('
    SELECT 
        DATE(created_at) as day,
        COALESCE(SUM(total_amount), 0) as revenue,
        COUNT(*) as orders
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
');
$dailyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// HOURLY DISTRIBUTION
// ============================================
$stmt = $pdo->query('
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as orders,
        COALESCE(SUM(total_amount), 0) as revenue
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY HOUR(created_at)
    ORDER BY hour ASC
');
$hourlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// PAYMENT METHOD STATS
// ============================================
$stmt = $pdo->query('
    SELECT 
        payment_method,
        COUNT(*) as count,
        COALESCE(SUM(total_amount), 0) as total
    FROM orders
    WHERE payment_method IS NOT NULL
    GROUP BY payment_method
');
$paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// DELIVERY STATS (order_deliveries)
// ============================================
$stmt = $pdo->query('
    SELECT 
        status,
        COUNT(*) as count,
        COALESCE(AVG(distance_km), 0) as avg_distance
    FROM order_deliveries
    GROUP BY status
');
$deliveryStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// RECENT REVIEWS
// ============================================
$stmt = $pdo->query('
    SELECT r.id, r.rating, r.comment, r.created_at,
           u.name AS customer_name,
           res.name AS restaurant_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN restaurants res ON r.restaurant_id = res.id
    ORDER BY r.created_at DESC
    LIMIT 10
');
$recentReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// RECENT RESTAURANTS
// ============================================
$stmt = $pdo->query('
    SELECT r.id, r.name, r.cuisine_type, r.location, r.status, r.rating,
           u.name AS owner_name
    FROM restaurants r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 10
');
$recentRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// DAILY GROWTH (last 7 days)
// ============================================
$stmt = $pdo->query('
    SELECT 
        DATE(created_at) as day,
        COUNT(*) as new_users
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
');
$userGrowth = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query('
    SELECT 
        DATE(created_at) as day,
        COUNT(*) as new_restaurants
    FROM restaurants
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
');
$restaurantGrowth = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// COMMISSION (15%)
// ============================================
$commissionRate = 0.15;
$totalCommission = $totalRevenue * $commissionRate;
$monthCommission = $monthRevenue * $commissionRate;

// ============================================
// LAST HOUR ORDERS
// ============================================
$stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)');
$ordersLastHour = $stmt->fetchColumn();

// ============================================
// NEW USERS TODAY
// ============================================
$stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()');
$newUsersToday = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM restaurants WHERE DATE(created_at) = CURDATE()');
$newRestaurantsToday = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard · Gebeta</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;background:#F3F4F6}
        .admin-layout{display:flex;min-height:100vh}
        .admin-sidebar{width:260px;background:#fff;border-right:1px solid #E5E7EB;position:fixed;top:0;left:0;bottom:0;overflow-y:auto}
        .admin-sidebar.collapsed{width:80px}
        .admin-sidebar.collapsed .sidebar-title,.admin-sidebar.collapsed .nav-item span:not(.icon){display:none}
        .admin-main{flex:1;margin-left:260px;transition:.3s}
        .admin-main.expanded{margin-left:80px}
        .admin-header{background:#fff;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #E5E7EB;position:sticky;top:0;z-index:100}
        .sidebar-header{padding:1.5rem;display:flex;align-items:center;gap:.75rem;border-bottom:1px solid #E5E7EB}
        .sidebar-logo{width:40px;height:40px;background:#FC8019;color:#fff;display:flex;align-items:center;justify-content:center;border-radius:10px;font-weight:700;font-size:1.25rem}
        .sidebar-title{font-size:1.25rem;font-weight:700}
        .sidebar-nav{padding:1rem}
        .nav-item{display:flex;align-items:center;gap:.75rem;padding:.75rem;color:#4B5563;text-decoration:none;border-radius:.5rem;margin-bottom:.25rem}
        .nav-item:hover{background:#F3F4F6}
        .nav-item.active{background:#FEF3C7;color:#FC8019}
        .admin-content{padding:2rem}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;margin-bottom:2rem}
        .stat-card{background:#fff;padding:1.5rem;border-radius:1rem;border:1px solid #E5E7EB}
        .stat-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem}
        .stat-label{font-size:.75rem;font-weight:600;color:#6B7280;text-transform:uppercase}
        .stat-value{font-size:1.875rem;font-weight:700;color:#1F2937}
        .stat-trend{font-size:.75rem;color:#6B7280;margin-top:.5rem}
        .stat-trend.up{color:#10B981}
        .stat-trend.down{color:#EF4444}
        .charts-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1.5rem;margin-bottom:2rem}
        .chart-card{background:#fff;border-radius:1rem;border:1px solid #E5E7EB;padding:1.5rem}
        .chart-title{font-size:1rem;font-weight:700;margin-bottom:1rem}
        .chart-container{height:300px}
        .data-table-container{background:#fff;border-radius:1rem;border:1px solid #E5E7EB;overflow-x:auto;margin-bottom:2rem}
        .table-header{display:flex;justify-content:space-between;align-items:center;padding:1rem 1.5rem;border-bottom:1px solid #E5E7EB}
        .table-title{font-size:1.125rem;font-weight:700}
        .data-table{width:100%;border-collapse:collapse}
        .data-table th,.data-table td{padding:.75rem 1rem;text-align:left;border-bottom:1px solid #E5E7EB}
        .data-table th{background:#F9FAFB;font-weight:600;font-size:.75rem;color:#6B7280}
        .status-badge{display:inline-block;padding:.25rem .5rem;border-radius:9999px;font-size:.75rem;font-weight:600}
        .status-pending{background:#FEF3C7;color:#D97706}
        .status-delivered{background:#D1FAE5;color:#059669}
        .status-active{background:#D1FAE5;color:#059669}
        .status-cancelled{background:#FEE2E2;color:#DC2626}
        .btn-primary{background:#FC8019;color:#fff;padding:.5rem 1rem;border-radius:.5rem;text-decoration:none;font-size:.75rem}
        .btn-secondary{background:#F3F4F6;color:#4B5563;padding:.5rem 1rem;border-radius:.5rem;text-decoration:none;font-size:.75rem}
        .btn-refresh{background:#FC8019;border:none;color:#fff;padding:.5rem 1rem;border-radius:.5rem;cursor:pointer}
        .dark-mode-toggle{background:0 0;border:none;font-size:1.25rem;cursor:pointer;padding:.5rem}
        .admin-theme.night{background:#111827}
        .admin-theme.night .admin-sidebar,.admin-theme.night .admin-header,.admin-theme.night .stat-card,.admin-theme.night .chart-card,.admin-theme.night .data-table-container{background:#1F2937;border-color:#374151}
        .admin-theme.night .stat-value,.admin-theme.night .chart-title,.admin-theme.night .table-title{color:#F9FAFB}
        .admin-theme.night .stat-label,.admin-theme.night .data-table td{color:#D1D5DB}
        .admin-theme.night .data-table th{background:#111827;color:#9CA3AF}
        @media(max-width:768px){.admin-sidebar{transform:translateX(-100%)}.admin-main{margin-left:0}.charts-grid{grid-template-columns:1fr}}
    </style>
</head>
<body class="admin-theme" id="adminThemeRoot">
<div class="admin-layout">
    <aside class="admin-sidebar" id="sidebar">
        <div class="sidebar-header"><div class="sidebar-logo">G</div><div class="sidebar-title">Gebeta Admin</div></div>
        <nav class="sidebar-nav">
            <div class="nav-item active"><span class="icon">📊</span><span>Dashboard</span></div>
            <a href="/admin/restaurants.php" class="nav-item"><span class="icon">🏪</span><span>Restaurants</span></a>
            <a href="/admin/orders.php" class="nav-item"><span class="icon">📦</span><span>Orders</span></a>
            <a href="/admin/users.php" class="nav-item"><span class="icon">👥</span><span>Users</span></a>
            <a href="/admin/menu-items.php" class="nav-item"><span class="icon">🍽️</span><span>Menu Items</span></a>
            <a href="/admin/delivery.php" class="nav-item"><span class="icon">🚚</span><span>Delivery</span></a>
            <a href="/admin/reviews.php" class="nav-item"><span class="icon">⭐</span><span>Reviews</span></a>
            <a href="/admin/payments.php" class="nav-item"><span class="icon">💳</span><span>Payments</span></a>
            <a href="/logout.php" class="nav-item"><span class="icon">🚪</span><span>Logout</span></a>
        </nav>
    </aside>
    <main class="admin-main" id="mainContent">
        <header class="admin-header">
            <button class="btn-refresh" onclick="location.reload()">🔄 Refresh</button>
            <div><button class="dark-mode-toggle" id="darkModeToggle">🌙</button></div>
        </header>
        <div class="admin-content">
            <h1 style="margin-bottom:1.5rem">Admin Dashboard</h1>
            
            <!-- Stats Row 1 -->
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-header"><span class="stat-label">Customers</span><span>👥</span></div><div class="stat-value"><?php echo number_format($totalCustomers); ?></div><div class="stat-trend up">+<?php echo $newUsersToday; ?> today</div></div>
                <div class="stat-card"><div class="stat-header"><span class="stat-label">Restaurants</span><span>🏪</span></div><div class="stat-value"><?php echo number_format($activeRestaurants); ?> / <?php echo $totalRestaurants; ?></div><div class="stat-trend"><?php echo $pendingRestaurants; ?> pending</div></div>
                <div class="stat-card"><div class="stat-header"><span class="stat-label">Delivery Partners</span><span>🚚</span></div><div class="stat-value"><?php echo number_format($totalDeliveryPartners); ?></div><div class="stat-trend"><?php echo $onlineDeliveryPartners; ?> online</div></div>
                <div class="stat-card"><div class="stat-header"><span class="stat-label">Menu Items</span><span>🍽️</span></div><div class="stat-value"><?php echo number_format($totalMenuItems); ?></div><div class="stat-trend"><?php echo $availableMenuItems; ?> available</div></div>
            </div>
            
            <!-- Stats Row 2 -->
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-header"><span class="stat-label">Total Orders</span><span>📦</span></div><div class="stat-value"><?php echo number_format($totalOrders); ?></div><div class="stat-trend"><?php echo $todayOrders; ?> today</div></div>
                <div class="stat-card"><div class="stat-header"><span class="stat-label">Pending Orders</span><span>⏳</span></div><div class="stat-value"><?php echo number_format($pendingOrders); ?></div><div class="stat-trend">Need action</div></div>
                <div class="stat-card"><div class="stat-header"><span class="stat-label">Delivered</span><span>✅</span></div><div class="stat-value"><?php echo number_format($deliveredOrders); ?></div><div class="stat-trend"><?php echo $totalOrders ? round(($deliveredOrders/$totalOrders)*100,1) : 0; ?>% complete</div></div>
                <div class="stat-card"><div class="stat-header"><span class="stat-label">Out for Delivery</span><span>🚚</span></div><div class="stat-value"><?php echo number_format($outForDeliveryOrders); ?></div><div class="stat-trend">On the way</div></div>
            </div>
            
            <!-- Stats Row 3 -->
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-header"><span class="stat-label">Total Revenue</span><span>💰</span></div><div class="stat-value"><?php echo number_format($totalRevenue, 0); ?> Birr</div><div class="stat-trend up">+<?php echo number_format($todayRevenue, 0); ?> today</div></div>
                <div class="stat-card"><div class="stat-header"><span class="stat-label">This Month</span><span>📅</span></div><div class="stat-value"><?php echo number_format($monthRevenue, 0); ?> Birr</div><div class="stat-trend"><?php echo $monthOrders; ?> orders</div></div>
                <div class="stat-card"><div class="stat-header"><span class="stat-label">Avg Order Value</span><span>📊</span></div><div class="stat-value"><?php echo number_format($avgOrderValue, 2); ?> Birr</div><div class="stat-trend">Per order</div></div>
                <div class="stat-card"><div class="stat-header"><span class="stat-label">Commission (15%)</span><span>💸</span></div><div class="stat-value"><?php echo number_format($totalCommission, 0); ?> Birr</div><div class="stat-trend"><?php echo number_format($monthCommission, 0); ?> this month</div></div>
            </div>
            
            <!-- Charts -->
            <div class="charts-grid">
                <div class="chart-card"><h3 class="chart-title">Monthly Revenue (Last 12 Months)</h3><canvas id="revenueChart" style="height:250px"></canvas></div>
                <div class="chart-card"><h3 class="chart-title">Monthly Orders (Last 12 Months)</h3><canvas id="ordersChart" style="height:250px"></canvas></div>
                <div class="chart-card"><h3 class="chart-title">Peak Hours (Last 30 Days)</h3><canvas id="peakHoursChart" style="height:250px"></canvas></div>
                <div class="chart-card"><h3 class="chart-title">Payment Methods</h3><canvas id="paymentChart" style="height:250px"></canvas></div>
            </div>
            
            <!-- Top Restaurants -->
            <div class="data-table-container">
                <div class="table-header"><h3 class="table-title">🏪 Top Restaurants</h3><a href="/admin/restaurants.php" class="btn-secondary">View All</a></div>
                <table class="data-table"><thead><tr><th>#</th><th>Name</th><th>Cuisine</th><th>Orders</th><th>Revenue</th><th>Rating</th><th>Status</th></tr></thead><tbody>
                <?php $i=1; foreach($topRestaurants as $r): ?>
                <tr><td><?php echo $i++; ?></td><td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td><td><?php echo htmlspecialchars($r['cuisine_type']); ?></td><td><?php echo number_format($r['order_count']); ?></td><td><?php echo number_format($r['total_revenue'], 0); ?> Birr</td><td>⭐ <?php echo number_format($r['rating'], 1); ?></td><td><span class="status-badge status-active">Active</span></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </div>
            
            <!-- Recent Orders -->
            <div class="data-table-container">
                <div class="table-header"><h3 class="table-title">📋 Recent Orders</h3><a href="/admin/orders.php" class="btn-secondary">View All</a></div>
                <table class="data-table"><thead><tr><th>Order #</th><th>Customer</th><th>Restaurant</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>
                <?php foreach($recentOrders as $order): ?>
                <tr><td><strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong></td><td><?php echo htmlspecialchars($order['customer_name']); ?></td><td><?php echo htmlspecialchars($order['restaurant_name']); ?></td><td><?php echo number_format($order['total_amount'], 2); ?> Birr</td><td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td><td><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </div>
        </div>
    </main>
</div>
<script>
const monthlyLabels = <?php echo json_encode(array_column($monthlyData, 'month_name')); ?>;
const monthlyRevenue = <?php echo json_encode(array_column($monthlyData, 'revenue')); ?>;
const monthlyOrders = <?php echo json_encode(array_column($monthlyData, 'orders')); ?>;
const hourlyLabels = <?php echo json_encode(array_column($hourlyData, 'hour')); ?>;
const hourlyOrders = <?php echo json_encode(array_column($hourlyData, 'orders')); ?>;
const paymentLabels = <?php echo json_encode(array_column($paymentMethods, 'payment_method')); ?>;
const paymentCounts = <?php echo json_encode(array_column($paymentMethods, 'count')); ?>;

new Chart(document.getElementById('revenueChart'), {type:'line',data:{labels:monthlyLabels,datasets:[{label:'Revenue (Birr)',data:monthlyRevenue,borderColor:'#FC8019',backgroundColor:'rgba(252,128,25,0.1)',fill:true,tension:0.4}]},options:{responsive:true,maintainAspectRatio:true}});
new Chart(document.getElementById('ordersChart'), {type:'bar',data:{labels:monthlyLabels,datasets:[{label:'Orders',data:monthlyOrders,backgroundColor:'#10B981',borderRadius:8}]},options:{responsive:true,maintainAspectRatio:true}});
new Chart(document.getElementById('peakHoursChart'), {type:'bar',data:{labels:hourlyLabels.map(h=>h+':00'),datasets:[{label:'Orders',data:hourlyOrders,backgroundColor:'#FC8019',borderRadius:8}]},options:{responsive:true,maintainAspectRatio:true}});
if(paymentLabels.length){new Chart(document.getElementById('paymentChart'), {type:'pie',data:{labels:paymentLabels,datasets:[{data:paymentCounts,backgroundColor:['#FC8019','#10B981','#3B82F6','#8B5CF6','#EC4899']}]},options:{responsive:true,maintainAspectRatio:true}})}

const sidebar=document.getElementById('sidebar'),main=document.getElementById('mainContent');let collapsed=false;document.getElementById('darkModeToggle')?.addEventListener('click',()=>{document.getElementById('adminThemeRoot').classList.toggle('night')});
</script>
</body>
</html>
EOF