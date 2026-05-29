cat > /home/e/Gebeta/admin/dashboard.php << 'EOF'
<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

// ============================================
// USER STATS - FIXED for your actual schema
// ============================================

// Try to get role column - if it doesn't exist, use fallback
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $hasRoleColumn = $stmt->fetch() ? true : false;
} catch (PDOException $e) {
    $hasRoleColumn = false;
}

if ($hasRoleColumn) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
    $totalCustomers = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'restaurant'");
    $totalRestaurantOwners = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $totalAdmins = $stmt->fetchColumn();
} else {
    // Fallback - count all users as customers
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalCustomers = $stmt->fetchColumn();
    $totalRestaurantOwners = 0;
    $totalAdmins = 0;
}

$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmt->fetchColumn();

// ============================================
// RESTAURANT STATS
// ============================================
$stmt = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE status = 'active'");
$activeRestaurants = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE status = 'pending'");
$pendingRestaurants = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM restaurants");
$totalRestaurants = $stmt->fetchColumn();

// ============================================
// DELIVERY PARTNER STATS
// ============================================
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM delivery_partners");
    $totalDeliveryPartners = $stmt->fetchColumn();
} catch (PDOException $e) {
    $totalDeliveryPartners = 0;
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM delivery_partners WHERE status = 'online'");
    $onlineDeliveryPartners = $stmt->fetchColumn();
} catch (PDOException $e) {
    $onlineDeliveryPartners = 0;
}

// ============================================
// ORDER STATS
// ============================================
$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$totalOrders = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$pendingOrders = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'");
$deliveredOrders = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'");
$cancelledOrders = $stmt->fetchColumn();

// ============================================
// REVENUE STATS
// ============================================
$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders");
$totalRevenue = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE()");
$todayRevenue = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(AVG(total_amount), 0) FROM orders");
$avgOrderValue = $stmt->fetchColumn();

// ============================================
// TODAY'S STATS
// ============================================
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
$todayOrders = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE MONTH(created_at) = MONTH(CURDATE())");
$monthRevenue = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE MONTH(created_at) = MONTH(CURDATE())");
$monthOrders = $stmt->fetchColumn();

// ============================================
// MENU & CATEGORIES
// ============================================
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM menu_items");
    $totalMenuItems = $stmt->fetchColumn();
} catch (PDOException $e) {
    $totalMenuItems = 0;
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_available = 1");
    $availableMenuItems = $stmt->fetchColumn();
} catch (PDOException $e) {
    $availableMenuItems = 0;
}

// ============================================
// REVIEWS
// ============================================
try {
    $stmt = $pdo->query("SELECT COALESCE(AVG(rating), 0) FROM reviews");
    $avgRating = $stmt->fetchColumn();
} catch (PDOException $e) {
    $avgRating = 0;
}

// ============================================
// RECENT ORDERS
// ============================================
try {
    $stmt = $pdo->query("
        SELECT o.id, o.order_number, o.status, o.total_amount, o.created_at,
               u.name AS customer_name, r.name AS restaurant_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN restaurants r ON o.restaurant_id = r.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentOrders = [];
}

// ============================================
// TOP RESTAURANTS
// ============================================
try {
    $stmt = $pdo->query("
        SELECT r.id, r.name, r.cuisine_type, r.rating,
               COUNT(o.id) AS order_count,
               COALESCE(SUM(o.total_amount), 0) AS total_revenue
        FROM restaurants r
        LEFT JOIN orders o ON r.id = o.restaurant_id
        GROUP BY r.id
        ORDER BY order_count DESC
        LIMIT 5
    ");
    $topRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $topRestaurants = [];
}

// ============================================
// NEW USERS TODAY
// ============================================
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
$newUsersToday = $stmt->fetchColumn();

// ============================================
// COMMISSION (15%)
// ============================================
$commissionRate = 0.15;
$totalCommission = $totalRevenue * $commissionRate;
$monthCommission = $monthRevenue * $commissionRate;

// ============================================
// MONTHLY REVENUE DATA FOR CHART
// ============================================
$monthlyLabels = [];
$monthlyRevenue = [];
$monthlyOrders = [];

try {
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%M %Y') as month_name,
            COALESCE(SUM(total_amount), 0) as revenue,
            COUNT(*) as orders
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY MIN(created_at) ASC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $monthlyLabels[] = $row['month_name'];
        $monthlyRevenue[] = (float)$row['revenue'];
        $monthlyOrders[] = (int)$row['orders'];
    }
} catch (PDOException $e) {
    // No data yet
}

// ============================================
// PEAK HOURS DATA
// ============================================
$hourlyLabels = [];
$hourlyOrders = [];
try {
    $stmt = $pdo->query("
        SELECT 
            HOUR(created_at) as hour,
            COUNT(*) as orders
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY HOUR(created_at)
        ORDER BY hour ASC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hourlyLabels[] = (int)$row['hour'];
        $hourlyOrders[] = (int)$row['orders'];
    }
} catch (PDOException $e) {
    // No data yet
}

// ============================================
// PAYMENT METHOD STATS
// ============================================
$paymentLabels = [];
$paymentCounts = [];
try {
    $stmt = $pdo->query("
        SELECT 
            payment_method,
            COUNT(*) as count
        FROM orders
        WHERE payment_method IS NOT NULL
        GROUP BY payment_method
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $paymentLabels[] = $row['payment_method'];
        $paymentCounts[] = (int)$row['count'];
    }
} catch (PDOException $e) {
    // No data yet
}

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
        .container{max-width:1400px;margin:0 auto;padding:20px}
        h1{color:#1F2937;margin-bottom:20px}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;margin-bottom:30px}
        .stat-card{background:#fff;padding:20px;border-radius:12px;border:1px solid #E5E7EB;box-shadow:0 1px 2px rgba(0,0,0,0.05)}
        .stat-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
        .stat-label{font-size:12px;font-weight:600;color:#6B7280;text-transform:uppercase}
        .stat-value{font-size:32px;font-weight:700;color:#1F2937}
        .stat-trend{font-size:12px;color:#6B7280;margin-top:8px}
        .stat-trend.up{color:#10B981}
        .charts-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-bottom:30px}
        .chart-card{background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:20px}
        .chart-title{font-size:16px;font-weight:700;margin-bottom:15px}
        .chart-container{height:250px}
        .table-card{background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow-x:auto;margin-bottom:30px}
        .table-header{padding:15px 20px;border-bottom:1px solid #E5E7EB;display:flex;justify-content:space-between;align-items:center}
        .table-title{font-size:18px;font-weight:700}
        table{width:100%;border-collapse:collapse}
        th,td{padding:12px 15px;text-align:left;border-bottom:1px solid #E5E7EB}
        th{background:#F9FAFB;font-weight:600;font-size:12px;color:#6B7280}
        .status-badge{display:inline-block;padding:4px 8px;border-radius:20px;font-size:11px;font-weight:600}
        .status-pending{background:#FEF3C7;color:#D97706}
        .status-delivered{background:#D1FAE5;color:#059669}
        .btn-refresh{background:#FC8019;border:none;color:#fff;padding:10px 20px;border-radius:8px;cursor:pointer;font-weight:600;margin-bottom:20px}
        .btn-refresh:hover{background:#E56B0F}
        @media(max-width:768px){.charts-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
        <h1>📊 Admin Dashboard</h1>
        <button class="btn-refresh" onclick="location.reload()">🔄 Refresh Data</button>
    </div>
    
    <!-- Stats Row 1 -->
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-header"><span class="stat-label">Total Customers</span><span>👥</span></div><div class="stat-value"><?php echo number_format($totalCustomers); ?></div><div class="stat-trend up">+<?php echo $newUsersToday; ?> today</div></div>
        <div class="stat-card"><div class="stat-header"><span class="stat-label">Active Restaurants</span><span>🏪</span></div><div class="stat-value"><?php echo number_format($activeRestaurants); ?> / <?php echo $totalRestaurants; ?></div><div class="stat-trend"><?php echo $pendingRestaurants; ?> pending</div></div>
        <div class="stat-card"><div class="stat-header"><span class="stat-label">Delivery Partners</span><span>🚚</span></div><div class="stat-value"><?php echo number_format($totalDeliveryPartners); ?></div><div class="stat-trend"><?php echo $onlineDeliveryPartners; ?> online</div></div>
        <div class="stat-card"><div class="stat-header"><span class="stat-label">Menu Items</span><span>🍽️</span></div><div class="stat-value"><?php echo number_format($totalMenuItems); ?></div><div class="stat-trend"><?php echo $availableMenuItems; ?> available</div></div>
    </div>
    
    <!-- Stats Row 2 -->
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-header"><span class="stat-label">Total Orders</span><span>📦</span></div><div class="stat-value"><?php echo number_format($totalOrders); ?></div><div class="stat-trend"><?php echo $todayOrders; ?> today</div></div>
        <div class="stat-card"><div class="stat-header"><span class="stat-label">Pending Orders</span><span>⏳</span></div><div class="stat-value"><?php echo number_format($pendingOrders); ?></div><div class="stat-trend">Need attention</div></div>
        <div class="stat-card"><div class="stat-header"><span class="stat-label">Delivered Orders</span><span>✅</span></div><div class="stat-value"><?php echo number_format($deliveredOrders); ?></div><div class="stat-trend"><?php echo $totalOrders ? round(($deliveredOrders/$totalOrders)*100,1) : 0; ?>% complete</div></div>
        <div class="stat-card"><div class="stat-header"><span class="stat-label">Avg Rating</span><span>⭐</span></div><div class="stat-value"><?php echo number_format($avgRating, 1); ?></div><div class="stat-trend">Customer satisfaction</div></div>
    </div>
    
    <!-- Stats Row 3 -->
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-header"><span class="stat-label">Total Revenue</span><span>💰</span></div><div class="stat-value"><?php echo number_format($totalRevenue, 0); ?> Birr</div><div class="stat-trend up">+<?php echo number_format($todayRevenue, 0); ?> today</div></div>
        <div class="stat-card"><div class="stat-header"><span class="stat-label">This Month</span><span>📅</span></div><div class="stat-value"><?php echo number_format($monthRevenue, 0); ?> Birr</div><div class="stat-trend"><?php echo $monthOrders; ?> orders</div></div>
        <div class="stat-card"><div class="stat-header"><span class="stat-label">Avg Order Value</span><span>📊</span></div><div class="stat-value"><?php echo number_format($avgOrderValue, 2); ?> Birr</div><div class="stat-trend">Per order average</div></div>
        <div class="stat-card"><div class="stat-header"><span class="stat-label">Commission (15%)</span><span>💸</span></div><div class="stat-value"><?php echo number_format($totalCommission, 0); ?> Birr</div><div class="stat-trend"><?php echo number_format($monthCommission, 0); ?> this month</div></div>
    </div>
    
    <!-- Charts -->
    <?php if(!empty($monthlyLabels)): ?>
    <div class="charts-grid">
        <div class="chart-card"><div class="chart-title">📈 Monthly Revenue Trend</div><div class="chart-container"><canvas id="revenueChart"></canvas></div></div>
        <div class="chart-card"><div class="chart-title">📊 Monthly Orders</div><div class="chart-container"><canvas id="ordersChart"></canvas></div></div>
    </div>
    <?php endif; ?>
    
    <!-- Top Restaurants -->
    <?php if(!empty($topRestaurants)): ?>
    <div class="table-card">
        <div class="table-header"><div class="table-title">🏆 Top Restaurants</div><a href="/admin/restaurants.php" style="color:#FC8019;text-decoration:none">View All →</a></div>
        <table><thead><tr><th>#</th><th>Name</th><th>Cuisine</th><th>Orders</th><th>Revenue</th><th>Rating</th></tr></thead>
        <tbody><?php $i=1; foreach($topRestaurants as $r): ?>
        <tr><td><?php echo $i++; ?></td><td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td><td><?php echo htmlspecialchars($r['cuisine_type'] ?? '-'); ?></td><td><?php echo number_format($r['order_count']); ?></td><td><?php echo number_format($r['total_revenue'], 0); ?> Birr</td><td>⭐ <?php echo number_format($r['rating'], 1); ?></td></tr>
        <?php endforeach; ?></tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Recent Orders -->
    <?php if(!empty($recentOrders)): ?>
    <div class="table-card">
        <div class="table-header"><div class="table-title">📋 Recent Orders</div><a href="/admin/orders.php" style="color:#FC8019;text-decoration:none">View All →</a></div>
        <table><thead><tr><th>Order #</th><th>Customer</th><th>Restaurant</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody><?php foreach($recentOrders as $order): ?>
        <tr><td><strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong></td><td><?php echo htmlspecialchars($order['customer_name'] ?? '-'); ?></td><td><?php echo htmlspecialchars($order['restaurant_name'] ?? '-'); ?></td><td><?php echo number_format($order['total_amount'], 2); ?> Birr</td><td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td><td><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></td></tr>
        <?php endforeach; ?></tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
<?php if(!empty($monthlyLabels)): ?>
const monthlyLabels = <?php echo json_encode($monthlyLabels); ?>;
const monthlyRevenue = <?php echo json_encode($monthlyRevenue); ?>;
const monthlyOrders = <?php echo json_encode($monthlyOrders); ?>;

new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: { labels: monthlyLabels, datasets: [{ label: 'Revenue (Birr)', data: monthlyRevenue, borderColor: '#FC8019', backgroundColor: 'rgba(252,128,25,0.1)', fill: true, tension: 0.4 }] },
    options: { responsive: true, maintainAspectRatio: true }
});

new Chart(document.getElementById('ordersChart'), {
    type: 'bar',
    data: { labels: monthlyLabels, datasets: [{ label: 'Orders', data: monthlyOrders, backgroundColor: '#10B981', borderRadius: 8 }] },
    options: { responsive: true, maintainAspectRatio: true }
});
<?php endif; ?>
</script>
</body>
</html>
EOF