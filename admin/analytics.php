<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

// Get analytics data - FIXED: Use prepared statements
$stmt = $pdo->prepare("SELECT SUM(total_amount + delivery_fee) FROM orders WHERE status = ?");
$stmt->execute(['delivered']);
$totalRevenue = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query('SELECT COUNT(*) FROM orders');
$totalOrders = $stmt->fetchColumn();

$avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

$stmt = $pdo->prepare("SELECT (COUNT(CASE WHEN status = ? THEN 1 END) * 100.0 / COUNT(*)) FROM orders");
$stmt->execute(['delivered']);
$completionRate = $stmt->fetchColumn() ?: 0;

// Orders by day (last 30 days)
$stmt = $pdo->prepare('
    SELECT DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as revenue
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
');
$stmt->execute();
$ordersByDay = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top restaurants
$stmt = $pdo->query('
    SELECT r.name, COUNT(o.id) as order_count, COALESCE(SUM(o.total_amount), 0) as revenue
    FROM restaurants r
    LEFT JOIN orders o ON r.id = o.restaurant_id
    GROUP BY r.id
    ORDER BY order_count DESC
    LIMIT 5
');
$topRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics · Gebeta Admin</title>
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
        body.night .chart-container,
        body.night .data-table-container {
            background: #1F2937;
            border-color: #374151;
        }
        
        body.night .data-table th {
            background: #111827;
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
            transition: all 0.3s;
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
        
        .header-action-btn {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
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
        
        .header-profile-info {
            display: none;
        }
        
        @media (min-width: 768px) {
            .header-profile-info {
                display: block;
            }
        }
        
        .header-profile-name {
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .header-profile-role {
            font-size: 0.75rem;
            color: #6B7280;
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
        
        .content-subtitle {
            color: #6B7280;
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
        
        .kpi-icon {
            font-size: 1.5rem;
        }
        
        .kpi-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .kpi-trend {
            font-size: 0.75rem;
            color: #6B7280;
        }
        
        .kpi-trend.positive {
            color: #059669;
        }
        
        .chart-container {
            background: white;
            border-radius: 1rem;
            border: 1px solid #E5E7EB;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .chart-title {
            font-size: 1.125rem;
            font-weight: bold;
        }
        
        .chart-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .chart-filter-btn {
            padding: 0.5rem 1rem;
            background: #F3F4F6;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
        }
        
        .chart-filter-btn.active {
            background: #FC8019;
            color: white;
        }
        
        .chart-canvas {
            max-height: 300px;
        }
        
        .data-table-container {
            background: white;
            border-radius: 1rem;
            border: 1px solid #E5E7EB;
            overflow-x: auto;
        }
        
        .table-header {
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
        
        .dark-mode-toggle {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-260px);
            }
            
            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .admin-content {
                padding: 1rem;
            }
            
            .kpi-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
            .header-toggle {
                display: none;
            }
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body id="adminThemeRoot">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
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
                <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
                <button class="header-toggle" id="sidebarToggle">☰</button>
                
                <div class="header-search">
                    <span>🔍</span>
                    <input type="text" class="header-search-input" placeholder="Search...">
                </div>
                
                <div class="header-actions">
                    <button class="header-action-btn" id="notificationBtn">
                        <span>🔔</span>
                    </button>
                    <button class="header-action-btn" id="darkModeToggle">
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
                            <button class="chart-filter-btn active" data-days="30">30 Days</button>
                            <button class="chart-filter-btn" data-days="90">90 Days</button>
                            <button class="chart-filter-btn" data-days="365">1 Year</button>
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
        // Mobile menu
        const sidebar = document.getElementById('sidebar');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mainContent = document.getElementById('mainContent');
        
        function closeMobileMenu() {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('active');
        }
        
        function openMobileMenu() {
            sidebar.classList.add('mobile-open');
            sidebarOverlay.classList.add('active');
        }
        
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', openMobileMenu);
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeMobileMenu);
        }
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            });
        }
        
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });
        
        // Revenue Chart
        const orderData = <?= json_encode($ordersByDay) ?>;
        
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        let revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: orderData.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
                }),
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
        
        // Top Restaurants Chart
        const topRestaurants = <?= json_encode($topRestaurants) ?>;
        
        const restaurantsCtx = document.getElementById('restaurantsChart').getContext('2d');
        new Chart(restaurantsCtx, {
            type: 'bar',
            data: {
                labels: topRestaurants.map(r => r.name.length > 15 ? r.name.substring(0, 12) + '...' : r.name),
                datasets: [{
                    label: 'Orders',
                    data: topRestaurants.map(r => r.order_count),
                    backgroundColor: '#FC8019',
                    borderRadius: 8
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
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Orders'
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
        
        // Chart filters
        document.querySelectorAll('.chart-filter-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const days = this.dataset.days;
                
                document.querySelectorAll('.chart-filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const response = await fetch(`/admin/api/chart-data.php?days=${days}`);
                const data = await response.json();
                
                revenueChart.data.labels = data.labels;
                revenueChart.data.datasets[0].data = data.revenue;
                revenueChart.data.datasets[1].data = data.orders;
                revenueChart.update();
            });
        });
        
        // Dark mode
        const themeRoot = document.getElementById('adminThemeRoot');
        const darkToggle = document.getElementById('darkModeToggle');
        const THEME_KEY = 'gebeta_admin_theme';
        
        function applyTheme(theme) {
            const isNight = theme === 'night';
            themeRoot.classList.toggle('night', isNight);
            if (darkToggle) {
                darkToggle.querySelector('span').textContent = isNight ? '☀️' : '🌙';
            }
        }
        
        function initTheme() {
            const saved = localStorage.getItem(THEME_KEY);
            if (saved === 'night' || saved === 'day') {
                applyTheme(saved);
                return;
            }
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            applyTheme(prefersDark ? 'night' : 'day');
        }
        
        if (darkToggle) {
            darkToggle.addEventListener('click', () => {
                const currentNight = themeRoot.classList.contains('night');
                const next = currentNight ? 'day' : 'night';
                localStorage.setItem(THEME_KEY, next);
                applyTheme(next);
            });
        }
        
        initTheme();
    </script>
</body>
</html>