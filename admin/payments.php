<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

// Get payment stats
$todayRevenue = $pdo->query('SELECT SUM(amount) FROM payments WHERE DATE(created_at) = CURDATE() AND status = "completed"')->fetchColumn() ?: 0;
$weekRevenue = $pdo->query('SELECT SUM(amount) FROM payments WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = "completed"')->fetchColumn() ?: 0;
$monthRevenue = $pdo->query('SELECT SUM(amount) FROM payments WHERE MONTH(created_at) = MONTH(CURDATE()) AND status = "completed"')->fetchColumn() ?: 0;
$pendingCount = $pdo->query('SELECT COUNT(*) FROM payments WHERE status = "pending"')->fetchColumn();

// Get all payments
$stmt = $pdo->query('
    SELECT p.*, o.order_number, u.name as customer_name
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 50
');
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending bank transfers
$pendingTransfers = $pdo->query('
    SELECT p.*, o.order_number, u.name as customer_name
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.user_id = u.id
    WHERE p.status = "pending" AND p.payment_method = "bank_transfer"
    ORDER BY p.created_at DESC
')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments · Gebeta Admin</title>
    <link rel="stylesheet" href="/assets/css/admin-layout.css">
    <link rel="stylesheet" href="/assets/css/admin-components.css">
</head>
<body>
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
                    <a href="/admin/analytics.php" class="nav-item">
                        <span class="nav-item-icon">📈</span>
                        <span>Analytics</span>
                    </a>
                    <a href="/admin/reports.php" class="nav-item">
                        <span class="nav-item-icon">📋</span>
                        <span>Reports</span>
                    </a>
                    <a href="/admin/payments.php" class="nav-item active">
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
                    <a href="/logout.php" class="nav-item" style="color: var(--red-600);">
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
                    <input type="text" class="header-search-input" placeholder="Search payments...">
                </div>
                
                <div class="header-actions">
                    <button class="header-action-btn">
                        <span>🔔</span>
                        <?php if ($pendingCount > 0): ?>
                        <span class="header-action-badge"><?= $pendingCount ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="header-action-btn">
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
                    <h1 class="content-title">💰 Payments & Transactions</h1>
                    <p class="content-subtitle">Manage platform payments and payouts</p>
                </div>
                
                <!-- Revenue Summary -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Today</span>
                            <div class="kpi-icon">📅</div>
                        </div>
                        <div class="kpi-value"><?= number_format($todayRevenue, 0) ?> Birr</div>
                        <div class="kpi-trend">Today's revenue</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">This Week</span>
                            <div class="kpi-icon">📊</div>
                        </div>
                        <div class="kpi-value"><?= number_format($weekRevenue, 0) ?> Birr</div>
                        <div class="kpi-trend positive">Last 7 days</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">This Month</span>
                            <div class="kpi-icon">💰</div>
                        </div>
                        <div class="kpi-value"><?= number_format($monthRevenue, 0) ?> Birr</div>
                        <div class="kpi-trend positive">Current month</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Pending</span>
                            <div class="kpi-icon">⏳</div>
                        </div>
                        <div class="kpi-value"><?= $pendingCount ?></div>
                        <div class="kpi-trend">Awaiting approval</div>
                    </div>
                </div>
                
                <!-- Pending Approvals -->
                <?php if (count($pendingTransfers) > 0): ?>
                <div class="data-table-container" style="margin-bottom: 24px;">
                    <div class="table-header">
                        <h2 class="table-title">⏳ Pending Bank Transfer Approvals</h2>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingTransfers as $transfer): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($transfer['order_number']) ?></strong></td>
                                <td><?= htmlspecialchars($transfer['customer_name']) ?></td>
                                <td><strong><?= number_format($transfer['amount'], 2) ?> Birr</strong></td>
                                <td><?= date('M d, Y g:i A', strtotime($transfer['created_at'])) ?></td>
                                <td>
                                    <button class="action-btn action-btn-success" onclick="approvePayment(<?= $transfer['id'] ?>)">✓ Approve</button>
                                    <button class="action-btn action-btn-danger" onclick="rejectPayment(<?= $transfer['id'] ?>)">✗ Reject</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- All Transactions -->
                <div class="data-table-container">
                    <div class="table-header">
                        <h2 class="table-title">All Transactions</h2>
                        <div class="table-search">
                            <span class="table-search-icon">🔍</span>
                            <input type="text" class="table-search-input" placeholder="Search transactions..." id="paymentSearch">
                        </div>
                        <div class="table-filters">
                            <button class="filter-btn active" data-status="all">All</button>
                            <button class="filter-btn" data-status="completed">Completed</button>
                            <button class="filter-btn" data-status="pending">Pending</button>
                            <button class="filter-btn" data-status="failed">Failed</button>
                        </div>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="paymentsTable">
                            <?php foreach ($payments as $payment): ?>
                            <tr data-status="<?= htmlspecialchars($payment['status']) ?>">
                                <td><strong><?= htmlspecialchars($payment['order_number']) ?></strong></td>
                                <td><?= htmlspecialchars($payment['customer_name']) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></td>
                                <td><strong><?= number_format($payment['amount'], 2) ?> Birr</strong></td>
                                <td><span class="status-badge <?= $payment['status'] ?>"><?= ucfirst($payment['status']) ?></span></td>
                                <td><?= date('M d, Y g:i A', strtotime($payment['created_at'])) ?></td>
                                <td>
                                    <button class="action-btn action-btn-secondary" onclick="viewPayment(<?= $payment['id'] ?>)">View</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="table-pagination">
                        <div class="pagination-info">Showing <?= count($payments) ?> transactions</div>
                        <div class="pagination-controls">
                            <button class="pagination-btn" disabled>←</button>
                            <button class="pagination-btn active">1</button>
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
        
        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const status = btn.dataset.status;
                const rows = document.querySelectorAll('#paymentsTable tr');
                
                rows.forEach(row => {
                    if (status === 'all' || row.dataset.status === status) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
        
        // Search functionality
        document.getElementById('paymentSearch').addEventListener('input', (e) => {
            const search = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#paymentsTable tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        });
        
        function approvePayment(id) {
            if (confirm('Approve this payment?')) {
                alert('Payment approved: ' + id);
                location.reload();
            }
        }
        
        function rejectPayment(id) {
            if (confirm('Reject this payment?')) {
                alert('Payment rejected: ' + id);
                location.reload();
            }
        }
        
        function viewPayment(id) {
            alert('View payment details: ' + id);
        }
    </script>
</body>
</html>
