<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Require login for delivery partners only
require_login(['delivery']);

$deliveryId = $_SESSION['user']['id'];
$deliveryName = $_SESSION['user']['name'];
// ... rest of code
$userId = $_SESSION['user']['id'];
$userName = $_SESSION['user']['name'];

// Get delivery partner info
$stmt = $pdo->prepare('SELECT * FROM delivery_partners WHERE user_id = ?');
$stmt->execute([$userId]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partner) {
    redirect('/delivery/register.php');
}

if (!$partner['verified']) {
    redirect('/delivery/pending-approval.php');
}

// Calculate KPIs
$stmt = $pdo->prepare('SELECT COUNT(*) FROM order_deliveries WHERE delivery_partner_id = ?');
$stmt->execute([$partner['id']]);
$totalDeliveries = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM order_deliveries WHERE delivery_partner_id = ? AND DATE(created_at) = CURDATE()');
$stmt->execute([$partner['id']]);
$todayDeliveries = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT SUM(delivery_fee) FROM order_deliveries WHERE delivery_partner_id = ? AND status = "delivered"');
$stmt->execute([$partner['id']]);
$totalEarnings = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare('SELECT SUM(delivery_fee) FROM order_deliveries WHERE delivery_partner_id = ? AND status = "delivered" AND DATE(delivered_at) = CURDATE()');
$stmt->execute([$partner['id']]);
$todayEarnings = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM order_deliveries WHERE delivery_partner_id = ? AND status IN ("assigned", "picked_up", "in_transit")');
$stmt->execute([$partner['id']]);
$activeDeliveries = $stmt->fetchColumn();

// Available orders
$stmt = $pdo->query('
    SELECT od.*, o.order_number, o.total_amount, 
           r.name AS restaurant_name, r.location AS restaurant_location,
           u.name AS customer_name, u.phone AS customer_phone
    FROM order_deliveries od
    JOIN orders o ON od.order_id = o.id
    JOIN restaurants r ON o.restaurant_id = r.id
    JOIN users u ON o.user_id = u.id
    WHERE od.status = "pending" AND od.delivery_partner_id IS NULL
    ORDER BY od.created_at DESC
    LIMIT 10
');
$availableOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// My active deliveries
$stmt = $pdo->prepare('
    SELECT od.*, o.order_number, o.total_amount,
           r.name AS restaurant_name, r.location AS restaurant_location,
           u.name AS customer_name, u.phone AS customer_phone
    FROM order_deliveries od
    JOIN orders o ON od.order_id = o.id
    JOIN restaurants r ON o.restaurant_id = r.id
    JOIN users u ON o.user_id = u.id
    WHERE od.delivery_partner_id = ? AND od.status IN ("assigned", "picked_up", "in_transit")
    ORDER BY od.created_at DESC
');
$stmt->execute([$partner['id']]);
$myDeliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent completed deliveries
$stmt = $pdo->prepare('
    SELECT od.*, o.order_number, o.total_amount,
           r.name AS restaurant_name,
           u.name AS customer_name
    FROM order_deliveries od
    JOIN orders o ON od.order_id = o.id
    JOIN restaurants r ON o.restaurant_id = r.id
    JOIN users u ON o.user_id = u.id
    WHERE od.delivery_partner_id = ? AND od.status = "delivered"
    ORDER BY od.delivered_at DESC
    LIMIT 10
');
$stmt->execute([$partner['id']]);
$completedDeliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/admin-layout.css">
    <link rel="stylesheet" href="/assets/css/admin-components.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">🚚</div>
                <div class="sidebar-title">Delivery</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="/delivery/dashboard.php" class="nav-item active">
                        <span class="nav-item-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="/delivery/available-orders.php" class="nav-item">
                        <span class="nav-item-icon">📦</span>
                        <span>Available Orders</span>
                        <span class="nav-item-badge"><?= count($availableOrders) ?></span>
                    </a>
                    <a href="/delivery/my-deliveries.php" class="nav-item">
                        <span class="nav-item-icon">🚴</span>
                        <span>My Deliveries</span>
                        <?php if ($activeDeliveries > 0): ?>
                        <span class="nav-item-badge"><?= $activeDeliveries ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/delivery/earnings.php" class="nav-item">
                        <span class="nav-item-icon">💰</span>
                        <span>Earnings</span>
                    </a>
                    <a href="/delivery/profile.php" class="nav-item">
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
                
                <div style="flex: 1; display: flex; align-items: center; gap: var(--space-4);">
                    <h2 style="font-size: var(--text-lg); font-weight: var(--font-semibold);">
                        Status: 
                        <span style="color: <?= $partner['status'] === 'online' ? 'var(--green-600)' : 'var(--gray-600)' ?>;">
                            <?= ucfirst($partner['status']) ?>
                        </span>
                    </h2>
                    <button class="action-btn <?= $partner['status'] === 'online' ? 'action-btn-danger' : 'action-btn-success' ?>" onclick="toggleStatus()">
                        <?= $partner['status'] === 'online' ? 'Go Offline' : 'Go Online' ?>
                    </button>
                </div>
                
                <div class="header-actions">
                    <button class="header-action-btn">
                        <span>🔔</span>
                        <?php if ($activeDeliveries > 0): ?>
                        <span class="header-action-badge"><?= $activeDeliveries ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <div class="header-profile">
                        <div class="header-avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
                        <div class="header-profile-info">
                            <div class="header-profile-name"><?= htmlspecialchars($userName) ?></div>
                            <div class="header-profile-role">Delivery Partner</div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <div class="admin-content">
                <div class="content-header">
                    <h1 class="content-title">Delivery Dashboard</h1>
                    <p class="content-subtitle">Track your deliveries and earnings</p>
                </div>
                
                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Deliveries</span>
                            <div class="kpi-icon">📦</div>
                        </div>
                        <div class="kpi-value"><?= number_format($totalDeliveries) ?></div>
                        <div class="kpi-trend">
                            <span><?= $todayDeliveries ?> today</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Earnings</span>
                            <div class="kpi-icon">💰</div>
                        </div>
                        <div class="kpi-value"><?= number_format($totalEarnings, 0) ?> Birr</div>
                        <div class="kpi-trend">
                            <span><?= number_format($todayEarnings, 0) ?> Birr today</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Active Deliveries</span>
                            <div class="kpi-icon">🚴</div>
                        </div>
                        <div class="kpi-value"><?= number_format($activeDeliveries) ?></div>
                        <div class="kpi-trend <?= $activeDeliveries > 0 ? 'positive' : '' ?>">
                            <span><?= $activeDeliveries > 0 ? 'In progress' : 'No active deliveries' ?></span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Rating</span>
                            <div class="kpi-icon">⭐</div>
                        </div>
                        <div class="kpi-value"><?= number_format($partner['rating'], 1) ?></div>
                        <div class="kpi-trend positive">
                            <span>Customer rating</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Vehicle</span>
                            <div class="kpi-icon">🏍️</div>
                        </div>
                        <div class="kpi-value" style="font-size: var(--text-xl);"><?= ucfirst($partner['vehicle_type']) ?></div>
                        <div class="kpi-trend">
                            <span><?= htmlspecialchars($partner['vehicle_number']) ?></span>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Available Orders</span>
                            <div class="kpi-icon">📋</div>
                        </div>
                        <div class="kpi-value"><?= count($availableOrders) ?></div>
                        <div class="kpi-trend">
                            <span>Ready to accept</span>
                        </div>
                    </div>
                </div>
                
                <!-- Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h2 class="chart-title">Earnings Trend</h2>
                        <div class="chart-actions">
                            <button class="chart-filter-btn active">7 Days</button>
                            <button class="chart-filter-btn">30 Days</button>
                        </div>
                    </div>
                    <canvas id="earningsChart" class="chart-canvas"></canvas>
                </div>
                
                <!-- Active Deliveries -->
                <?php if (!empty($myDeliveries)): ?>
                <div class="data-table-container">
                    <div class="table-header">
                        <h2 class="table-title">🚴 Active Deliveries</h2>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Restaurant</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myDeliveries as $delivery): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($delivery['order_number']) ?></strong></td>
                                <td><?= htmlspecialchars($delivery['restaurant_name']) ?></td>
                                <td>
                                    <div><?= htmlspecialchars($delivery['customer_name']) ?></div>
                                    <small style="color: var(--gray-500);"><?= htmlspecialchars($delivery['customer_phone']) ?></small>
                                </td>
                                <td><strong><?= number_format($delivery['delivery_fee'], 2) ?> Birr</strong></td>
                                <td><span class="status-badge <?= $delivery['status'] ?>"><?= ucfirst(str_replace('_', ' ', $delivery['status'])) ?></span></td>
                                <td>
                                    <button class="action-btn action-btn-primary" onclick="updateDeliveryStatus(<?= $delivery['id'] ?>)">Update</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- Available Orders -->
                <?php if (!empty($availableOrders)): ?>
                <div class="data-table-container">
                    <div class="table-header">
                        <h2 class="table-title">📦 Available Orders</h2>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Restaurant</th>
                                <th>Distance</th>
                                <th>Delivery Fee</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($availableOrders as $order): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                <td><?= htmlspecialchars($order['restaurant_name']) ?></td>
                                <td><?= number_format($order['distance_km'], 1) ?> km</td>
                                <td><strong><?= number_format($order['delivery_fee'], 2) ?> Birr</strong></td>
                                <td>
                                    <button class="action-btn action-btn-success" onclick="acceptOrder(<?= $order['id'] ?>)">Accept</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- Recent Completed -->
                <?php if (!empty($completedDeliveries)): ?>
                <div class="data-table-container">
                    <div class="table-header">
                        <h2 class="table-title">✅ Recent Completed Deliveries</h2>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Restaurant</th>
                                <th>Customer</th>
                                <th>Earned</th>
                                <th>Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completedDeliveries as $delivery): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($delivery['order_number']) ?></strong></td>
                                <td><?= htmlspecialchars($delivery['restaurant_name']) ?></td>
                                <td><?= htmlspecialchars($delivery['customer_name']) ?></td>
                                <td><strong><?= number_format($delivery['delivery_fee'], 2) ?> Birr</strong></td>
                                <td><?= date('M d, g:i A', strtotime($delivery['delivered_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainContent').classList.toggle('expanded');
        });
        
        const ctx = document.getElementById('earningsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Earnings (Birr)',
                    data: [450, 520, 480, 610, 730, 820, 680],
                    backgroundColor: 'rgba(72, 196, 121, 0.8)',
                    borderColor: '#48C479',
                    borderWidth: 2
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
        
        function toggleStatus() {
            // Implement status toggle
            alert('Status toggle functionality - to be implemented');
        }
        
        function acceptOrder(orderId) {
            if (confirm('Accept this delivery order?')) {
                // Implement accept order
                alert('Accept order functionality - to be implemented');
            }
        }
        
        function updateDeliveryStatus(deliveryId) {
            // Implement status update
            alert('Update delivery status - to be implemented');
        }
    </script>
</body>
</html>
