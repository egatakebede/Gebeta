<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

$totalRestaurants = $pdo->query('SELECT COUNT(*) FROM restaurants')->fetchColumn();
$activeRestaurants = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE status = 'active'")->fetchColumn();
$pendingRestaurants = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE status = 'pending'")->fetchColumn();
$totalOrders = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$todayOrders = $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()')->fetchColumn();
$totalUsers = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalRestaurantOwners = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'restaurant'")->fetchColumn();
$stmt = $pdo->query('SELECT SUM(total_amount + delivery_fee) FROM orders');
$revenue = $stmt->fetchColumn() ?: 0;
$stmt = $pdo->query('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE DATE(created_at) = CURDATE()');
$todayRevenue = $stmt->fetchColumn() ?: 0;

$recentOrders = $pdo->query('SELECT o.id, o.order_number, o.status, o.total_amount, o.created_at, r.name AS restaurant_name, u.name AS customer_name FROM orders o JOIN restaurants r ON o.restaurant_id = r.id JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);
$topRestaurants = $pdo->query('SELECT id, name, rating, cuisine_type, status FROM restaurants ORDER BY rating DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px 20px;
            border-radius: 0 0 24px 24px;
            margin-bottom: 24px;
        }
        .admin-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .admin-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .stat-card strong {
            display: block;
            font-size: 32px;
            color: var(--primary-orange);
            margin-bottom: 8px;
        }
        .stat-card span {
            color: var(--gray-text);
            font-size: 13px;
            font-weight: 600;
        }
        .stat-card .stat-badge {
            display: inline-block;
            background: rgba(252, 128, 25, 0.1);
            color: var(--primary-orange);
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            margin-top: 8px;
        }
        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--dark-text);
        }
        .order-list-item {
            background: #fff;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
        }
        .order-list-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .order-number {
            font-weight: 700;
            font-size: 16px;
            color: var(--dark-text);
        }
        .order-meta {
            font-size: 13px;
            color: var(--gray-text);
            margin-bottom: 4px;
        }
        .restaurant-list-item {
            background: #fff;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
        }
        .restaurant-list-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .restaurant-info h4 {
            font-size: 16px;
            margin-bottom: 4px;
            color: var(--dark-text);
        }
        .restaurant-info p {
            font-size: 13px;
            color: var(--gray-text);
        }
        .rating-badge {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #fff;
            padding: 8px 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>👨‍💼 Admin Dashboard</h1>
        <p>Welcome back! Here's what's happening today.</p>
    </div>
    
    <main class="page-content">
        <section class="stats-grid">
            <div class="stat-card">
                <strong><?= $totalRestaurants ?></strong>
                <span>Total Restaurants</span>
                <div class="stat-badge"><?= $activeRestaurants ?> Active</div>
            </div>
            <div class="stat-card">
                <strong><?= $totalOrders ?></strong>
                <span>Total Orders</span>
                <div class="stat-badge"><?= $todayOrders ?> Today</div>
            </div>
            <div class="stat-card">
                <strong><?= $totalUsers ?></strong>
                <span>Total Users</span>
                <div class="stat-badge"><?= $totalCustomers ?> Customers</div>
            </div>
            <div class="stat-card">
                <strong><?= number_format($revenue, 0) ?></strong>
                <span>Total Revenue (Birr)</span>
                <div class="stat-badge"><?= number_format($todayRevenue, 0) ?> Today</div>
            </div>
        </section>

        <?php if ($pendingRestaurants > 0): ?>
        <section style="background:#FFF3E0;border:2px solid #FFB74D;border-radius:16px;padding:16px;margin-bottom:24px;">
            <strong style="color:#F57C00;font-size:16px;">Attention <?= $pendingRestaurants ?> restaurant(s) pending approval</strong>
            <p style="color:#E65100;font-size:13px;margin-top:4px;">Review and approve new restaurant applications</p>
            <a href="/admin/restaurants.php" class="pill-button" style="margin-top:12px;display:inline-flex;">Review Now</a>
        </section>
        <?php endif; ?>

        <section>
            <h2 class="section-title">🏆 Top Restaurants</h2>
            <?php foreach ($topRestaurants as $restaurant): ?>
                <div class="restaurant-list-item">
                    <div class="restaurant-info">
                        <h4><?= htmlspecialchars($restaurant['name']) ?></h4>
                        <p><?= htmlspecialchars($restaurant['cuisine_type']) ?> • <?= ucfirst($restaurant['status']) ?></p>
                    </div>
                    <div class="rating-badge">Rating <?= number_format($restaurant['rating'], 1) ?></div>
                </div>
            <?php endforeach; ?>
        </section>

        <section style="margin-top:32px;">
            <h2 class="section-title">Orders Recent Orders</h2>
            <?php foreach ($recentOrders as $order): ?>
                <a href="/admin/orders.php" class="order-list-item" style="display:block;text-decoration:none;color:inherit;">
                    <div class="order-header">
                        <span class="order-number">#<?= htmlspecialchars($order['order_number']) ?></span>
                        <span class="status-badge" style="background:<?= $order['status'] === 'delivered' ? '#E8F5E9' : '#FFF3E0' ?>;color:<?= $order['status'] === 'delivered' ? '#2E7D32' : '#F57C00' ?>;padding:6px 12px;border-radius:999px;font-size:11px;font-weight:700;"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></span>
                    </div>
                    <div class="order-meta"><?= htmlspecialchars($order['restaurant_name']) ?> • <?= htmlspecialchars($order['customer_name']) ?></div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                        <span style="font-size:12px;color:var(--gray-text);"><?= date('M d, Y g:i A', strtotime($order['created_at'])) ?></span>
                        <strong style="color:var(--primary-orange);font-size:15px;"><?= number_format($order['total_amount'], 2) ?> Birr</strong>
                    </div>
                </a>
            <?php endforeach; ?>
        </section>
    </main>

    <footer class="bottom-bar">
        <a href="/admin/dashboard.php" class="active">
            <span>Home</span>
            <span>Dashboard</span>
        </a>
        <a href="/admin/restaurants.php">
            <span>Hawassa</span>
            <span>Restaurants</span>
        </a>
        <a href="/admin/users.php">
            <span>👥</span>
            <span>Users</span>
        </a>
        <a href="/admin/orders.php">
            <span>Orders</span>
            <span>Orders</span>
        </a>
        <a href="/admin/reports.php">
            <span>Analytics</span>
            <span>Reports</span>
        </a>
    </footer>
</body>
</html>
