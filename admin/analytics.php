<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

// Get analytics data
$totalRevenue = $pdo->query('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE status = "delivered"')->fetchColumn() ?: 0;
$totalOrders = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
$completionRate = $pdo->query('SELECT (COUNT(CASE WHEN status = "delivered" THEN 1 END) * 100.0 / COUNT(*)) FROM orders')->fetchColumn() ?: 0;

// Orders by day (last 30 days)
$ordersByDay = $pdo->query('
    SELECT DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as revenue
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
')->fetchAll(PDO::FETCH_ASSOC);

// Top restaurants
$topRestaurants = $pdo->query('
    SELECT r.name, COUNT(o.id) as order_count, SUM(o.total_amount) as revenue
    FROM restaurants r
    LEFT JOIN orders o ON r.id = o.restaurant_id
    GROUP BY r.id
    ORDER BY order_count DESC
    LIMIT 5
')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics · Gebeta Admin</title>
    <link rel="stylesheet" href="/assets/css/admin-layout.css">
    <link rel="stylesheet" href="/assets/css/admin-components.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
                    <div class="nav-section-title">Main</div>
                    <a href="/admin/dashboard.php" class="nav-item">
                        <span class="nav-item-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Management</div>
                    <a href="/admin/restaurants.php" class="nav-item">
                        <span class="nav-item-icon">🏪</span>
                        <span>Restaurants</span>
                    </a>
                    <a href="/admin/users.php" class="nav-item">
                        <span class="nav-item-icon">👥</span>
                        <span>Users</span>
                    </a>
                    <a href="/admin/delivery-partners.php" class="nav-item">
                        <span class="nav-item-icon">🚚</span>
                        <span>Delivery Partners</span>
                    </a>
                    <a href="/admin/orders.php" class="nav-item">
                        <span class="nav-item-icon">📄</span>
                        <span>Orders</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Analytics</div>
                    <a href="/admin/analytics.php" class="nav-item active">
                        <span class="nav-item-icon">📈</span>
                        <span>Analytics</span>
                    </a>
                    <a href="/admin/reports.php" class="nav-item">
                        <span class="nav-item-icon">📋</span>
                        <span>Reports</span>
                    </a>
                    <a href="/admin/payments.php" class="nav-item">
                        <span class="nav-item-icon">💰</span>
                        <span>Payments</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Settings</div>
                    <a href="/admin/settings.php" class="nav-item">
                        <span class="nav-item-icon">⚙️</span>
                        <span>Settings</span>
                    </a>
                    <a href="/logout.php" class="nav-item" style="color: var(--red-600);">
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
                    <input type="text" class="header-search-input" placeholder="Search...">
                </div>
                
                <div class="header-actions">
                    <button class="header-action-btn">
                        <span>🔔</span>
                    </button>
                    <button class="header-action-btn">
                        <span>🌙</span>
                    </button>
                    <div class="header-profile">
                        <div class="header-avatar">A</div>
                        <div class="header-profile-info">
                            <div class="header-profile-name">Admin</div>
                            <div class="header-profile-role">Administrator</div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <div class="admin-content">
                <div class="content-header">
                    <h1 class="content-title">📈 Analytics</h1>
                    <p class="content-subtitle">Platform insights and performance metrics</p>
                </div>
                
                <!-- Key Metrics -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Revenue</span>
                            <div class="kpi-icon">💰</div>
                        </div>
                        <div class="kpi-value"><?= number_format($totalRevenue, 0) ?> Birr</div>
                        <div class="kpi-trend positive">All time earnings</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Avg Order Value</span>
                            <div class="kpi-icon">📊</div>
                        </div>
                        <div class="kpi-value"><?= number_format($avgOrderValue, 0) ?> Birr</div>
                        <div class="kpi-trend">Per order average</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Completion Rate</span>
                            <div class="kpi-icon">✅</div>
                        </div>
                        <div class="kpi-value"><?= number_format($completionRate, 1) ?>%</div>
                        <div class="kpi-trend positive">Successfully delivered</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Orders</span>
                            <div class="kpi-icon">📦</div>
                        </div>
                        <div class="kpi-value"><?= number_format($totalOrders) ?></div>
                        <div class="kpi-trend">All time orders</div>
                    </div>
                </div>
                
                <!-- Revenue Trend Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2 class="chart-title">Revenue & Orders Trend (Last 30 Days)</h2>
                        <div class="chart-actions">
                            <button class="chart-filter-btn active">30 Days</button>
                            <button class="chart-filter-btn">90 Days</button>
                            <button class="chart-filter-btn">1 Year</button>
                        </div>
                    </div>
                    <canvas id="revenueChart" class="chart-canvas"></canvas>
                </div>
                
                <!-- Top Restaurants Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2 class="chart-title">Top Restaurants by Orders</h2>
                    </div>
                    <canvas id="restaurantsChart" class="chart-canvas"></canvas>
                </div>
                
                <!-- Top Restaurants Table -->
                <div class="data-table-container">
                    <div class="table-header">
                        <h2 class="table-title">Top Performing Restaurants</h2>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Restaurant</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topRestaurants as $index => $restaurant): ?>
                            <tr>
                                <td><strong>#<?= $index + 1 ?></strong></td>
                                <td><?= htmlspecialchars($restaurant['name']) ?></td>
                                <td><strong><?= number_format($restaurant['order_count']) ?></strong></td>
                                <td><strong><?= number_format($restaurant['revenue'], 2) ?> Birr</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
        
        // Revenue Chart (guard against missing canvas)
        const revenueEl = document.getElementById('revenueChart');
        if (!revenueEl) {
            console.error('Analytics: #revenueChart canvas not found');
        } else {
            const revenueCtx = revenueEl.getContext('2d');
            const orderData = <?= json_encode($ordersByDay) ?>;
            
            new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: orderData.map(d => new Date(d.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'})),
                datasets: [{
                    label: 'Revenue (Birr)',
                    data: orderData.map(d => d.revenue),
                    borderColor: '#FC8019',
                    backgroundColor: 'rgba(252, 128, 25, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Orders',
                    data: orderData.map(d => d.count),
                    borderColor: '#48C479',
                    backgroundColor: 'rgba(72, 196, 121, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Top Restaurants Chart (guard against missing canvas)
        const restaurantsEl = document.getElementById('restaurantsChart');
        if (!restaurantsEl) {
            console.error('Analytics: #restaurantsChart canvas not found');
        } else {
            const restaurantsCtx = restaurantsEl.getContext('2d');
            const topRestaurants = <?= json_encode($topRestaurants) ?>;
            
            new Chart(restaurantsCtx, {
            type: 'bar',
            data: {
                labels: topRestaurants.map(r => r.name),
                datasets: [{
                    label: 'Orders',
                    data: topRestaurants.map(r => r.order_count),
                    backgroundColor: '#FC8019',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
