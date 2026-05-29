<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

// Get analytics data
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

// Get monthly data
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M', strtotime("-$i months"));
    $monthlyData[] = $month;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Analytics · Gebeta Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #FC8019;
            --primary-dark: #E56B0F;
            --primary-light: #FEF3C7;
            --success: #10B981;
            --success-light: #D1FAE5;
            --danger: #EF4444;
            --danger-light: #FEE2E2;
            --warning: #F59E0B;
            --warning-light: #FEF3C7;
            --info: #3B82F6;
            --info-light: #DBEAFE;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: var(--gray-50);
        }
        
        body.night {
            background: var(--gray-900);
        }
        
        body.night .sidebar,
        body.night .top-header,
        body.night .stat-card,
        body.night .chart-container,
        body.night .ranking-card,
        body.night .filter-bar {
            background: var(--gray-800);
            border-color: var(--gray-700);
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: white;
            border-right: 1px solid var(--gray-200);
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 100;
            transition: transform 0.3s ease;
        }
        
        .sidebar.collapsed {
            width: 80px;
        }
        
        .sidebar.collapsed .sidebar-title,
        .sidebar.collapsed .nav-item span:last-child,
        .sidebar.collapsed .nav-section-title {
            display: none;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .sidebar-logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
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
        
        .nav-section-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: var(--gray-400);
            margin: 1rem 0 0.5rem 0.75rem;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            color: var(--gray-600);
            text-decoration: none;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }
        
        body.night .nav-item {
            color: var(--gray-300);
        }
        
        .nav-item:hover {
            background: var(--gray-100);
        }
        
        body.night .nav-item:hover {
            background: var(--gray-700);
        }
        
        .nav-item.active {
            background: var(--primary-light);
            color: var(--primary);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            transition: margin-left 0.3s ease;
            width: 100%;
        }
        
        .main-content.expanded {
            margin-left: 80px;
        }
        
        /* Header */
        .top-header {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 99;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--gray-100);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            flex: 1;
            max-width: 400px;
        }
        
        .search-box input {
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
        
        .theme-toggle {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .user-badge {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .user-name {
            font-weight: 500;
        }
        
        @media (max-width: 640px) {
            .user-name {
                display: none;
            }
        }
        
        /* Content */
        .content-area {
            padding: 2rem;
        }
        
        .page-title {
            font-size: 1.875rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        body.night .page-title {
            color: white;
        }
        
        .page-subtitle {
            color: var(--gray-500);
            margin-bottom: 2rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--gray-200);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-500);
            margin-top: 0.25rem;
        }
        
        /* Chart */
        .chart-container {
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--gray-200);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-header {
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.125rem;
            font-weight: bold;
        }
        
        .chart-canvas {
            max-height: 300px;
            width: 100%;
        }
        
        /* Two Column Layout */
        .two-columns {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        /* Ranking Card */
        .ranking-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--gray-200);
            padding: 1.5rem;
        }
        
        .ranking-header {
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .ranking-title {
            font-size: 1.125rem;
            font-weight: bold;
        }
        
        .ranking-list {
            list-style: none;
        }
        
        .ranking-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-100);
        }
        
        body.night .ranking-item {
            border-bottom-color: var(--gray-700);
        }
        
        .ranking-position {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.125rem;
        }
        
        .ranking-position.gold { color: #F59E0B; }
        .ranking-position.silver { color: #94A3B8; }
        .ranking-position.bronze { color: #CD7F32; }
        
        .ranking-info {
            flex: 1;
            margin-left: 1rem;
        }
        
        .ranking-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .ranking-stats {
            font-size: 0.75rem;
            color: var(--gray-500);
        }
        
        .ranking-value {
            font-weight: bold;
            color: var(--primary);
        }
        
        /* Bottom Navigation */
        .bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid var(--gray-200);
            padding: 0.75rem 1rem;
            z-index: 100;
        }
        
        body.night .bottom-nav {
            background: var(--gray-800);
            border-top-color: var(--gray-700);
        }
        
        .bottom-nav-items {
            display: flex;
            justify-content: space-around;
        }
        
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            text-decoration: none;
            color: var(--gray-500);
            font-size: 0.7rem;
        }
        
        .bottom-nav-item.active {
            color: var(--primary);
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
        
        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding-bottom: 70px;
            }
            .menu-toggle {
                display: block;
            }
            .sidebar-toggle {
                display: none;
            }
            .bottom-nav {
                display: block;
            }
            .top-header {
                padding: 0.875rem 1rem;
            }
            .content-area {
                padding: 1rem;
            }
            .page-title {
                font-size: 1.5rem;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.875rem;
            }
            .stat-card {
                padding: 1rem;
            }
            .stat-value {
                font-size: 1.5rem;
            }
            .two-columns {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .chart-container, .ranking-card {
                padding: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .header-actions {
                gap: 0.5rem;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body id="adminThemeRoot">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="admin-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">G</div>
                <div class="sidebar-title">Gebeta</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section-title">MAIN</div>
                <a href="/admin/dashboard.php" class="nav-item"><span>📊</span><span>Dashboard</span></a>
                <a href="/admin/analytics.php" class="nav-item active"><span>📈</span><span>Analytics</span></a>
                <div class="nav-section-title">MANAGEMENT</div>
                <a href="/admin/restaurants.php" class="nav-item"><span>🏪</span><span>Restaurants</span></a>
                <a href="/admin/users.php" class="nav-item"><span>👥</span><span>Users</span></a>
                <a href="/admin/orders.php" class="nav-item"><span>📦</span><span>Orders</span></a>
                <div class="nav-section-title">ACCOUNT</div>
                <a href="/logout.php" class="nav-item"><span>🚪</span><span>Logout</span></a>
            </nav>
        </aside>
        
        <main class="main-content" id="mainContent">
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                </div>
                <div class="search-box"><span>🔍</span><input type="text" placeholder="Search..." id="globalSearch"></div>
                <div class="header-actions">
                    <button class="theme-toggle" id="themeToggle">🌙</button>
                    <div class="user-badge"><div class="user-avatar">A</div><span class="user-name">Admin</span></div>
                </div>
            </header>
            
            <div class="content-area">
                <h1 class="page-title">📈 Analytics</h1>
                <p class="page-subtitle">Platform insights and performance metrics</p>
                
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-value"><?= number_format($totalRevenue, 0) ?> Birr</div><div class="stat-label">Total Revenue</div></div>
                    <div class="stat-card"><div class="stat-value"><?= number_format($avgOrderValue, 0) ?> Birr</div><div class="stat-label">Avg Order Value</div></div>
                    <div class="stat-card"><div class="stat-value"><?= number_format($completionRate, 1) ?>%</div><div class="stat-label">Completion Rate</div></div>
                    <div class="stat-card"><div class="stat-value"><?= number_format($totalOrders) ?></div><div class="stat-label">Total Orders</div></div>
                </div>
                
                <div class="chart-container">
                    <div class="chart-header"><h2 class="chart-title">Revenue & Orders Trend (Last 30 Days)</h2></div>
                    <canvas id="revenueChart" class="chart-canvas"></canvas>
                </div>
                
                <div class="two-columns">
                    <div class="ranking-card">
                        <div class="ranking-header"><div class="ranking-title">🏆 Top Performing Restaurants</div></div>
                        <ul class="ranking-list">
                            <?php foreach ($topRestaurants as $index => $restaurant): ?>
                            <li class="ranking-item">
                                <div class="ranking-position <?= $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : '')) ?>">#<?= $index + 1 ?></div>
                                <div class="ranking-info"><div class="ranking-name"><?= htmlspecialchars($restaurant['name']) ?></div><div class="ranking-stats"><?= number_format($restaurant['order_count']) ?> orders</div></div>
                                <div class="ranking-value"><?= number_format($restaurant['revenue'], 0) ?> Birr</div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="ranking-card">
                        <div class="ranking-header"><div class="ranking-title">📊 Quick Stats</div></div>
                        <ul class="ranking-list">
                            <li class="ranking-item"><div class="ranking-position">📦</div><div class="ranking-info"><div class="ranking-name">Total Orders</div></div><div class="ranking-value"><?= number_format($totalOrders) ?></div></li>
                            <li class="ranking-item"><div class="ranking-position">💰</div><div class="ranking-info"><div class="ranking-name">Total Revenue</div></div><div class="ranking-value"><?= number_format($totalRevenue, 0) ?> Birr</div></li>
                            <li class="ranking-item"><div class="ranking-position">⭐</div><div class="ranking-info"><div class="ranking-name">Avg Order Value</div></div><div class="ranking-value"><?= number_format($avgOrderValue, 0) ?> Birr</div></li>
                            <li class="ranking-item"><div class="ranking-position">✅</div><div class="ranking-info"><div class="ranking-name">Completion Rate</div></div><div class="ranking-value"><?= number_format($completionRate, 1) ?>%</div></li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <nav class="bottom-nav">
        <div class="bottom-nav-items">
            <a href="/admin/dashboard.php" class="bottom-nav-item"><span>📊</span><span>Home</span></a>
            <a href="/admin/restaurants.php" class="bottom-nav-item"><span>🏪</span><span>Restaurants</span></a>
            <a href="/admin/users.php" class="bottom-nav-item"><span>👥</span><span>Users</span></a>
            <a href="/admin/orders.php" class="bottom-nav-item"><span>📦</span><span>Orders</span></a>
            <a href="/admin/analytics.php" class="bottom-nav-item active"><span>📈</span><span>Analytics</span></a>
        </div>
    </nav>
    
    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const menuToggle = document.getElementById('menuToggle');
        const overlay = document.getElementById('sidebarOverlay');
        
        sidebarToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            document.getElementById('mainContent')?.classList.toggle('expanded');
        });
        
        function closeMenu() { sidebar.classList.remove('mobile-open'); overlay.classList.remove('active'); document.body.style.overflow = ''; }
        function openMenu() { sidebar.classList.add('mobile-open'); overlay.classList.add('active'); document.body.style.overflow = 'hidden'; }
        menuToggle?.addEventListener('click', openMenu);
        overlay?.addEventListener('click', closeMenu);
        window.addEventListener('resize', () => { if (window.innerWidth > 768) closeMenu(); });
        
        // Chart
        const orderData = <?= json_encode($ordersByDay) ?>;
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
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
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
        });
        
        // Dark mode
        const themeRoot = document.getElementById('adminThemeRoot');
        const themeToggle = document.getElementById('themeToggle');
        const themeKey = 'gebeta_admin_theme';
        function applyTheme(theme) { const isNight = theme === 'night'; themeRoot.classList.toggle('night', isNight); themeToggle.textContent = isNight ? '☀️' : '🌙'; }
        const saved = localStorage.getItem(themeKey);
        if (saved) applyTheme(saved);
        else if (window.matchMedia('(prefers-color-scheme: dark)').matches) applyTheme('night');
        themeToggle?.addEventListener('click', () => { const isNight = themeRoot.classList.contains('night'); const next = isNight ? 'day' : 'night'; localStorage.setItem(themeKey, next); applyTheme(next); });
    </script>
</body>
</html>