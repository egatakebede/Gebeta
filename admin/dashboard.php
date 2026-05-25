<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

// Calculate KPIs with trends
$totalOrders = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$yesterdayOrders = $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)')->fetchColumn();
$ordersTrend = $yesterdayOrders > 0 ? (($totalOrders - $yesterdayOrders) / $yesterdayOrders) * 100 : 0;

$stmt = $pdo->query('SELECT SUM(total_amount + delivery_fee) FROM orders');
$totalRevenue = $stmt->fetchColumn() ?: 0;
$stmt = $pdo->query('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)');
$yesterdayRevenue = $stmt->fetchColumn() ?: 0;
$revenueTrend = $yesterdayRevenue > 0 ? (($totalRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100 : 0;

$activeUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$totalUsers = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$usersTrend = $totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0;

$activeRestaurants = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE status = 'active'")->fetchColumn();
$pendingRestaurants = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE status = 'pending'")->fetchColumn();

$activeDeliveries = $pdo->query("SELECT COUNT(*) FROM order_deliveries WHERE status IN ('assigned', 'picked_up', 'in_transit')")->fetchColumn();

$stmt = $pdo->query('SELECT AVG(rating) FROM restaurants WHERE rating > 0');
$avgRating = $stmt->fetchColumn() ?: 0;

// Recent orders
$recentOrders = $pdo->query('
    SELECT o.id, o.order_number, o.status, o.total_amount, o.created_at, 
           r.name AS restaurant_name, u.name AS customer_name 
    FROM orders o 
    JOIN restaurants r ON o.restaurant_id = r.id 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/admin-layout.css">
    <link rel="stylesheet" href="/assets/css/admin-components.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="admin-layout admin-theme" id="adminThemeRoot">

        <!-- Sidebar -->
        <aside class="admin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">G</div>
                <div class="sidebar-title">Gebeta</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="/admin/dashboard.php" class="nav-item active">
                        <span class="nav-item-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="/admin/orders.php" class="nav-item">
                        <span class="nav-item-icon">📦</span>
                        <span>Orders</span>
                        <span class="nav-item-badge"><?= $totalOrders ?></span>
                    </a>
                    <a href="/admin/restaurants.php" class="nav-item">
                        <span class="nav-item-icon">🏪</span>
                        <span>Restaurants</span>
                        <?php if ($pendingRestaurants > 0): ?>
                        <span class="nav-item-badge"><?= $pendingRestaurants ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/admin/users.php" class="nav-item">
                        <span class="nav-item-icon">👥</span>
                        <span>Users</span>
                    </a>
                    <a href="/admin/delivery-partners.php" class="nav-item">
                        <span class="nav-item-icon">🚚</span>
                        <span>Delivery Partners</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Analytics</div>
                    <a href="/admin/analytics.php" class="nav-item">
                        <span class="nav-item-icon">📈</span>
                        <span>Analytics</span>
                    </a>
                    <a href="/admin/reports.php" class="nav-item">
                        <span class="nav-item-icon">📄</span>
                        <span>Reports</span>
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
                <button class="header-toggle" id="sidebarToggle">
                    <span>☰</span>
                </button>
                
                <div class="header-search">
                    <span class="header-search-icon">🔍</span>
                    <input type="text" class="header-search-input" placeholder="Search orders, restaurants, users..." id="globalSearch">
                </div>
                
                <div class="header-actions">
                    <button class="header-action-btn" id="notificationBtn">
                        <span>🔔</span>
                        <?php if ($pendingRestaurants > 0): ?>
                        <span class="header-action-badge"><?= $pendingRestaurants ?></span>
                        <?php endif; ?>
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
                    <h1 class="content-title">Dashboard Overview</h1>
                    <p class="content-subtitle">Welcome back! Here's what's happening with your platform today.</p>
                </div>
                
                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Orders</span>
                            <div class="kpi-icon">📦</div>
                        </div>
                        <div class="kpi-value"><?= number_format($totalOrders) ?></div>
                        <div class="kpi-trend <?= $ordersTrend >= 0 ? 'positive' : 'negative' ?>">
                            <span class="kpi-trend-icon"><?= $ordersTrend >= 0 ? '↑' : '↓' ?></span>
                            <span><?= abs(number_format($ordersTrend, 1)) ?>% vs yesterday</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Revenue</span>
                            <div class="kpi-icon">💰</div>
                        </div>
                        <div class="kpi-value"><?= number_format($totalRevenue, 0) ?> Birr</div>
                        <div class="kpi-trend <?= $revenueTrend >= 0 ? 'positive' : 'negative' ?>">
                            <span class="kpi-trend-icon"><?= $revenueTrend >= 0 ? '↑' : '↓' ?></span>
                            <span><?= abs(number_format($revenueTrend, 1)) ?>% vs yesterday</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Active Users</span>
                            <div class="kpi-icon">👥</div>
                        </div>
                        <div class="kpi-value"><?= number_format($activeUsers) ?></div>
                        <div class="kpi-trend positive">
                            <span class="kpi-trend-icon">↑</span>
                            <span><?= number_format($usersTrend, 1) ?>% active rate</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Active Restaurants</span>
                            <div class="kpi-icon">🏪</div>
                        </div>
                        <div class="kpi-value"><?= number_format($activeRestaurants) ?></div>
                        <div class="kpi-trend">
                            <span><?= $pendingRestaurants ?> pending approval</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Active Deliveries</span>
                            <div class="kpi-icon">🚚</div>
                        </div>
                        <div class="kpi-value"><?= number_format($activeDeliveries) ?></div>
                        <div class="kpi-trend">
                            <span>In progress now</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Average Rating</span>
                            <div class="kpi-icon">⭐</div>
                        </div>
                        <div class="kpi-value"><?= number_format($avgRating, 1) ?></div>
                        <div class="kpi-trend positive">
                            <span>Platform average</span>
                        </div>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2 class="chart-title">Revenue & Orders Trend</h2>
                        <div class="chart-actions">
                            <button class="chart-filter-btn active" data-period="7">7 Days</button>
                            <button class="chart-filter-btn" data-period="30">30 Days</button>
                            <button class="chart-filter-btn" data-period="90">90 Days</button>
                        </div>
                    </div>
                    <canvas id="revenueChart" class="chart-canvas"></canvas>
                </div>
                
                <!-- Recent Orders Table -->
                <div class="data-table-container">
                    <div class="table-header">
                        <h2 class="table-title">Recent Orders</h2>
                        <div class="table-search">
                            <span class="table-search-icon">🔍</span>
                            <input type="text" class="table-search-input" placeholder="Search orders..." id="orderSearch">
                        </div>
                        <div class="table-filters">
                            <button class="filter-btn active">All</button>
                            <button class="filter-btn">Pending</button>
                            <button class="filter-btn">Delivered</button>
                        </div>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="sortable">Order #</th>
                                <th class="sortable">Customer</th>
                                <th class="sortable">Restaurant</th>
                                <th class="sortable">Amount</th>
                                <th class="sortable">Status</th>
                                <th class="sortable">Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td><?= htmlspecialchars($order['restaurant_name']) ?></td>
                                <td><strong><?= number_format($order['total_amount'], 2) ?> Birr</strong></td>
                                <td><span class="status-badge <?= $order['status'] ?>"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></span></td>
                                <td><?= date('M d, Y g:i A', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <button class="action-btn action-btn-secondary" onclick="viewOrder(<?= $order['id'] ?>)">View</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="table-pagination">
                        <div class="pagination-info">Showing 1-10 of <?= $totalOrders ?> orders</div>
                        <div class="pagination-controls">
                            <button class="pagination-btn" disabled>←</button>
                            <button class="pagination-btn active">1</button>
                            <button class="pagination-btn">2</button>
                            <button class="pagination-btn">3</button>
                            <button class="pagination-btn">→</button>
                        </div>
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
        
        // Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Revenue (Birr)',
                    data: [4500, 5200, 4800, 6100, 7300, 8200, 9100],
                    borderColor: '#FC8019',
                    backgroundColor: 'rgba(252, 128, 25, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Orders',
                    data: [120, 145, 135, 160, 200, 240, 310],
                    borderColor: '#48C479',
                    backgroundColor: 'rgba(72, 196, 121, 0.1)',
                    tension: 0.4
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
        
        // View order function
        function viewOrder(id) {
            window.location.href = `/admin/orders.php?id=${id}`;
        }

        // Day/Night theme toggle
        const themeRoot = document.getElementById('adminThemeRoot');
        const darkToggle = document.getElementById('darkModeToggle');
        const THEME_KEY = 'gebeta_admin_theme'; // 'day' | 'night'

        function applyTheme(theme) {
            if (!themeRoot) return;
            const isNight = theme === 'night';
            themeRoot.classList.toggle('night', isNight);

            // keep icon: 🌙 for night, ☀️ for day
            if (darkToggle) {
                darkToggle.querySelector('span')?.textContent = isNight ? '🌙' : '☀️';
            }
        }

        function initTheme() {
            const saved = localStorage.getItem(THEME_KEY);
            if (saved === 'night' || saved === 'day') {
                applyTheme(saved);
                return;
            }
            // default: system preference
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            applyTheme(prefersDark ? 'night' : 'day');
        }

        darkToggle?.addEventListener('click', () => {
            const currentNight = themeRoot?.classList.contains('night');
            const next = currentNight ? 'day' : 'night';
            localStorage.setItem(THEME_KEY, next);
            applyTheme(next);
        });

        initTheme();
    </script>
</body>
</html>

