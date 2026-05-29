<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    
    $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->execute([$newStatus, $orderId]);
    $successMsg = 'Order status updated successfully';
}

// Get filter and search parameters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = '
    SELECT o.id, o.order_number, o.status, o.total_amount, o.delivery_fee, o.created_at, 
           u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
           r.name AS restaurant_name, r.location AS restaurant_location
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN restaurants r ON o.restaurant_id = r.id 
    WHERE 1=1
';
$params = [];

if ($search) {
    $query .= ' AND (o.order_number LIKE ? OR u.name LIKE ? OR r.name LIKE ?)';
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($filter !== 'all') {
    $query .= ' AND o.status = ?';
    $params[] = $filter;
}

$query .= ' ORDER BY o.created_at DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for stats
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$pendingCount = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'");
$deliveredCount = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'");
$cancelledCount = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$totalCount = $stmt->fetchColumn();

// Calculate total revenue
$stmt = $pdo->query("SELECT SUM(total_amount + delivery_fee) FROM orders WHERE status = 'delivered'");
$totalRevenue = $stmt->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Manage Orders · Gebeta Admin</title>
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
            --purple: #8B5CF6;
            --purple-light: #EDE9FE;
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
        }
        
        body.night {
            background: var(--gray-900);
            color: var(--gray-100);
        }
        
        body.night .sidebar,
        body.night .top-header,
        body.night .stat-card,
        body.night .order-card,
        body.night .filter-bar,
        body.night .modal-content {
            background: var(--gray-800);
            border-color: var(--gray-700);
        }
        
        body.night .search-input,
        body.night .filter-select {
            background: var(--gray-700);
            color: var(--gray-100);
            border-color: var(--gray-600);
        }
        
        /* Layout */
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
        
        body.night .nav-item.active {
            background: var(--gray-700);
            color: var(--primary);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            transition: margin-left 0.3s ease;
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
        
        .back-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: none;
            border: none;
            color: var(--gray-600);
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
        }
        
        .back-btn:hover {
            background: var(--gray-100);
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
        
        /* Content */
        .content-area {
            padding: 2rem;
        }
        
        .page-title {
            font-size: 1.875rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: var(--gray-500);
            margin-bottom: 2rem;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--gray-200);
            text-align: center;
            transition: all 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-500);
        }
        
        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--gray-200);
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.5rem 1.5rem;
            background: var(--gray-100);
            border: none;
            border-radius: 2rem;
            text-decoration: none;
            color: var(--gray-600);
            font-size: 0.875rem;
            cursor: pointer;
        }
        
        body.night .filter-tab {
            background: var(--gray-700);
            color: var(--gray-300);
        }
        
        .filter-tab.active {
            background: var(--primary);
            color: white;
        }
        
        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 0.5rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }
        
        /* Orders Grid */
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
        }
        
        .order-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--gray-200);
            overflow: hidden;
            transition: all 0.2s;
        }
        
        .order-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        
        .order-header {
            padding: 1rem 1.25rem;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        body.night .order-header {
            background: var(--gray-700);
            border-bottom-color: var(--gray-600);
        }
        
        .order-number {
            font-weight: bold;
            font-size: 1rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-badge.pending { background: var(--warning-light); color: var(--warning); }
        .status-badge.confirmed { background: var(--info-light); color: var(--info); }
        .status-badge.preparing { background: var(--info-light); color: var(--info); }
        .status-badge.ready { background: var(--info-light); color: var(--info); }
        .status-badge.out_for_delivery { background: var(--purple-light); color: var(--purple); }
        .status-badge.delivered { background: var(--success-light); color: var(--success); }
        .status-badge.cancelled { background: var(--danger-light); color: var(--danger); }
        
        .order-body {
            padding: 1.25rem;
        }
        
        .order-detail {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--gray-100);
        }
        
        body.night .order-detail {
            border-bottom-color: var(--gray-700);
        }
        
        .detail-icon {
            font-size: 1rem;
            min-width: 28px;
        }
        
        .detail-label {
            font-size: 0.75rem;
            color: var(--gray-500);
        }
        
        .detail-value {
            font-weight: 500;
        }
        
        .amount {
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .order-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--gray-200);
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        body.night .order-footer {
            border-top-color: var(--gray-700);
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary { background: var(--primary); color: white; }
        .btn-secondary { background: var(--gray-100); color: var(--gray-600); }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray-500);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            max-width: 400px;
            width: 90%;
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .modal-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
        
        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
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

        .sidebar-overlay.active {
            display: block;
        }
        
        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            .sidebar.collapsed {
                width: 260px;
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
                padding: 1rem;
            }
            .content-area {
                padding: 1rem;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            .orders-grid {
                grid-template-columns: 1fr;
            }
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body id="adminThemeRoot">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">G</div>
                <div class="sidebar-title">Gebeta</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section-title">MAIN</div>
                <a href="/admin/dashboard.php" class="nav-item">
                    <span>📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="/admin/analytics.php" class="nav-item">
                    <span>📈</span>
                    <span>Analytics</span>
                </a>
                
                <div class="nav-section-title">MANAGEMENT</div>
                <a href="/admin/restaurants.php" class="nav-item">
                    <span>🏪</span>
                    <span>Restaurants</span>
                </a>
                <a href="/admin/users.php" class="nav-item">
                    <span>👥</span>
                    <span>Users</span>
                </a>
                <a href="/admin/orders.php" class="nav-item active">
                    <span>📦</span>
                    <span>Orders</span>
                </a>
                <a href="/admin/delivery-partners.php" class="nav-item">
                    <span>🚚</span>
                    <span>Delivery</span>
                </a>
                
                <div class="nav-section-title">ACCOUNT</div>
                <a href="/logout.php" class="nav-item">
                    <span>🚪</span>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">☰</button>
                    <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                    <button class="back-btn" onclick="history.back()">
                        <span>←</span>
                        <span>Back</span>
                    </button>
                </div>
                
                <div class="search-box">
                    <span>🔍</span>
                    <input type="text" placeholder="Search orders..." id="globalSearch">
                </div>
                
                <div class="header-actions">
                    <button class="theme-toggle" id="themeToggle">🌙</button>
                    <div class="user-badge">
                        <div class="user-avatar">A</div>
                        <span>Admin</span>
                    </div>
                </div>
            </header>
            
            <div class="content-area">
                <div class="page-title">📦 Manage Orders</div>
                <div class="page-subtitle">Track and manage all customer orders</div>
                
                <?php if (isset($successMsg)): ?>
                    <div class="alert alert-success">✅ <?= $successMsg ?></div>
                <?php endif; ?>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?= number_format($totalRevenue, 0) ?> Birr</div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $totalCount ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $pendingCount ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $deliveredCount ?></div>
                        <div class="stat-label">Delivered</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $cancelledCount ?></div>
                        <div class="stat-label">Cancelled</div>
                    </div>
                </div>
                
                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="filter-tabs">
                        <a href="?filter=all<?= $search ? '&search=' . urlencode($search) : '' ?>" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">All Orders</a>
                        <a href="?filter=pending<?= $search ? '&search=' . urlencode($search) : '' ?>" class="filter-tab <?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
                        <a href="?filter=confirmed<?= $search ? '&search=' . urlencode($search) : '' ?>" class="filter-tab <?= $filter === 'confirmed' ? 'active' : '' ?>">Confirmed</a>
                        <a href="?filter=preparing<?= $search ? '&search=' . urlencode($search) : '' ?>" class="filter-tab <?= $filter === 'preparing' ? 'active' : '' ?>">Preparing</a>
                        <a href="?filter=out_for_delivery<?= $search ? '&search=' . urlencode($search) : '' ?>" class="filter-tab <?= $filter === 'out_for_delivery' ? 'active' : '' ?>">Out for Delivery</a>
                        <a href="?filter=delivered<?= $search ? '&search=' . urlencode($search) : '' ?>" class="filter-tab <?= $filter === 'delivered' ? 'active' : '' ?>">Delivered</a>
                        <a href="?filter=cancelled<?= $search ? '&search=' . urlencode($search) : '' ?>" class="filter-tab <?= $filter === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
                    </div>
                    <form method="GET" action="" style="display: flex; gap: 0.5rem; flex: 1;">
                        <input type="text" name="search" class="search-input" placeholder="Search by order #, customer, restaurant..." value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="filter" value="<?= $filter ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
                
                <!-- Orders Grid -->
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                        <div>No orders found</div>
                    </div>
                <?php else: ?>
                    <div class="orders-grid">
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <span class="order-number">#<?= htmlspecialchars($order['order_number']) ?></span>
                                    <span class="status-badge <?= $order['status'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                    </span>
                                </div>
                                <div class="order-body">
                                    <div class="order-detail">
                                        <span class="detail-icon">👤</span>
                                        <div>
                                            <div class="detail-label">Customer</div>
                                            <div class="detail-value"><?= htmlspecialchars($order['customer_name']) ?></div>
                                        </div>
                                    </div>
                                    <div class="order-detail">
                                        <span class="detail-icon">🏪</span>
                                        <div>
                                            <div class="detail-label">Restaurant</div>
                                            <div class="detail-value"><?= htmlspecialchars($order['restaurant_name']) ?></div>
                                        </div>
                                    </div>
                                    <div class="order-detail">
                                        <span class="detail-icon">📍</span>
                                        <div>
                                            <div class="detail-label">Location</div>
                                            <div class="detail-value"><?= htmlspecialchars($order['restaurant_location'] ?? 'N/A') ?></div>
                                        </div>
                                    </div>
                                    <div class="order-detail">
                                        <span class="detail-icon">💰</span>
                                        <div>
                                            <div class="detail-label">Amount</div>
                                            <div class="amount"><?= number_format($order['total_amount'], 2) ?> Birr</div>
                                        </div>
                                    </div>
                                    <div class="order-detail">
                                        <span class="detail-icon">📅</span>
                                        <div>
                                            <div class="detail-label">Date</div>
                                            <div class="detail-value"><?= date('M d, Y g:i A', strtotime($order['created_at'])) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="order-footer">
                                    <button class="btn btn-secondary" onclick="viewOrder(<?= $order['id'] ?>)">📋 View Details</button>
                                    <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                                        <button class="btn btn-primary" onclick="openStatusModal(<?= $order['id'] ?>, '<?= $order['status'] ?>')">🔄 Update Status</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Status Update Modal -->
    <div class="modal" id="statusModal">
        <div class="modal-content">
            <div class="modal-title">Update Order Status</div>
            <form method="POST">
                <input type="hidden" name="order_id" id="modalOrderId">
                <select name="status" class="modal-select" id="modalStatus">
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="preparing">Preparing</option>
                    <option value="ready">Ready</option>
                    <option value="out_for_delivery">Out for Delivery</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Mobile Bottom Navigation -->
    <nav class="bottom-nav">
        <div class="bottom-nav-items">
            <a href="/admin/dashboard.php" class="bottom-nav-item">
                <span>📊</span>
                <span>Home</span>
            </a>
            <a href="/admin/restaurants.php" class="bottom-nav-item">
                <span>🏪</span>
                <span>Restaurants</span>
            </a>
            <a href="/admin/users.php" class="bottom-nav-item">
                <span>👥</span>
                <span>Users</span>
            </a>
            <a href="/admin/orders.php" class="bottom-nav-item active">
                <span>📦</span>
                <span>Orders</span>
            </a>
            <a href="/admin/analytics.php" class="bottom-nav-item">
                <span>📈</span>
                <span>Analytics</span>
            </a>
        </div>
    </nav>
    
    <script>
        // Sidebar
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const menuToggle = document.getElementById('menuToggle');
        const overlay = document.getElementById('sidebarOverlay');
        
        sidebarToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });
        
        function closeMenu() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        }
        
        function openMenu() {
            sidebar.classList.add('mobile-open');
            overlay.classList.add('active');
        }
        
        menuToggle?.addEventListener('click', openMenu);
        overlay?.addEventListener('click', closeMenu);
        window.addEventListener('resize', () => { if (window.innerWidth > 768) closeMenu(); });
        
        // Modal functions
        function openStatusModal(orderId, currentStatus) {
            document.getElementById('modalOrderId').value = orderId;
            document.getElementById('modalStatus').value = currentStatus;
            document.getElementById('statusModal').classList.add('active');
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
        }
        
        // View order
        function viewOrder(id) {
            window.location.href = `/admin/orders.php?id=${id}`;
        }
        
        // Global search
        document.getElementById('globalSearch')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    window.location.href = `/admin/orders.php?search=${encodeURIComponent(searchTerm)}&filter=<?= $filter ?>`;
                }
            }
        });
        
        // Dark mode
        const themeRoot = document.getElementById('adminThemeRoot');
        const themeToggle = document.getElementById('themeToggle');
        const themeKey = 'gebeta_admin_theme';
        
        function applyTheme(theme) {
            const isNight = theme === 'night';
            themeRoot.classList.toggle('night', isNight);
            themeToggle.textContent = isNight ? '☀️' : '🌙';
        }
        
        const savedTheme = localStorage.getItem(themeKey);
        if (savedTheme) applyTheme(savedTheme);
        else if (window.matchMedia('(prefers-color-scheme: dark)').matches) applyTheme('night');
        
        themeToggle?.addEventListener('click', () => {
            const isNight = themeRoot.classList.contains('night');
            const newTheme = isNight ? 'day' : 'night';
            localStorage.setItem(themeKey, newTheme);
            applyTheme(newTheme);
        });
        
        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target === modal) {
                closeStatusModal();
            }
        }
    </script>
</body>
</html>