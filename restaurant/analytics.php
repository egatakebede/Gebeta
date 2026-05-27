<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['restaurant']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE user_id = ? LIMIT 1');
$stmt->execute([$_SESSION['user']['id']]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$restaurant) {
    redirect('/');
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ?');
$stmt->execute([$restaurant['id']]);
$totalOrders = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE restaurant_id = ?');
$stmt->execute([$restaurant['id']]);
$revenue = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare('SELECT status, COUNT(*) AS count FROM orders WHERE restaurant_id = ? GROUP BY status');
$stmt->execute([$restaurant['id']]);
$statusBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fix: Remove the undefined function or define it
// $cartCount = get_cart_count(); // This function doesn't exist

// Get pending orders count
$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND status = "pending"');
$stmt->execute([$restaurant['id']]);
$pendingOrders = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/admin-layout.css">
    <link rel="stylesheet" href="/assets/css/admin-components.css">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: white;
            border-bottom: 1px solid #E8E8E8;
        }
        .page-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .pill-button {
            background: #FC8019;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
        }
        .page-content {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #E8E8E8;
            text-align: center;
        }
        .stat-card strong {
            display: block;
            font-size: 32px;
            color: #FC8019;
            margin-bottom: 10px;
        }
        .stat-card span {
            color: #666;
            font-size: 14px;
        }
        .report-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #E8E8E8;
            padding: 20px;
            margin-bottom: 20px;
        }
        .report-card h2 {
            margin: 0 0 20px 0;
            font-size: 18px;
        }
        .detail-line {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #F0F0F0;
        }
        .detail-line:last-child {
            border-bottom: none;
        }
        .bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #E8E8E8;
            display: flex;
            justify-content: space-around;
            padding: 12px 20px;
            gap: 10px;
        }
        .bottom-bar a {
            text-decoration: none;
            color: #666;
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .bottom-bar a:hover {
            background: #FC8019;
            color: white;
        }
    </style>
</head>
<body>
    <header class="page-header">
        <h1>📊 Analytics Dashboard</h1>
        <a class="pill-button" href="/restaurant/dashboard.php">← Back to Dashboard</a>
    </header>
    
    <main class="page-content">
        <!-- Stats Cards -->
        <section class="stats-grid">
            <div class="stat-card">
                <strong><?= number_format($totalOrders) ?></strong>
                <span>Total Orders</span>
            </div>
            <div class="stat-card">
                <strong><?= number_format($revenue, 2) ?> Birr</strong>
                <span>Total Revenue</span>
            </div>
            <div class="stat-card">
                <strong><?= number_format($pendingOrders) ?></strong>
                <span>Pending Orders</span>
            </div>
            <div class="stat-card">
                <strong><?= number_format($totalOrders > 0 ? $revenue / $totalOrders : 0, 2) ?> Birr</strong>
                <span>Average Order Value</span>
            </div>
        </section>
        
        <!-- Order Status Breakdown -->
        <section class="report-card">
            <h2>📋 Order Status Breakdown</h2>
            <?php if (empty($statusBreakdown)): ?>
                <div class="detail-line">
                    <span>No orders yet</span>
                    <span>0</span>
                </div>
            <?php else: ?>
                <?php foreach ($statusBreakdown as $row): ?>
                    <div class="detail-line">
                        <span>
                            <?php 
                                $statusIcon = match($row['status']) {
                                    'pending' => '⏳',
                                    'preparing' => '👨‍🍳',
                                    'ready' => '✅',
                                    'delivered' => '🚚',
                                    default => '📦'
                                };
                                echo $statusIcon . ' ' . ucfirst(str_replace('_', ' ', $row['status']));
                            ?>
                        </span>
                        <span><strong><?= $row['count'] ?></strong> orders</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        
        <!-- Recent Performance -->
        <section class="report-card">
            <h2>📈 Quick Insights</h2>
            <div class="detail-line">
                <span>Completion Rate</span>
                <span>
                    <?php 
                    $completed = 0;
                    foreach ($statusBreakdown as $row) {
                        if ($row['status'] === 'delivered') {
                            $completed = $row['count'];
                            break;
                        }
                    }
                    $rate = $totalOrders > 0 ? round(($completed / $totalOrders) * 100) : 0;
                    echo $rate . '%';
                    ?>
                </span>
            </div>
            <div class="detail-line">
                <span>Pending Actions</span>
                <span><?= $pendingOrders ?> orders need attention</span>
            </div>
        </section>
    </main>
    
    <footer class="bottom-bar">
        <a href="/restaurant/dashboard.php">🏠 Dashboard</a>
        <a href="/restaurant/menu.php">🍽️ Menu</a>
        <a href="/restaurant/analytics.php">📊 Analytics</a>
        <a href="/restaurant/profile.php">👤 Profile</a>
    </footer>
</body>
</html>