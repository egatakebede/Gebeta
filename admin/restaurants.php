<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $restaurantId = $_POST['restaurant_id'] ?? null;
    
    if ($action === 'toggle_status') {
        $stmt = $pdo->prepare('SELECT status FROM restaurants WHERE id = ?');
        $stmt->execute([$restaurantId]);
        $current = $stmt->fetchColumn();
        $newStatus = $current === 'active' ? 'suspended' : 'active';
        $stmt = $pdo->prepare('UPDATE restaurants SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $restaurantId]);
        echo json_encode(['success' => true, 'new_status' => $newStatus]);
        exit();
    }
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare('UPDATE restaurants SET status = "active", approved_at = NOW() WHERE id = ?');
        $stmt->execute([$restaurantId]);
        echo json_encode(['success' => true]);
        exit();
    }
    
    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM restaurants WHERE id = ?');
        $stmt->execute([$restaurantId]);
        echo json_encode(['success' => true]);
        exit();
    }
    
    if ($action === 'add') {
        $name = $_POST['name'] ?? '';
        $cuisine_type = $_POST['cuisine_type'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $location = $_POST['location'] ?? '';
        $description = $_POST['description'] ?? '';
        
        $stmt = $pdo->prepare('INSERT INTO restaurants (name, cuisine_type, phone, location, description, status, rating, created_at) VALUES (?, ?, ?, ?, ?, "pending", 0, NOW())');
        $stmt->execute([$name, $cuisine_type, $phone, $location, $description]);
        echo json_encode(['success' => true, 'restaurant_id' => $pdo->lastInsertId()]);
        exit();
    }
}

// Get filter and search parameters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$query = 'SELECT r.*, COALESCE(COUNT(o.id), 0) as order_count FROM restaurants r LEFT JOIN orders o ON r.id = o.restaurant_id WHERE 1=1';
$countQuery = 'SELECT COUNT(*) FROM restaurants WHERE 1=1';
$params = [];

if ($search) {
    $query .= ' AND (r.name LIKE ? OR r.location LIKE ? OR r.cuisine_type LIKE ?)';
    $countQuery .= ' AND (name LIKE ? OR location LIKE ? OR cuisine_type LIKE ?)';
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($filter === 'active') {
    $query .= ' AND r.status = ?';
    $countQuery .= ' AND status = ?';
    $params[] = 'active';
} elseif ($filter === 'pending') {
    $query .= ' AND r.status = ?';
    $countQuery .= ' AND status = ?';
    $params[] = 'pending';
} elseif ($filter === 'suspended') {
    $query .= ' AND r.status = ?';
    $countQuery .= ' AND status = ?';
    $params[] = 'suspended';
}

$query .= ' GROUP BY r.id ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset';

// Execute count query
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalRestaurants = $stmt->fetchColumn();
$totalPages = ceil($totalRestaurants / $limit);

// Execute main query with bound parameters
$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $value) {
    $stmt->bindValue($key + 1, $value);
}
$stmt->execute();
$restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for stats
$stmt = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE status = 'active'");
$activeCount = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE status = 'pending'");
$pendingCount = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE status = 'suspended'");
$suspendedCount = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM restaurants");
$totalAll = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT AVG(rating) FROM restaurants WHERE rating > 0");
$avgRating = $stmt->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Manage Restaurants · Gebeta Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #FC8019;
            --primary-dark: #E56B0F;
            --primary-light: #FFF5ED;
            --success: #10B981;
            --success-light: #E8F5E9;
            --danger: #EF4444;
            --danger-light: #FFEBEE;
            --warning: #F59E0B;
            --warning-light: #FFF3E0;
            --info: #3B82F6;
            --info-light: #E3F2FD;
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
        }
        
        body.night {
            background: var(--gray-900);
        }
        
        body.night .sidebar,
        body.night .top-header,
        body.night .stat-card,
        body.night .data-table-container,
        body.night .filter-bar,
        body.night .modal-content,
        body.night .pagination {
            background: var(--gray-800);
            border-color: var(--gray-700);
            color: var(--gray-100);
        }
        
        body.night .data-table thead {
            background: var(--gray-700);
        }
        
        body.night .data-table td {
            border-bottom-color: var(--gray-700);
        }
        
        body.night .bulk-actions {
            background: var(--gray-700);
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
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
        
        .main-content {
            flex: 1;
            margin-left: 260px;
            transition: margin-left 0.3s ease;
            width: 100%;
        }
        
        .main-content.expanded {
            margin-left: 80px;
        }
        
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
        
        .add-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 0.75rem;
            border: 1px solid var(--gray-200);
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 0.25rem;
        }
        
        .filter-bar {
            background: white;
            padding: 1rem;
            border-radius: 0.75rem;
            border: 1px solid var(--gray-200);
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.5rem 1rem;
            background: var(--gray-100);
            border: none;
            border-radius: 2rem;
            text-decoration: none;
            color: var(--gray-600);
            font-size: 0.8rem;
            cursor: pointer;
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
            font-size: 0.8rem;
        }
        
        .data-table-container {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid var(--gray-200);
            overflow-x: auto;
        }
        
        .bulk-actions {
            background: var(--primary-light);
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .selected-count {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        .bulk-btn {
            padding: 0.4rem 0.8rem;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .bulk-btn.danger {
            color: var(--danger);
        }
        
        .bulk-close {
            margin-left: auto;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }
        
        .data-table thead {
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }
        
        .data-table th {
            padding: 0.8rem 1rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--gray-500);
            text-transform: uppercase;
            cursor: pointer;
        }
        
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            font-size: 0.8rem;
        }
        
        .data-table tr:hover {
            background: var(--gray-50);
        }
        
        .checkbox-cell {
            width: 40px;
        }
        
        .restaurant-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .restaurant-avatar {
            width: 48px;
            height: 48px;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .restaurant-name {
            font-weight: 600;
            margin-bottom: 0.2rem;
        }
        
        .restaurant-id {
            font-size: 0.7rem;
            color: var(--gray-400);
        }
        
        .cuisine-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            background: var(--info-light);
            color: var(--info);
            border-radius: 0.5rem;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .rating-cell {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .stars {
            color: #FBBF24;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .status-badge.active { background: var(--success-light); color: var(--success); }
        .status-badge.pending { background: var(--warning-light); color: var(--warning); }
        .status-badge.suspended { background: var(--danger-light); color: var(--danger); }
        
        .action-menu {
            position: relative;
        }
        
        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.3rem;
            color: var(--gray-500);
        }
        
        .action-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            min-width: 160px;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        body.night .action-dropdown {
            background: var(--gray-800);
            border-color: var(--gray-700);
        }
        
        .action-dropdown.open {
            display: block;
        }
        
        .action-dropdown a {
            display: block;
            padding: 0.6rem 1rem;
            color: var(--gray-600);
            text-decoration: none;
            font-size: 0.75rem;
        }
        
        .action-dropdown a:hover {
            background: var(--gray-100);
        }
        
        .action-dropdown a.danger {
            color: var(--danger);
        }
        
        .action-dropdown hr {
            margin: 0.3rem 0;
            border-color: var(--gray-200);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }
        
        .page-btn, .page-number {
            padding: 0.5rem 0.8rem;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .page-number.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.open {
            display: flex;
        }
        
        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            position: relative;
            background: white;
            border-radius: 1rem;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--gray-200);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            font-size: 0.8rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-600);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray-500);
        }
        
        .bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid var(--gray-200);
            padding: 0.6rem;
            z-index: 100;
        }
        
        .bottom-nav-items {
            display: flex;
            justify-content: space-around;
        }
        
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.2rem;
            text-decoration: none;
            color: var(--gray-500);
            font-size: 0.65rem;
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
        
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
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
                padding-bottom: 60px;
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
                padding: 0.8rem 1rem;
            }
            .content-area {
                padding: 1rem;
            }
            .page-title {
                font-size: 1.3rem;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
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
            .header-actions {
                gap: 0.5rem;
            }
            .add-btn span {
                display: none;
            }
        }
    </style>
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
                <a href="/admin/analytics.php" class="nav-item"><span>📈</span><span>Analytics</span></a>
                <div class="nav-section-title">MANAGEMENT</div>
                <a href="/admin/restaurants.php" class="nav-item active"><span>🏪</span><span>Restaurants</span></a>
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
                <div class="search-box">
                    <span>🔍</span>
                    <input type="text" id="globalSearch" placeholder="Search restaurants...">
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="themeToggle">🌙</button>
                    <button class="add-btn" onclick="openAddRestaurantModal()">
                        <span>➕</span>
                        <span>Add</span>
                    </button>
                    <div class="user-badge">
                        <div class="user-avatar">A</div>
                        <span>Admin</span>
                    </div>
                </div>
            </header>
            
            <div class="content-area">
                <h1 class="page-title">🍽️ Manage Restaurants</h1>
                <p class="page-subtitle">Manage restaurant partners, approvals, and performance</p>
                
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-value"><?= $totalAll ?></div><div class="stat-label">Total</div></div>
                    <div class="stat-card"><div class="stat-value"><?= $activeCount ?></div><div class="stat-label">Active</div></div>
                    <div class="stat-card"><div class="stat-value"><?= $pendingCount ?></div><div class="stat-label">Pending</div></div>
                    <div class="stat-card"><div class="stat-value"><?= $suspendedCount ?></div><div class="stat-label">Suspended</div></div>
                    <div class="stat-card"><div class="stat-value"><?= number_format($avgRating, 1) ?> ⭐</div><div class="stat-label">Avg Rating</div></div>
                </div>
                
                <div class="filter-bar">
                    <div class="filter-tabs">
                        <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">All</a>
                        <a href="?filter=active" class="filter-tab <?= $filter === 'active' ? 'active' : '' ?>">Active</a>
                        <a href="?filter=pending" class="filter-tab <?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
                        <a href="?filter=suspended" class="filter-tab <?= $filter === 'suspended' ? 'active' : '' ?>">Suspended</a>
                    </div>
                    <form method="GET" action="" style="display: flex; gap: 0.5rem; flex: 1;">
                        <input type="text" name="search" class="search-input" placeholder="Search by name, location..." value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="filter" value="<?= $filter ?>">
                        <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem;">Search</button>
                    </form>
                </div>
                
                <div class="data-table-container">
                    <div class="bulk-actions" id="bulkActions" style="display:none;">
                        <span class="selected-count"><span id="selectedCount">0</span> selected</span>
                        <button class="bulk-btn" onclick="bulkApprove()">✓ Approve</button>
                        <button class="bulk-btn" onclick="bulkActivate()">✓ Activate</button>
                        <button class="bulk-btn danger" onclick="bulkSuspend()">⚠ Suspend</button>
                        <button class="bulk-btn danger" onclick="bulkDelete()">🗑 Delete</button>
                        <button class="bulk-close" onclick="clearSelection()">✕</button>
                    </div>
                    
                    <?php if (empty($restaurants)): ?>
                        <div class="empty-state">No restaurants found</div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-cell"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                                    <th>Restaurant</th>
                                    <th>Cuisine</th>
                                    <th>Location</th>
                                    <th>Phone</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Orders</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($restaurants as $restaurant): ?>
                                <tr id="restaurant-row-<?= $restaurant['id'] ?>">
                                    <td class="checkbox-cell"><input type="checkbox" class="row-checkbox" value="<?= $restaurant['id'] ?>" onchange="updateBulkActions()"></td>
                                    <td>
                                        <div class="restaurant-info">
                                            <div class="restaurant-avatar">🍽️</div>
                                            <div>
                                                <div class="restaurant-name"><?= htmlspecialchars($restaurant['name']) ?></div>
                                                <div class="restaurant-id">ID: <?= $restaurant['id'] ?></div>
                                            </div>
                                        </div>
                                    </td
                                    <td><span class="cuisine-badge"><?= htmlspecialchars($restaurant['cuisine_type']) ?></span></td>
                                    <td><?= htmlspecialchars($restaurant['location']) ?></td>
                                    <td><?= htmlspecialchars($restaurant['phone'] ?? 'N/A') ?></td>
                                    <td>
                                        <div class="rating-cell">
                                            <span class="stars">⭐ <?= number_format($restaurant['rating'], 1) ?></span>
                                        </div>
                                    </td
                                    <td><span class="status-badge <?= $restaurant['status'] ?>"><?= ucfirst($restaurant['status']) ?></span></td>
                                    <td><strong><?= $restaurant['order_count'] ?></strong></td>
                                    <td>
                                        <div class="action-menu">
                                            <button class="action-btn" onclick="toggleActionMenu(this)">⋮</button>
                                            <div class="action-dropdown">
                                                <a href="#" onclick="viewRestaurant(<?= $restaurant['id'] ?>)">👁 View</a>
                                                <a href="#" onclick="editRestaurant(<?= $restaurant['id'] ?>)">✏ Edit</a>
                                                <hr>
                                                <?php if ($restaurant['status'] === 'pending'): ?>
                                                    <a href="#" onclick="approveRestaurant(<?= $restaurant['id'] ?>)">✓ Approve</a>
                                                <?php endif; ?>
                                                <?php if ($restaurant['status'] === 'active'): ?>
                                                    <a href="#" onclick="suspendRestaurant(<?= $restaurant['id'] ?>)">⚠ Suspend</a>
                                                <?php endif; ?>
                                                <?php if ($restaurant['status'] === 'suspended'): ?>
                                                    <a href="#" onclick="activateRestaurant(<?= $restaurant['id'] ?>)">✓ Activate</a>
                                                <?php endif; ?>
                                                <hr>
                                                <a class="danger" href="#" onclick="deleteRestaurant(<?= $restaurant['id'] ?>)">🗑 Delete</a>
                                            </div>
                                        </div>
                                    </td
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page-1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" class="page-btn">← Previous</a>
                            <?php endif; ?>
                            <div class="page-numbers">
                                <?php for($i = 1; $i <= min(5, $totalPages); $i++): ?>
                                    <a href="?page=<?= $i ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" class="page-number <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                                <?php endfor; ?>
                                <?php if($totalPages > 5): ?>
                                    <span>...</span>
                                    <a href="?page=<?= $totalPages ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" class="page-number"><?= $totalPages ?></a>
                                <?php endif; ?>
                            </div>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page+1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" class="page-btn">Next →</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <nav class="bottom-nav">
        <div class="bottom-nav-items">
            <a href="/admin/dashboard.php" class="bottom-nav-item"><span>📊</span><span>Home</span></a>
            <a href="/admin/restaurants.php" class="bottom-nav-item active"><span>🏪</span><span>Restaurants</span></a>
            <a href="/admin/users.php" class="bottom-nav-item"><span>👥</span><span>Users</span></a>
            <a href="/admin/orders.php" class="bottom-nav-item"><span>📦</span><span>Orders</span></a>
            <a href="/admin/analytics.php" class="bottom-nav-item"><span>📈</span><span>Analytics</span></a>
        </div>
    </nav>
    
    <div class="modal" id="restaurantModal">
        <div class="modal-overlay" onclick="closeModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Restaurant</h2>
                <button class="modal-close" onclick="closeModal()">✕</button>
            </div>
            <form id="restaurantForm" onsubmit="submitRestaurantForm(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Restaurant Name *</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Cuisine Type *</label>
                        <select name="cuisine_type" required>
                            <option value="Ethiopian">Ethiopian</option>
                            <option value="Pizza">Pizza</option>
                            <option value="Cafe">Cafe</option>
                            <option value="Fast Food">Fast Food</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Phone *</label>
                        <input type="tel" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label>Location *</label>
                        <input type="text" name="location" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Restaurant</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menuToggle');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');
        
        function closeMenu() { sidebar.classList.remove('mobile-open'); overlay.classList.remove('active'); }
        function openMenu() { sidebar.classList.add('mobile-open'); overlay.classList.add('active'); }
        
        menuToggle?.addEventListener('click', openMenu);
        overlay?.addEventListener('click', closeMenu);
        sidebarToggle?.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
        window.addEventListener('resize', () => { if (window.innerWidth > 768) closeMenu(); });
        
        function toggleActionMenu(btn) {
            document.querySelectorAll('.action-dropdown.open').forEach(d => d.classList.remove('open'));
            btn.nextElementSibling.classList.toggle('open');
        }
        
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.action-menu')) {
                document.querySelectorAll('.action-dropdown.open').forEach(d => d.classList.remove('open'));
            }
        });
        
        let selectedRestaurants = new Set();
        
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const bulkActions = document.getElementById('bulkActions');
            
            selectedRestaurants.clear();
            checkboxes.forEach(cb => selectedRestaurants.add(cb.value));
            
            if (selectedRestaurants.size > 0) {
                bulkActions.style.display = 'flex';
                document.getElementById('selectedCount').textContent = selectedRestaurants.size;
            } else {
                bulkActions.style.display = 'none';
            }
            
            document.getElementById('selectAll').checked = checkboxes.length === document.querySelectorAll('.row-checkbox').length;
        }
        
        function toggleSelectAll(checkbox) {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = checkbox.checked);
            updateBulkActions();
        }
        
        function clearSelection() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
            updateBulkActions();
            document.getElementById('bulkActions').style.display = 'none';
        }
        
        async function bulkApprove() {
            if (!confirm(`Approve ${selectedRestaurants.size} restaurants?`)) return;
            for (let id of selectedRestaurants) {
                await fetch('/admin/restaurants.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: `action=approve&restaurant_id=${id}`
                });
            }
            location.reload();
        }
        
        async function bulkActivate() {
            if (!confirm(`Activate ${selectedRestaurants.size} restaurants?`)) return;
            for (let id of selectedRestaurants) {
                await fetch('/admin/restaurants.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: `action=toggle_status&restaurant_id=${id}`
                });
            }
            location.reload();
        }
        
        async function bulkSuspend() {
            if (!confirm(`Suspend ${selectedRestaurants.size} restaurants?`)) return;
            for (let id of selectedRestaurants) {
                await fetch('/admin/restaurants.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: `action=toggle_status&restaurant_id=${id}`
                });
            }
            location.reload();
        }
        
        async function bulkDelete() {
            if (!confirm(`Delete ${selectedRestaurants.size} restaurants? This cannot be undone!`)) return;
            for (let id of selectedRestaurants) {
                await fetch('/admin/restaurants.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: `action=delete&restaurant_id=${id}`
                });
            }
            location.reload();
        }
        
        async function approveRestaurant(id) {
            if (!confirm('Approve this restaurant?')) return;
            await fetch('/admin/restaurants.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: `action=approve&restaurant_id=${id}`
            });
            location.reload();
        }
        
        async function suspendRestaurant(id) {
            if (!confirm('Suspend this restaurant?')) return;
            await fetch('/admin/restaurants.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: `action=toggle_status&restaurant_id=${id}`
            });
            location.reload();
        }
        
        async function activateRestaurant(id) {
            if (!confirm('Activate this restaurant?')) return;
            await fetch('/admin/restaurants.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: `action=toggle_status&restaurant_id=${id}`
            });
            location.reload();
        }
        
        async function deleteRestaurant(id) {
            if (!confirm('Delete this restaurant permanently?')) return;
            await fetch('/admin/restaurants.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: `action=delete&restaurant_id=${id}`
            });
            document.getElementById(`restaurant-row-${id}`)?.remove();
        }
        
        function viewRestaurant(id) { window.location.href = `/admin/restaurant-details.php?id=${id}`; }
        function editRestaurant(id) { window.location.href = `/admin/edit-restaurant.php?id=${id}`; }
        
        function openAddRestaurantModal() {
            document.getElementById('restaurantModal').classList.add('open');
            document.getElementById('restaurantForm').reset();
        }
        
        function closeModal() {
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('open'));
        }
        
        async function submitRestaurantForm(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            formData.append('action', 'add');
            
            const response = await fetch('/admin/restaurants.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                closeModal();
                location.reload();
            }
        }
        
        const themeRoot = document.getElementById('adminThemeRoot');
        const themeToggle = document.getElementById('themeToggle');
        
        function applyTheme(theme) {
            const isNight = theme === 'night';
            themeRoot.classList.toggle('night', isNight);
            themeToggle.textContent = isNight ? '☀️' : '🌙';
        }
        
        const saved = localStorage.getItem('gebeta_admin_theme');
        if (saved) applyTheme(saved);
        else if (window.matchMedia('(prefers-color-scheme: dark)').matches) applyTheme('night');
        
        themeToggle?.addEventListener('click', () => {
            const isNight = themeRoot.classList.contains('night');
            const next = isNight ? 'day' : 'night';
            localStorage.setItem('gebeta_admin_theme', next);
            applyTheme(next);
        });
    </script>
</body>
</html>