<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

// Get delivery partners with user info
$stmt = $pdo->query('
    SELECT dp.*, u.name, u.email, u.phone, u.status as user_status
    FROM delivery_partners dp
    JOIN users u ON dp.user_id = u.id
    ORDER BY dp.created_at DESC
');
$partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$totalPartners = count($partners);
$activePartners = count(array_filter($partners, fn($p) => $p['status'] === 'online'));
$onDelivery = count(array_filter($partners, fn($p) => $p['status'] === 'on_delivery'));
$avgRating = $totalPartners > 0 ? array_sum(array_column($partners, 'rating')) / $totalPartners : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Partners · Gebeta Admin</title>
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
                    <a href="/admin/delivery-partners.php" class="nav-item active">
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
                    <a href="/admin/payments.php" class="nav-item">
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
                    <input type="text" class="header-search-input" placeholder="Search delivery partners..." id="globalSearch">
                </div>
                
                <div class="header-actions">
                    <button class="header-action-btn">
                        <span>🔔</span>
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
                    <h1 class="content-title">🚚 Delivery Partners</h1>
                    <p class="content-subtitle">Manage delivery drivers and their performance</p>
                </div>
                
                <!-- Stats Grid -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Total Partners</span>
                            <div class="kpi-icon">🚚</div>
                        </div>
                        <div class="kpi-value"><?= $totalPartners ?></div>
                        <div class="kpi-trend">All registered drivers</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Online Now</span>
                            <div class="kpi-icon">🟢</div>
                        </div>
                        <div class="kpi-value"><?= $activePartners ?></div>
                        <div class="kpi-trend positive">Available for delivery</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">On Delivery</span>
                            <div class="kpi-icon">📦</div>
                        </div>
                        <div class="kpi-value"><?= $onDelivery ?></div>
                        <div class="kpi-trend">Currently delivering</div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <span class="kpi-label">Avg Rating</span>
                            <div class="kpi-icon">⭐</div>
                        </div>
                        <div class="kpi-value"><?= number_format($avgRating, 1) ?></div>
                        <div class="kpi-trend positive">Platform average</div>
                    </div>
                </div>
                
                <!-- Partners Table -->
                <div class="data-table-container">
                    <div class="table-header">
                        <h2 class="table-title">All Delivery Partners</h2>
                        <div class="table-search">
                            <span class="table-search-icon">🔍</span>
                            <input type="text" class="table-search-input" placeholder="Search partners..." id="partnerSearch">
                        </div>
                        <div class="table-filters">
                            <button class="filter-btn active" data-status="all">All</button>
                            <button class="filter-btn" data-status="online">Online</button>
                            <button class="filter-btn" data-status="offline">Offline</button>
                            <button class="filter-btn" data-status="on_delivery">On Delivery</button>
                        </div>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Partner</th>
                                <th class="sortable">Vehicle</th>
                                <th class="sortable">Rating</th>
                                <th class="sortable">Deliveries</th>
                                <th class="sortable">Earnings</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="partnersTable">
                            <?php foreach ($partners as $partner): ?>
                            <tr data-status="<?= htmlspecialchars($partner['status']) ?>">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div class="header-avatar" style="width: 40px; height: 40px;">
                                            <?= strtoupper(substr($partner['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;"><?= htmlspecialchars($partner['name']) ?></div>
                                            <div style="font-size: 12px; color: var(--gray-500);"><?= htmlspecialchars($partner['phone']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 500;"><?= ucfirst($partner['vehicle_type']) ?></div>
                                    <div style="font-size: 12px; color: var(--gray-500);"><?= htmlspecialchars($partner['vehicle_number'] ?? 'N/A') ?></div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <span>⭐</span>
                                        <strong><?= number_format($partner['rating'], 1) ?></strong>
                                    </div>
                                </td>
                                <td><strong><?= number_format($partner['total_deliveries']) ?></strong></td>
                                <td><strong><?= number_format($partner['total_earnings'], 2) ?> Birr</strong></td>
                                <td>
                                    <?php
                                    $statusClass = $partner['status'];
                                    $statusText = str_replace('_', ' ', ucfirst($partner['status']));
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                </td>
                                <td>
                                    <button class="action-btn action-btn-secondary" onclick="viewPartner(<?= $partner['id'] ?>)">View</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="table-pagination">
                        <div class="pagination-info">Showing <?= count($partners) ?> partners</div>
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
                const rows = document.querySelectorAll('#partnersTable tr');
                
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
        document.getElementById('partnerSearch').addEventListener('input', (e) => {
            const search = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#partnersTable tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        });
        
        function viewPartner(id) {
            alert('View partner details: ' + id);
        }
    </script>
</body>
</html>
