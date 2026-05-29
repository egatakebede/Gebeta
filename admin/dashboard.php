<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

// Get admin info from session
$adminId = $_SESSION['user']['id'];
$adminName = $_SESSION['user']['name'];

// Get platform stats
$stmt = $pdo->query('SELECT COUNT(*) FROM users');
$totalUsers = $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM orders');
$totalOrders = $stmt->fetchColumn();

// ✅ FIXED: Use prepare() with placeholders
$stmt = $pdo->prepare('SELECT COUNT(*) FROM restaurants WHERE status = ?');
$stmt->execute(['active']);
$activeRestaurants = $stmt->fetchColumn();

// ✅ FIXED: Use prepare() with placeholders
$stmt = $pdo->prepare('SELECT COUNT(*) FROM delivery_partners WHERE status = ?');
$stmt->execute(['online']);
$activeDeliveryPartners = $stmt->fetchColumn();

// Get total revenue
$stmt = $pdo->prepare('SELECT SUM(total_amount) FROM orders WHERE payment_status = ?');
$stmt->execute(['completed']);
$totalRevenue = $stmt->fetchColumn() ?: 0;

// Get completed orders
$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE payment_status = ?');
$stmt->execute(['completed']);
$completedOrders = $stmt->fetchColumn();

// Recent orders
$stmt = $pdo->prepare('
    SELECT o.id, o.order_number, o.status, o.total_amount, o.created_at,
           r.name AS restaurant_name, u.name AS customer_name
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
');
$stmt->execute();
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top restaurants
$stmt = $pdo->prepare('
    SELECT r.id, r.name, r.cuisine_type, r.location, r.rating,
           COUNT(o.id) AS order_count, SUM(o.total_amount) AS total_revenue
    FROM restaurants r
    LEFT JOIN orders o ON r.id = o.restaurant_id
    WHERE r.status = ?
    GROUP BY r.id
    ORDER BY order_count DESC
    LIMIT 6
');
$stmt->execute(['active']);
$topRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard · Gebeta</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #F9FAFB;
        }
        
        /* Layout */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 260px;
            background: white;
            border-right: 1px solid #E5E7EB;
            transition: all 0.3s;
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
        
        /* Header */
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
            transition: background 0.2s;
        }
        
        .header-toggle:hover {
            background: #F3F4F6;
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
            font-size: 0.875rem;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
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
            font-size: 1.125rem;
        }
        
        /* Sidebar */
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
            color: #1F2937;
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
            letter-spacing: 0.5px;
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
            font-weight: 600;
        }
        
        /* Content */
        .admin-content {
            padding: 2rem;
        }
        
        .content-header {
            margin-bottom: 2rem;
        }
        
        .content-title {
            font-size: 1.875rem;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .content-subtitle {
            color: #6B7280;
            font-size: 0.875rem;
        }
        
        /* KPI Cards */
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
            transition: all 0.2s;
        }
        
        .kpi-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .kpi-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6B7280;
        }
        
        .kpi-icon {
            font-size: 1.5rem;
        }
        
        .kpi-value {
            font-size: 2rem;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .kpi-trend {
            font-size: 0.75rem;
            color: #6B7280;
        }
        
        .kpi-trend.positive {
            color: #10B981;
        }
        
        /* Tables */
        .data-table-container {
            background: white;
            border-radius: 1rem;
            border: 1px solid #E5E7EB;
            overflow-x: auto;
            margin-bottom: 2rem;
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
            color: #1F2937;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 0.75rem 1.5rem;
            background: #F9FAFB;
            font-weight: 600;
            font-size: 0.75rem;
            color: #4B5563;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table td {
            padding: 1rem 1.5rem;
            border-top: 1px solid #E5E7EB;
            font-size: 0.875rem;
        }
        
        .data-table tbody tr:hover {
            background: #F9FAFB;
        }
        
        /* Status Badges */
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
        .status-badge.active { background: #D1FAE5; color: #059669; }
        .status-badge.online { background: #D1FAE5; color: #059669; }
        
        /* Buttons */
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        
        .action-btn-primary {
            background: #FC8019;
            color: white;
        }
        
        .action-btn-primary:hover {
            background: #E56B0F;
        }
        
        .action-btn-secondary {
            background: #F3F4F6;
            color: #4B5563;
            border: 1px solid #E5E7EB;
        }
        
        .action-btn-secondary:hover {
            background: #E5E7EB;
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
            font-size: 0.75rem;
            transition: all 0.2s;
        }
        
        .btn-refresh:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(252, 128, 25, 0.3);
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
            color: #1F2937;
        }
        
        .dark-mode-toggle {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: background 0.2s;
        }
        
        .dark-mode-toggle:hover {
            background: #F3F4F6;
        }
        
        /* Dark Mode */
        .admin-theme.night {
            background: #111827;
        }
        
        .admin-theme.night .admin-sidebar,
        .admin-theme.night .admin-header,
        .admin-theme.night .kpi-card,
        .admin-theme.night .data-table-container {
            background: #1F2937;
            border-color: #374151;
        }
        
        .admin-theme.night .content-title,
        .admin-theme.night .kpi-value,
        .admin-theme.night .table-title,
        .admin-theme.night .text-2xl,
        .admin-theme.night .sidebar-title {
            color: #F9FAFB;
        }
        
        .admin-theme.night .content-subtitle,
        .admin-theme.night .kpi-label,
        .admin-theme.night .kpi-trend,
        .admin-theme.night .nav-item:not(.active),
        .admin-theme.night .data-table td {
            color: #D1D5DB;
        }
        
        .admin-theme.night .data-table th {
            background: #111827;
            color: #9CA3AF;
        }
        
        .admin-theme.night .data-table tbody tr:hover {
            background: #374151;
        }
        
        .admin-theme.night .nav-item:hover:not(.active) {
            background: #374151;
        }
        
        .admin-theme.night .header-search,
        .admin-theme.night .action-btn-secondary {
            background: #374151;
            border-color: #4B5563;
            color: #D1D5DB;
        }
        
        .admin-theme.night .header-search-input {
            background: #374151;
            color: #F9FAFB;
        }
        
        .admin-theme.night .dark-mode-toggle:hover {
            background: #374151;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .admin-main.expanded {
                margin-left: 0;
            }
            
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .header-search {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body class="admin-theme" id="adminThemeRoot">
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">G</div>
                <div class="sidebar-title">Gebeta</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Dashboard</div>
                    <a href="/admin/dashboard.php" class="nav-item active">
                        <span style="font-size: 1.25rem;">📊</span>
                        <span>Dashboard</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Management</div>
                    <a href="/admin/restaurants.php" class="nav-item">
                        <span style="font-size: 1.25rem;">🏪</span>
                        <span>Restaurants</span>
                    </a>
                    <a href="/admin/users.php" class="nav-item">
                        <span style="font-size: 1.25rem;">👥</span>
                        <span>Users</span>
                    </a>
                    <a href="/admin/delivery-partners.php" class="nav-item">
                        <span style="font-size: 1.25rem;">🚗</span>
                        <span>Delivery Partners</span>
                    </a>
                    <a href="/admin/orders.php" class="nav-item">
                        <span style="font-size: 1.25rem;">📦</span>
                        <span>Orders</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Account</div>
                    <a href="/logout.php" class="nav-item">
                        <span style="font-size: 1.25rem;">🚪</span>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main" id="mainContent">
            <!-- Header -->
            <header class="admin-header">
                <button class="header-toggle" id="sidebarToggle">☰</button>
                
                <div class="header-search">
                    <span>🔍</span>
                    <input type="text" class="header-search-input" placeholder="Search orders, users...">
                </div>
                
                <div class="header-actions">
                    <button class="dark-mode-toggle" id="darkModeToggle" title="Toggle dark mode">🌙</button>
                    <button class="btn-refresh" onclick="location.reload()">🔄 Refresh</button>
                    
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div class="header-avatar"><?php echo strtoupper(substr($adminName, 0, 1)); ?></div>
                        <div>
                            <div style="font-weight: 600; font-size: 0.875rem;"><?php echo htmlspecialchars($adminName); ?></div>
                            <div style="font-size: 0.75rem; color: #6B7280;">Administrator</div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <div class="admin-content">
                <div class="content-header">
                    <h1 class="content-title">Platform Dashboard 📊</h1>
                    <p class="content-subtitle">Overview of all platform activities and performance</p>
                </div>
                
                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Users</span>
                            <span class="kpi-icon">👥</span>
                        </div>
                        <div class="kpi-value"><?php echo number_format($totalUsers); ?></div>
                        <div class="kpi-trend">Active users on platform</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Orders</span>
                            <span class="kpi-icon">📦</span>
                        </div>
                        <div class="kpi-value"><?php echo number_format($totalOrders); ?></div>
                        <div class="kpi-trend"><?php echo $completedOrders; ?> completed</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Active Restaurants</span>
                            <span class="kpi-icon">🏪</span>
                        </div>
                        <div class="kpi-value"><?php echo number_format($activeRestaurants); ?></div>
                        <div class="kpi-trend">Verified restaurants</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Online Drivers</span>
                            <span class="kpi-icon">🚗</span>
                        </div>
                        <div class="kpi-value"><?php echo number_format($activeDeliveryPartners); ?></div>
                        <div class="kpi-trend">Available for delivery</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Revenue</span>
                            <span class="kpi-icon">💰</span>
                        </div>
                        <div class="kpi-value"><?php echo number_format($totalRevenue, 0); ?> Birr</div>
                        <div class="kpi-trend positive">Platform commission</div>
                    </div>
                </div>
                
                <!-- Recent Orders Table -->
                <?php if (!empty($recentOrders)): ?>
                <div class="data-table-container">
                    <div class="table-header">
                        <h2 class="table-title">Recent Orders</h2>
                        <a href="/admin/orders.php" class="action-btn action-btn-secondary">View All Orders</a>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
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
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['restaurant_name']); ?></td>
                                <td><strong><?php echo number_format($order['total_amount'], 2); ?> Birr</strong></td>
                                <td><span class="status-badge <?php echo str_replace('_', ' ', $order['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                <td><a href="/admin/orders.php?id=<?php echo $order['id']; ?>" class="action-btn action-btn-primary">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- Top Restaurants Table -->
                <?php if (!empty($topRestaurants)): ?>
                <div class="mt-8">
                    <h2 class="text-2xl mb-6">Top Performing Restaurants</h2>
                    <div class="data-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Restaurant Name</th>
                                    <th>Cuisine Type</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                    <th>Rating</th>
                                    <th>Location</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topRestaurants as $restaurant): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($restaurant['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($restaurant['cuisine_type']); ?></td>
                                    <td><span class="status-badge active"><?php echo $restaurant['order_count']; ?> orders</span></td>
                                    <td><strong><?php echo number_format($restaurant['total_revenue'] ?: 0, 0); ?> Birr</strong></td>
                                    <td>⭐ <?php echo number_format($restaurant['rating'], 1); ?></td>
                                    <td><?php echo htmlspecialchars($restaurant['location']); ?></td>
                                    <td><a href="/admin/restaurants.php?id=<?php echo $restaurant['id']; ?>" class="action-btn action-btn-primary">Manage</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            var sidebar = document.getElementById('sidebar');
            var main = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('expanded');
        });
        
        // Dark Mode Toggle
        var themeRoot = document.getElementById('adminThemeRoot');
        var darkToggle = document.getElementById('darkModeToggle');
        var themeKey = 'gebeta_admin_theme';
        
        function applyTheme(theme) {
            if (theme === 'night') {
                themeRoot.classList.add('night');
                darkToggle.textContent = '☀️';
                darkToggle.title = 'Switch to light mode';
            } else {
                themeRoot.classList.remove('night');
                darkToggle.textContent = '🌙';
                darkToggle.title = 'Switch to dark mode';
            }
        }
        
        var saved = localStorage.getItem(themeKey);
        if (saved === 'night') {
            applyTheme('night');
        }
        
        darkToggle.addEventListener('click', function() {
            var isNight = themeRoot.classList.contains('night');
            var newTheme = isNight ? 'day' : 'night';
            localStorage.setItem(themeKey, newTheme);
            applyTheme(newTheme);
        });
    </script>
</body>
</html>