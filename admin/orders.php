<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query('SELECT o.id, o.order_number, o.status, o.total_amount, o.created_at, u.name AS customer_name, r.name AS restaurant_name FROM orders o JOIN users u ON o.user_id = u.id JOIN restaurants r ON o.restaurant_id = r.id ORDER BY o.created_at DESC LIMIT 20');
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders · Gebeta Admin</title>
    <link rel="stylesheet" href="/assets/css/admin-layout.css">
    <link rel="stylesheet" href="/assets/css/admin-components.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
                    <a href="/admin/orders.php" class="nav-item active">
                        <span class="nav-item-icon">📦</span>
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
                    <input type="text" class="header-search-input" placeholder="Search orders..." id="globalSearch">
                </div>

                <div class="header-actions">
                    <button class="header-action-btn">
                        <span>🔔</span>
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
                    <h1 class="content-title">📦 Orders</h1>
                    <p class="content-subtitle">Manage and review recent orders</p>
                </div>

                <!-- Orders Table -->
                <div class="data-table-container">
                    <div class="table-header">
                        <h2 class="table-title">Recent Orders</h2>
                        <div class="table-search">
                            <span class="table-search-icon">🔍</span>
                            <input type="text" class="table-search-input" placeholder="Search in table..." id="orderSearch">
                        </div>
                        <div class="table-filters">
                            <button class="filter-btn active" data-status="all">All</button>
                            <button class="filter-btn" data-status="pending">Pending</button>
                            <button class="filter-btn" data-status="delivered">Delivered</button>
                            <button class="filter-btn" data-status="cancelled">Cancelled</button>
                        </div>
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
                        <tbody id="ordersTable">
                            <?php foreach ($orders as $order): ?>
                                <tr data-status="<?= htmlspecialchars($order['status']) ?>">
                                    <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($order['restaurant_name']) ?></td>
                                    <td><strong><?= number_format($order['total_amount'], 2) ?> Birr</strong></td>
                                    <td><span class="status-badge <?= htmlspecialchars($order['status']) ?>"><?= ucfirst(str_replace('_', ' ', htmlspecialchars($order['status']))) ?></span></td>
                                    <td><?= date('M d, Y g:i A', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <button class="action-btn action-btn-secondary" onclick="viewOrder(<?= (int)$order['id'] ?>)">View</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="table-pagination">
                        <div class="pagination-info">Showing <?= count($orders) ?> orders</div>
                        <div class="pagination-controls">
                            <button class="pagination-btn" disabled>←</button>
                            <button class="pagination-btn active">1</button>
                            <button class="pagination-btn" disabled>→</button>
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
                const rows = document.querySelectorAll('#ordersTable tr');

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
        const orderSearch = document.getElementById('orderSearch');
        orderSearch?.addEventListener('input', (e) => {
            const search = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#ordersTable tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        });

        // Header search (mirror to table search)
        document.getElementById('globalSearch')?.addEventListener('input', (e) => {
            if (!orderSearch) return;
            orderSearch.value = e.target.value;
            orderSearch.dispatchEvent(new Event('input'));
        });

        function viewOrder(id) {
            window.location.href = `/admin/orders.php?id=${id}`;
        }

        // Theme toggle (keeps consistency with dashboard)
        const themeRoot = document.getElementById('adminThemeRoot');
        const darkToggle = document.getElementById('darkModeToggle');
        const THEME_KEY = 'gebeta_admin_theme';

        function applyTheme(theme) {
            if (!themeRoot) return;
            const isNight = theme === 'night';
            themeRoot.classList.toggle('night', isNight);

            if (darkToggle) {
                darkToggle.querySelector('span')?.textContent = isNight ? '🌙' : '☀️';
            }
        }

        function initTheme() {
            if (!themeRoot) return;
            const saved = localStorage.getItem(THEME_KEY);
            if (saved === 'night' || saved === 'day') {
                applyTheme(saved);
                return;
            }
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            applyTheme(prefersDark ? 'night' : 'day');
        }

        darkToggle?.addEventListener('click', () => {
            if (!themeRoot) return;
            const currentNight = themeRoot.classList.contains('night');
            const next = currentNight ? 'day' : 'night';
            localStorage.setItem(THEME_KEY, next);
            applyTheme(next);
        });

        initTheme();
    </script>
</body>
</html>

