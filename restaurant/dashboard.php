<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['restaurant']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE user_id = ? LIMIT 1');
$stmt->execute([$_SESSION['user']['id']]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$restaurant) {
    redirect('/restaurant/setup.php');
}

// Calculate KPIs
$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ?');
$stmt->execute([$restaurant['id']]);
$totalOrders = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND DATE(created_at) = CURDATE()');
$stmt->execute([$restaurant['id']]);
$todayOrders = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE restaurant_id = ?');
$stmt->execute([$restaurant['id']]);
$totalRevenue = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE restaurant_id = ? AND DATE(created_at) = CURDATE()');
$stmt->execute([$restaurant['id']]);
$todayRevenue = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND status = "pending"');
$stmt->execute([$restaurant['id']]);
$pendingOrders = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM menu_items mi JOIN categories c ON mi.category_id = c.id WHERE c.restaurant_id = ?');
$stmt->execute([$restaurant['id']]);
$totalMenuItems = $stmt->fetchColumn();

// Recent orders
$stmt = $pdo->prepare('
    SELECT o.*, u.name AS customer_name, u.phone AS customer_phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.restaurant_id = ?
    ORDER BY o.created_at DESC
    LIMIT 10
');
$stmt->execute([$restaurant['id']]);
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top selling items
$stmt = $pdo->prepare('
    SELECT mi.name, SUM(oi.quantity) AS total_sold, SUM(oi.price * oi.quantity) AS revenue
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.restaurant_id = ?
    GROUP BY mi.id
    ORDER BY total_sold DESC
    LIMIT 5
');
$stmt->execute([$restaurant['id']]);
$topItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Dashboard · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/admin-layout.css">
    <link rel="stylesheet" href="/assets/css/admin-components.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">🏪</div>
                <div class="sidebar-title"><?= htmlspecialchars(substr($restaurant['name'], 0, 15)) ?></div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="/restaurant/dashboard.php" class="nav-item active">
                        <span class="nav-item-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="/restaurant/menu.php" class="nav-item">
                        <span class="nav-item-icon">📋</span>
                        <span>Menu</span>
                        <span class="nav-item-badge"><?= $totalMenuItems ?></span>
                    </a>
                    <a href="/restaurant/analytics.php" class="nav-item">
                        <span class="nav-item-icon">📈</span>
                        <span>Analytics</span>
                    </a>
                    <a href="/restaurant/profile.php" class="nav-item">
                        <span class="nav-item-icon">⚙️</span>
                        <span>Settings</span>
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
                    <input type="text" class="header-search-input" placeholder="Search orders..." id="globalSearch">
                </div>
                
                <div class="header-actions">
                    <button class="header-action-btn">
                        <span>🔔</span>
                        <?php if ($pendingOrders > 0): ?>
                        <span class="header-action-badge"><?= $pendingOrders ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <div class="header-profile">
                        <div class="header-avatar">🏪</div>
                        <div class="header-profile-info">
                            <div class="header-profile-name"><?= htmlspecialchars($restaurant['name']) ?></div>
                            <div class="header-profile-role">Restaurant Owner</div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <div class="admin-content">
                <div class="content-header">
                    <h1 class="content-title">Restaurant Dashboard</h1>
                    <p class="content-subtitle">Manage your restaurant and track performance</p>
                </div>
                
                <!-- Status Alert -->
                <?php if ($restaurant['status'] === 'pending'): ?>
                <div style="background: var(--yellow-50); border: 2px solid var(--yellow-500); border-radius: var(--radius-xl); padding: var(--space-6); margin-bottom: var(--space-6);">
                    <strong style="color: var(--yellow-700); font-size: var(--text-lg);">⏳ Pending Approval</strong>
                    <p style="color: var(--yellow-600); margin-top: var(--space-2);">Your restaurant is under review. You'll be notified once approved.</p>
                </div>
                <?php endif; ?>
                
                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Orders</span>
                            <div class="kpi-icon">📦</div>
                        </div>
                        <div class="kpi-value"><?= number_format($totalOrders) ?></div>
                        <div class="kpi-trend">
                            <span><?= $todayOrders ?> today</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Revenue</span>
                            <div class="kpi-icon">💰</div>
                        </div>
                        <div class="kpi-value"><?= number_format($totalRevenue, 0) ?> Birr</div>
                        <div class="kpi-trend">
                            <span><?= number_format($todayRevenue, 0) ?> Birr today</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Pending Orders</span>
                            <div class="kpi-icon">⏳</div>
                        </div>
                        <div class="kpi-value"><?= number_format($pendingOrders) ?></div>
                        <div class="kpi-trend <?= $pendingOrders > 0 ? 'negative' : 'positive' ?>">
                            <span><?= $pendingOrders > 0 ? 'Needs attention' : 'All clear!' ?></span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Menu Items</span>
                            <div class="kpi-icon">📋</div>
                        </div>
                        <div class="kpi-value"><?= number_format($totalMenuItems) ?></div>
                        <div class="kpi-trend">
                            <span>Active items</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Rating</span>
                            <div class="kpi-icon">⭐</div>
                        </div>
                        <div class="kpi-value"><?= number_format($restaurant['rating'], 1) ?></div>
                        <div class="kpi-trend positive">
                            <span>Customer rating</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Status</span>
                            <div class="kpi-icon">🏪</div>
                        </div>
                        <div class="kpi-value" style="font-size: var(--text-xl);"><?= ucfirst($restaurant['status']) ?></div>
                        <div class="kpi-trend">
                            <span><?= $restaurant['status'] === 'active' ? 'Open for orders' : 'Not accepting orders' ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2 class="chart-title">Revenue Trend</h2>
                        <div class="chart-actions">
                            <button class="chart-filter-btn active">7 Days</button>
                            <button class="chart-filter-btn">30 Days</button>
                        </div>
                    </div>
                    <canvas id="revenueChart" class="chart-canvas"></canvas>
                </div>
                
                <!-- Top Selling Items -->
                <?php if (!empty($topItems)): ?>
                <div style="background: white; border-radius: var(--radius-2xl); padding: var(--space-6); box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); margin-bottom: var(--space-6);">
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-bold); margin-bottom: var(--space-6);">Top Selling Items</h2>
                    <div style="display: grid; gap: var(--space-4);">
                        <?php foreach ($topItems as $item): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-4); background: var(--gray-50); border-radius: var(--radius-lg);">
                            <div>
                                <div style="font-weight: var(--font-semibold); margin-bottom: var(--space-1);"><?= htmlspecialchars($item['name']) ?></div>
                                <div style="font-size: var(--text-sm); color: var(--gray-600);"><?= $item['total_sold'] ?> sold</div>
                            </div>
                            <div style="font-weight: var(--font-bold); color: var(--orange-600);"><?= number_format($item['revenue'], 0) ?> Birr</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Recent Orders -->
                <div class="data-table-container">
                    <div class="table-header">
                        <h2 class="table-title">Recent Orders</h2>
                        <div class="table-filters">
                            <button class="filter-btn active">All</button>
                            <button class="filter-btn">Pending</button>
                            <button class="filter-btn">Preparing</button>
                        </div>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
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
                                    <div><?= htmlspecialchars($order['customer_name']) ?></div>
                                    <small style="color: var(--gray-500);"><?= htmlspecialchars($order['customer_phone']) ?></small>
                                </td>
                                <td><strong><?= number_format($order['total_amount'], 2) ?> Birr</strong></td>
                                <td><span class="status-badge <?= $order['status'] ?>"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></span></td>
                                <td><?= date('M d, g:i A', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <a href="/restaurant/order-detail.php?id=<?= $order['id'] ?>" class="action-btn action-btn-primary">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainContent').classList.toggle('expanded');
        });
        
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Revenue (Birr)',
                    data: [3200, 4100, 3800, 4500, 5200, 6100, 5800],
                    borderColor: '#FC8019',
                    backgroundColor: 'rgba(252, 128, 25, 0.1)',
                    tension: 0.4
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
