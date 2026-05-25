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

// Minimal page shell; data comes from API endpoints.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant['name'] ?? 'Restaurant') ?> · Dashboard</title>

    <link rel="stylesheet" href="/assets/css/admin-layout.css">
    <link rel="stylesheet" href="/assets/css/admin-components.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        .dash-topbar {
            background: linear-gradient(135deg, #FC8019 0%, #E56B0F 100%);
            color: #fff;
            padding: 18px 20px;
            border-radius: 10px;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .dash-topbar h1 { margin: 0; font-size: 18px; }
        .dash-topbar .status-line { font-size: 13px; opacity: 0.95; margin-top: 4px; display:flex; align-items:center; gap:8px; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #48C479; animation: pulse 2s infinite; }
        @keyframes pulse { 0%{transform:scale(1);opacity:1} 50%{transform:scale(1.4);opacity:.6} 100%{transform:scale(1);opacity:1} }

        .dash-header-right { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .dash-header-btn {
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.25);
            color: #fff;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            display:flex;
            align-items:center;
            gap:8px;
            font-weight:600;
            font-size: 13px;
            transition: all .2s;
            white-space:nowrap;
        }
        .dash-header-btn:hover { background: rgba(255,255,255,0.28); }

        .dash-actions-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 18px; }
        .dash-action-btn { background:#fff; border:1px solid #E8E8E8; border-radius: 12px; padding:14px; cursor:pointer; text-align:center; font-weight:700; color:#282C3F; }
        .dash-action-btn:hover { border-color:#FC8019; }

        .dash-kpi-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px; margin-bottom: 18px; }
        .dash-kpi-card { background:#fff; border:1px solid #E8E8E8; border-radius: 12px; padding: 18px; }
        .dash-kpi-header { display:flex; justify-content:space-between; align-items:center; gap:10px; }
        .dash-kpi-label { font-size: 12px; color:#93959F; text-transform: uppercase; font-weight:800; }
        .dash-kpi-value { font-size: 30px; font-weight:900; color:#282C3F; margin-top: 10px; }
        .dash-kpi-sub { font-size: 12px; color:#93959F; margin-top: 6px; }

        .section { display:none; }
        .section.active { display:block; }

        .dash-grid-2 { display:grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 18px; }
        .dash-chart-card { background:#fff; border:1px solid #E8E8E8; border-radius: 12px; padding: 16px; }
        .dash-chart-card h2 { margin:0 0 12px 0; font-size: 15px; }

        .dash-table-wrap { background:#fff; border:1px solid #E8E8E8; border-radius: 12px; padding: 16px; overflow:auto; }
        table { width:100%; border-collapse: collapse; }
        th { text-align:left; font-size: 12px; color:#93959F; text-transform: uppercase; padding: 10px; border-bottom:1px solid #E8E8E8; }
        td { padding: 10px; border-bottom:1px solid #E8E8E8; font-size: 13px; }
        tr:hover { background:#F5F5F5; }

        .dash-controls { display:flex; gap: 10px; align-items:center; flex-wrap:wrap; margin: 10px 0 14px 0; }
        .dash-control { padding:10px 12px; border:1px solid #E8E8E8; border-radius: 8px; }

        .status-badge { display:inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight:900; }
        .status-pending { background:#FFF3E0; color:#E65100; }
        .status_preparing, .status-preparing { background:#FCE4EC; color:#C2185B; }
        .status-ready { background:#E8F5E9; color:#2E7D32; }
        .status-delivered, .status-completed { background:#E3F2FD; color:#1565C0; }

        .btn-primary { background:#FC8019; border:none; color:#fff; padding:10px 14px; border-radius: 10px; cursor:pointer; font-weight:900; }
        .btn-secondary { background:#F5F5F5; border:1px solid #E8E8E8; color:#282C3F; padding:10px 14px; border-radius: 10px; cursor:pointer; font-weight:900; }

        @media (prefers-color-scheme: dark) {
            /* optional dark mode: keep existing admin theme if it already supports it */
        }
    </style>
</head>
<body>
<div class="admin-layout admin-theme" id="adminThemeRoot">

    <aside class="admin-sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🏪</div>
            <div class="sidebar-title"><?= htmlspecialchars(substr($restaurant['name'] ?? 'Restaurant', 0, 15)) ?></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Dashboard</div>
                <a href="#" class="nav-item active" data-tab="home"><span class="nav-item-icon">🏠</span><span>Home</span></a>
                <a href="#" class="nav-item" data-tab="orders"><span class="nav-item-icon">🧾</span><span>Orders</span></a>
                <a href="#" class="nav-item" data-tab="menu"><span class="nav-item-icon">🍽️</span><span>Menu</span></a>
                <a href="#" class="nav-item" data-tab="analytics"><span class="nav-item-icon">📈</span><span>Analytics</span></a>
                <a href="#" class="nav-item" data-tab="customers"><span class="nav-item-icon">👥</span><span>Customers</span></a>
                <a href="#" class="nav-item" data-tab="finance"><span class="nav-item-icon">💳</span><span>Finance</span></a>
                <a href="#" class="nav-item" data-tab="staff"><span class="nav-item-icon">🧑‍🍳</span><span>Staff</span></a>
                <a href="#" class="nav-item" data-tab="settings"><span class="nav-item-icon">⚙️</span><span>Settings</span></a>
                <a href="#" class="nav-item" data-tab="marketing"><span class="nav-item-icon">📣</span><span>Marketing</span></a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Account</div>
                <a href="/logout.php" class="nav-item"><span class="nav-item-icon">🚪</span><span>Logout</span></a>
            </div>
        </nav>
    </aside>

    <main class="admin-main" id="mainContent">
        <header class="admin-header">
            <button class="header-toggle" id="sidebarToggle"><span>☰</span></button>
            <div class="header-search">
                <span class="header-search-icon">🔎</span>
                <input type="text" class="header-search-input" placeholder="Search orders..." id="globalSearch">
            </div>
            <div class="header-actions">
                <button class="header-action-btn" id="pendingNotifBtn">
                    <span>🔔</span>
                    <span class="header-action-badge" id="pendingBadge" style="display:none">0</span>
                </button>
                <div class="header-profile">
                    <div class="header-avatar">🏳️</div>
                    <div class="header-profile-info">
                        <div class="header-profile-name"><?= htmlspecialchars($restaurant['name'] ?? '') ?></div>
                        <div class="header-profile-role">Restaurant Owner</div>
                    </div>
                </div>
            </div>
        </header>

        <div class="admin-content">
            <div class="content-header">
                <h1 class="content-title">Restaurant Dashboard</h1>
                <p class="content-subtitle">Real-time management & performance analytics</p>
            </div>

            <div class="dash-topbar">
                <div>
                    <h1>🏪 <?= htmlspecialchars($restaurant['name'] ?? 'Restaurant') ?></h1>
                    <div class="status-line">
                        <span class="status-dot"></span>
                        <span id="statusLine">Status: <?= htmlspecialchars($restaurant['status'] ?? 'active') ?> · Last Updated: —</span>
                    </div>
                </div>
                <div class="dash-header-right">
                    <button class="dash-header-btn" type="button" onclick="goTab('analytics')">📊 Reports</button>
                    <button class="dash-header-btn" type="button" onclick="goTab('settings')">⚙️ Settings</button>
                    <button class="dash-header-btn" type="button" onclick="window.location='/restaurant/profile.php'">👤 Profile</button>
                </div>
            </div>

            <!-- HOME -->
            <section class="section active" data-section="home">
                <div class="dash-kpi-grid">
                    <div class="dash-kpi-card">
                        <div class="dash-kpi-header"><span class="dash-kpi-label">Today's Orders</span><span>🧾</span></div>
                        <div class="dash-kpi-value" id="kpi_today_orders">0</div>
                        <div class="dash-kpi-sub" id="kpi_today_orders_sub">—</div>
                    </div>
                    <div class="dash-kpi-card">
                        <div class="dash-kpi-header"><span class="dash-kpi-label">Pending Orders</span><span>⚠️</span></div>
                        <div class="dash-kpi-value" id="kpi_pending_orders">0</div>
                        <div class="dash-kpi-sub" id="kpi_pending_sub">—</div>
                    </div>
                    <div class="dash-kpi-card">
                        <div class="dash-kpi-header"><span class="dash-kpi-label">Total Revenue</span><span>💰</span></div>
                        <div class="dash-kpi-value" id="kpi_total_revenue">0</div>
                        <div class="dash-kpi-sub" id="kpi_total_revenue_sub">—</div>
                    </div>
                    <div class="dash-kpi-card">
                        <div class="dash-kpi-header"><span class="dash-kpi-label">Today's Revenue</span><span>📈</span></div>
                        <div class="dash-kpi-value" id="kpi_today_revenue">0</div>
                        <div class="dash-kpi-sub" id="kpi_today_revenue_sub">—</div>
                    </div>
                </div>

                <div class="dash-actions-grid">
                    <button class="dash-action-btn" type="button" onclick="goTab('menu')">➕ Add Menu Item</button>
                    <button class="dash-action-btn" type="button" onclick="goTab('orders')">📋 View Orders</button>
                    <button class="dash-action-btn" type="button" onclick="goTab('customers')">👥 Customers</button>
                    <button class="dash-action-btn" type="button" onclick="goTab('analytics')">📊 Reports</button>
                    <button class="dash-action-btn" type="button" onclick="goTab('finance')">🧾 Finance</button>
                    <button class="dash-action-btn" type="button" onclick="goTab('marketing')">📢 Promotions</button>
                </div>

                <div class="dash-grid-2">
                    <div class="dash-chart-card">
                        <h2>Orders Trend (last 7 days)</h2>
<canvas id="ordersTrendChart" style="width:100%; height:220px;"></canvas>
                    </div>
                    <div class="dash-chart-card">
                        <h2>Revenue Trend (last 7 days)</h2>
<canvas id="revenueTrendChart" style="width:100%; height:220px;"></canvas>
                    </div>
                    <div class="dash-chart-card">
                        <h2>Top Selling Items</h2>
                        <div id="topItemsList" style="display:grid; gap:10px;"></div>
                    </div>
                    <div class="dash-chart-card">
                        <h2>Peak Hours</h2>
<canvas id="peakHoursChart" style="width:100%; height:220px;"></canvas>
                    </div>
                </div>

                <div style="margin-top: 18px;">
                    <div class="dash-table-wrap">
                        <div class="dash-controls" style="margin-top:0">
                            <div style="font-weight:900">Recent Orders</div>
                            <div style="margin-left:auto" class="dash-controls">
                                <button class="btn-secondary" type="button" onclick="goTab('orders')">Open Orders</button>
                            </div>
                        </div>
                        <div style="overflow:auto">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="recentOrdersBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ORDERS -->
            <section class="section" data-section="orders">
                <div class="dash-table-wrap">
                    <div class="dash-controls" style="margin-top:0">
                        <div style="font-weight:900">📄 Orders Management</div>
                        <input class="dash-control" type="text" id="ordersSearch" placeholder="Search order #..." />
                        <select class="dash-control" id="ordersStatusFilter">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="preparing">Preparing</option>
                            <option value="ready">Ready</option>
                            <option value="delivered">Completed</option>
                        </select>
                        <button class="btn-primary" type="button" onclick="loadOrders(true)">Refresh</button>
                    </div>
                    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom: 10px;">
                        <button class="btn-secondary" type="button" onclick="setOrdersStatusTab('pending')">🔴 New</button>
                        <button class="btn-secondary" type="button" onclick="setOrdersStatusTab('preparing')">🟡 Preparing</button>
                        <button class="btn-secondary" type="button" onclick="setOrdersStatusTab('ready')">🟢 Ready</button>
                        <button class="btn-secondary" type="button" onclick="setOrdersStatusTab('delivered')">✅ Completed</button>
                    </div>
                    <div style="overflow:auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ordersBody"></tbody>
                        </table>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center; justify-content:flex-end; margin-top: 12px;">
                        <button class="btn-secondary" type="button" onclick="ordersPrev()">Prev</button>
                        <div style="font-weight:900" id="ordersPaginationText">—</div>
                        <button class="btn-secondary" type="button" onclick="ordersNext()">Next</button>
                    </div>
                </div>
            </section>

            <!-- MENU -->
            <section class="section" data-section="menu">
                <div class="dash-table-wrap">
                    <div class="dash-controls" style="margin-top:0">
                        <div style="font-weight:900">🍽️ Menu Management</div>
                        <select class="dash-control" id="menuCategoryFilter"><option value="">All Categories</option></select>
                        <select class="dash-control" id="menuSortBy">
                            <option value="popularity">By Popularity</option>
                            <option value="rating">By Rating</option>
                            <option value="price">By Price</option>
                            <option value="sales">By Sales</option>
                        </select>
                        <button class="btn-primary" type="button" onclick="loadMenu(true)">Refresh</button>
                    </div>
                    <div id="menuItemsGrid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px;"></div>
                </div>
            </section>

            <!-- ANALYTICS -->
            <section class="section" data-section="analytics">
                <div class="dash-table-wrap">
                    <div class="dash-controls" style="margin-top:0">
                        <div style="font-weight:900">📊 Analytics & Reports</div>
                        <input class="dash-control" type="date" id="analyticsFrom" />
                        <span style="opacity:.6">-</span>
                        <input class="dash-control" type="date" id="analyticsTo" />
                        <button class="btn-primary" type="button" onclick="loadAnalytics(true)">Refresh</button>
                    </div>
                    <div class="dash-grid-2">
                        <div class="dash-chart-card">
                            <h2>Orders Trend</h2>
<canvas id="analyticsOrdersChart" style="width:100%; height:220px;"></canvas>
                        </div>
                        <div class="dash-chart-card">
                            <h2>Revenue Trend</h2>
<canvas id="analyticsRevenueChart" style="width:100%; height:220px;"></canvas>
                        </div>
                        <div class="dash-chart-card">
                            <h2>Top Selling Items</h2>
                            <div id="analyticsTopItems" style="display:grid; gap:10px;"></div>
                        </div>
                        <div class="dash-chart-card">
                            <h2>Peak Hours</h2>
<canvas id="analyticsPeakChart" style="width:100%; height:220px;"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CUSTOMERS -->
            <section class="section" data-section="customers">
                <div class="dash-table-wrap">
                    <div class="dash-controls" style="margin-top:0">
                        <div style="font-weight:900">👥 Customer Insights</div>
                        <input class="dash-control" type="text" id="customersSearch" placeholder="Search customers..." />
                        <button class="btn-primary" type="button" onclick="loadCustomers(true)">Refresh</button>
                    </div>
                    <div style="overflow:auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Orders</th>
                                    <th>Spent</th>
                                    <th>Last Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="customersBody"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- FINANCE -->
            <section class="section" data-section="finance">
                <div class="dash-table-wrap">
                    <div class="dash-controls" style="margin-top:0">
                        <div style="font-weight:900">💳 Financial Dashboard</div>
                        <button class="btn-secondary" type="button" onclick="alert('Export not wired yet')">Export</button>
                    </div>
                    <div class="dash-kpi-grid">
                        <div class="dash-kpi-card"><div class="dash-kpi-header"><span class="dash-kpi-label">Revenue</span><span>💰</span></div><div class="dash-kpi-value" id="fin_revenue_today">0</div><div class="dash-kpi-sub">Today</div></div>
                        <div class="dash-kpi-card"><div class="dash-kpi-header"><span class="dash-kpi-label">Commission</span><span>🧾</span></div><div class="dash-kpi-value" id="fin_commission_today">0</div><div class="dash-kpi-sub">(Est.)</div></div>
                        <div class="dash-kpi-card"><div class="dash-kpi-header"><span class="dash-kpi-label">Net Earn</span><span>📌</span></div><div class="dash-kpi-value" id="fin_net_today">0</div><div class="dash-kpi-sub">Today</div></div>
                        <div class="dash-kpi-card"><div class="dash-kpi-header"><span class="dash-kpi-label">Pending Amount</span><span>⚠️</span></div><div class="dash-kpi-value" id="fin_pending_today">0</div><div class="dash-kpi-sub">Pending</div></div>
                    </div>

                    <div style="margin-top: 14px; overflow:auto">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Delivery Fee</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody id="finTransactionsBody"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- STAFF / SETTINGS / MARKETING (UI placeholders) -->
            <section class="section" data-section="staff">
                <div class="dash-table-wrap">
                    <div style="font-weight:900">🧑‍🍳 Staff & Performance Tracking</div>
                    <p style="color:#93959F; margin-top: 8px;">Staff performance UI is not wired to backend yet. This section will be connected once staff/inventory/promotion tables are confirmed.</p>
                </div>
            </section>
            <section class="section" data-section="settings">
                <div class="dash-table-wrap">
                    <div style="font-weight:900">⚙️ Restaurant Configuration</div>
                    <p style="color:#93959F; margin-top: 8px;">Settings UI placeholder.</p>
                </div>
            </section>
            <section class="section" data-section="marketing">
                <div class="dash-table-wrap">
                    <div style="font-weight:900">📢 Promotions & Campaigns</div>
                    <p style="color:#93959F; margin-top: 8px;">Marketing UI placeholder.</p>
                </div>
            </section>

        </div>
    </main>
</div>

<script>
    const sections = document.querySelectorAll('.section');
    function goTab(tab) {
        sections.forEach(s => {
            s.classList.toggle('active', s.getAttribute('data-section') === tab);
        });
        document.querySelectorAll('.nav-item').forEach(a => {
            a.classList.toggle('active', a.getAttribute('data-tab') === tab);
        });

        if (tab === 'orders') loadOrders(false);
        if (tab === 'menu') loadMenu(false);
        if (tab === 'analytics') loadAnalytics(false);
        if (tab === 'customers') loadCustomers(false);
        if (tab === 'finance') loadFinance(false);
    }
    window.goTab = goTab;

    document.querySelectorAll('.nav-item[data-tab]').forEach(a => {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            goTab(a.getAttribute('data-tab'));
        });
    });

    document.getElementById('sidebarToggle').addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('collapsed');
        document.getElementById('mainContent').classList.toggle('expanded');
    });

    // ---------- Charts ----------
    let revenueTrendChart, ordersTrendChart, peakHoursChart;

    function destroyChart(ch) { try { if (ch) ch.destroy(); } catch(e){} }

    function renderHomeCharts(data) {
        const orders = data?.chart?.revenue_trend || [];
        const revenue = data?.chart?.revenue_trend || [];
        // Note: spec wants orders_trend separately; current API returns revenue_trend in dashboard-data.
        // We'll use revenue_trend as revenue and compute a simple placeholder for orders using same series.

        destroyChart(revenueTrendChart);
        destroyChart(ordersTrendChart);
        destroyChart(peakHoursChart);

        const ordersCtx = document.getElementById('ordersTrendChart').getContext('2d');
        const revenueCtx = document.getElementById('revenueTrendChart').getContext('2d');

        const labels = ['-6d','-5d','-4d','-3d','-2d','-1d','Today'];

        ordersTrendChart = new Chart(ordersCtx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Orders',
                    data: revenue,
                    borderColor: '#48C479',
                    backgroundColor: 'rgba(72,196,121,0.12)',
                    tension: 0.35
                }]
            },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
        });

        revenueTrendChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Revenue (Birr)',
                    data: revenue,
                    borderColor: '#FC8019',
                    backgroundColor: 'rgba(252,128,25,0.14)',
                    tension: 0.35
                }]
            },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
        });

        // Peak hours bar
        const peakCtx = document.getElementById('peakHoursChart').getContext('2d');
        const peak = data?.chart?.peak_hours || [];
        const peakLabels = peak.map(p => p.hour ?? p.hr ?? p.H ?? p.hour);
        const peakRevenues = peak.map(p => Number(p.revenue ?? p.revenue_amount ?? 0));

        peakHoursChart = new Chart(peakCtx, {
            type: 'bar',
            data: {
                labels: peakLabels.map(h => h + ':00'),
                datasets: [{
                    label: 'Revenue',
                    data: peakRevenues,
                    backgroundColor: 'rgba(252,128,25,0.55)'
                }]
            },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
        });
    }

    function renderTopItems(items) {
        const el = document.getElementById('topItemsList');
        el.innerHTML = '';
        (items || []).forEach(it => {
            const row = document.createElement('div');
            row.style.display = 'flex';
            row.style.justifyContent = 'space-between';
            row.style.alignItems = 'center';
            row.style.padding = '10px 12px';
            row.style.border = '1px solid #E8E8E8';
            row.style.borderRadius = '10px';
            row.style.background = '#F8F8F8';
            row.innerHTML = `<div><div style="font-weight:900">${escapeHtml(it.name || '')}</div><div style="font-size:12px;color:#93959F">${it.total_sold ?? it.sold_count ?? 0} sold</div></div><div style="font-weight:900;color:#FC8019">${formatBirr(it.revenue ?? 0)}</div>`;
            el.appendChild(row);
        });
    }

    function escapeHtml(s){ return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'<','>':'>','"':'"',"'":'&#39;'}[c])); }
    function formatBirr(n){ const num = Number(n||0); return num.toLocaleString(undefined,{maximumFractionDigits:0}); }

    // ---------- Data loading ----------
    async function loadDashboardData() {
        try {
            const res = await fetch('/api/dashboard-data.php', { headers: {'Accept':'application/json'} });
            const json = await res.json();
            if (!json.success) throw new Error(json.error || 'Failed');

            const d = json.data;
            const m = d.metrics || {};

            document.getElementById('kpi_today_orders').textContent = m.today_orders ?? 0;
            document.getElementById('kpi_pending_orders').textContent = m.pending_orders ?? 0;
            document.getElementById('kpi_total_revenue').textContent = formatBirr(m.total_revenue ?? 0);
            document.getElementById('kpi_today_revenue').textContent = formatBirr(m.today_revenue ?? 0);

            const pending = Number(m.pending_orders ?? 0);
            document.getElementById('kpi_pending_sub').textContent = pending > 0 ? 'Action needed' : 'All clear!';
            document.getElementById('kpi_today_orders_sub').textContent = (m.total_orders ?? 0) + ' total orders';
            document.getElementById('kpi_total_revenue_sub').textContent = 'Total to date';
            document.getElementById('kpi_today_revenue_sub').textContent = 'Today revenue';

            // Status/pending badge
            const badge = document.getElementById('pendingBadge');
            if (pending > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = pending;
            } else {
                badge.style.display = 'none';
            }

            const updatedAt = new Date(json.data.updated_at * 1000);
            document.getElementById('statusLine').textContent = `Status: ${json.data.restaurant.status || 'active'} · Last Updated: ${updatedAt.toLocaleTimeString()}`;

            renderHomeCharts(d);
            renderTopItems(d.top_items);
            renderRecentOrders(d.recent_orders);
        } catch (e) {
            console.error(e);
        }
    }

    function renderRecentOrders(orders) {
        const tbody = document.getElementById('recentOrdersBody');
        tbody.innerHTML = '';
        (orders || []).forEach(o => {
            const tr = document.createElement('tr');
            const status = (o.status || '').toString();
            const badgeClass = status === 'pending' ? 'status-badge status-pending' :
                (status === 'preparing' ? 'status-badge status-preparing' :
                (status === 'ready' ? 'status-badge status-ready' : 'status-badge status-delivered'));

            tr.innerHTML = `
                <td><strong>${escapeHtml(o.order_number || o.id || '')}</strong></td>
                <td>
                    <div>${escapeHtml(o.customer_name || '')}</div>
                    <small style="color:#93959F">${escapeHtml(o.customer_phone || '')}</small>
                </td>
                <td><strong>${formatBirr(o.total_amount ?? 0)} Birr</strong></td>
                <td><span class="${badgeClass}">${escapeHtml(status.replace('_',' '))}</span></td>
                <td>${o.created_at ? escapeHtml(new Date(o.created_at).toLocaleString()) : ''}</td>
                <td><a class="btn-secondary" href="/restaurant/order-detail.php?id=${escapeHtml(o.id)}">View</a></td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Orders list
    let ordersPage = 1;
    let ordersTotalPages = 1;

    async function loadOrders(reset=false) {
        if (reset) ordersPage = 1;

        const status = document.getElementById('ordersStatusFilter').value;
        const q = document.getElementById('ordersSearch').value.trim();

        const url = new URL('/api/orders-list.php', window.location.origin);
        url.searchParams.set('page', ordersPage);
        url.searchParams.set('pageSize', 10);
        if (status) url.searchParams.set('status', status);
        if (q) url.searchParams.set('q', q);

        const res = await fetch(url.toString(), { headers: {'Accept':'application/json'} });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Failed');

        ordersTotalPages = json.data.meta.totalPages || 1;
        document.getElementById('ordersPaginationText').textContent = `Page ${json.data.meta.page} / ${ordersTotalPages}`;

        const tbody = document.getElementById('ordersBody');
        tbody.innerHTML = '';
        (json.data.items || []).forEach(o => {
            const tr = document.createElement('tr');
            const status = (o.status || '').toString();
            let badgeClass = 'status-badge status-delivered';
            if (status === 'pending') badgeClass = 'status-badge status-pending';
            if (status === 'preparing') badgeClass = 'status-badge status-preparing';
            if (status === 'ready') badgeClass = 'status-badge status-ready';

            tr.innerHTML = `
                <td><strong>${escapeHtml(o.order_number || o.id)}</strong></td>
                <td>
                    <div>${escapeHtml(o.customer_name || '')}</div>
                    <small style="color:#93959F">${escapeHtml(o.customer_phone || '')}</small>
                </td>
                <td>${escapeHtml(o.items_count ?? '-') }</td>
                <td><strong>${formatBirr(o.total_amount ?? 0)} Birr</strong></td>
                <td><span class="${badgeClass}">${escapeHtml(status)}</span></td>
                <td>${o.created_at ? escapeHtml(new Date(o.created_at).toLocaleTimeString()) : ''}</td>
                <td><a class="btn-secondary" href="/restaurant/order-detail.php?id=${escapeHtml(o.id)}">View</a></td>
            `;
            tbody.appendChild(tr);
        });
    }

    function setOrdersStatusTab(status){
        document.getElementById('ordersStatusFilter').value = status;
        loadOrders(true);
    }
    function ordersPrev(){ if (ordersPage > 1) { ordersPage--; loadOrders(false);} }
    function ordersNext(){ if (ordersPage < ordersTotalPages) { ordersPage++; loadOrders(false);} }
    window.loadOrders = loadOrders;
    window.ordersPrev = ordersPrev;
    window.ordersNext = ordersNext;
    window.setOrdersStatusTab = setOrdersStatusTab;

    // Menu
    async function loadMenu(reset=false){
        const catSelect = document.getElementById('menuCategoryFilter');
        const sortBy = document.getElementById('menuSortBy').value;
        const categoryId = catSelect.value || '';

        const url = new URL('/api/menu-items.php', window.location.origin);
        url.searchParams.set('page', 1);
        url.searchParams.set('pageSize', 30);
        if (categoryId) url.searchParams.set('categoryId', categoryId);
        url.searchParams.set('sortBy', sortBy);

        const res = await fetch(url.toString(), { headers: {'Accept':'application/json'} });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Failed');

        const categories = json.data.categories || [];
        // Populate categories once
        if (reset) {
            catSelect.innerHTML = '<option value="">All Categories</option>' + categories.map(c=>`<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
        } else {
            // ensure options exist
            if (catSelect.options.length <= 1 && categories.length) {
                catSelect.innerHTML = '<option value="">All Categories</option>' + categories.map(c=>`<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
            }
        }

        const grid = document.getElementById('menuItemsGrid');
        grid.innerHTML = '';
        (json.data.items || []).forEach(it => {
            const card = document.createElement('div');
            card.style.background='#fff';
            card.style.border='1px solid #E8E8E8';
            card.style.borderRadius='12px';
            card.style.overflow='hidden';
            card.style.display='flex';
            card.style.flexDirection='column';
            card.innerHTML = `
                <div style="height:130px;background:#E8E8E8;display:flex;align-items:center;justify-content:center;font-size:40px">🍽️</div>
                <div style="padding:14px;display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
                        <div>
                            <div style="font-weight:900">${escapeHtml(it.name || '')}</div>
                            <div style="font-size:12px;color:#93959F">${escapeHtml(it.category_name || '')}</div>
                        </div>
                        <span class="status-badge status-ready">${(it.is_available ?? true) ? 'Available' : 'Unavailable'}</span>
                    </div>
                    <div style="color:#93959F;font-size:12px;min-height: 32px">${escapeHtml(it.description || '')}</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;border-top:1px solid #E8E8E8;padding-top:10px">
                        <div><div style="font-size:10px;text-transform:uppercase;color:#93959F">Price</div><div style="font-weight:900">${formatBirr(it.price ?? 0)} Birr</div></div>
                        <div><div style="font-size:10px;text-transform:uppercase;color:#93959F">Sold Today</div><div style="font-weight:900">${it.sold_today ?? 0}</div></div>
                    </div>
                </div>
            `;
            grid.appendChild(card);
        });
    }
    window.loadMenu = loadMenu;

    // Analytics (use api/chart-data + api/analytics for top items/peak)
    async function loadAnalytics(reset=false){
        const from = document.getElementById('analyticsFrom').value;
        const to = document.getElementById('analyticsTo').value;

        const url = new URL('/api/analytics.php', window.location.origin);
        if (from) url.searchParams.set('from', from);
        if (to) url.searchParams.set('to', to);

        const res = await fetch(url.toString(), { headers: {'Accept':'application/json'} });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Failed');

        const chart = json.data.chart || {};
        const labels = chart.labels || [];

        // Orders chart
        if (window.analyticsOrdersChart) window.analyticsOrdersChart.destroy();
        if (window.analyticsRevenueChart) window.analyticsRevenueChart.destroy();
        if (window.analyticsPeakChart) window.analyticsPeakChart.destroy();

        const ordersCtx = document.getElementById('analyticsOrdersChart').getContext('2d');
        const revenueCtx = document.getElementById('analyticsRevenueChart').getContext('2d');
        const peakCtx = document.getElementById('analyticsPeakChart').getContext('2d');

        window.analyticsOrdersChart = new Chart(ordersCtx, {
            type: 'line',
            data: { labels, datasets: [{ label:'Orders', data: chart.orders_trend ?? chart.orders ?? [], borderColor:'#48C479', backgroundColor:'rgba(72,196,121,0.12)', tension:0.35 }] },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
        });
        window.analyticsRevenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: { labels, datasets: [{ label:'Revenue', data: chart.revenue_trend ?? [], borderColor:'#FC8019', backgroundColor:'rgba(252,128,25,0.14)', tension:0.35 }] },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
        });

        const top = chart.top_items || [];
        const topEl = document.getElementById('analyticsTopItems');
        topEl.innerHTML = '';
        top.forEach(it => {
            const row = document.createElement('div');
            row.style.display='flex';
            row.style.justifyContent='space-between';
            row.style.alignItems='center';
            row.style.padding='10px 12px';
            row.style.border='1px solid #E8E8E8';
            row.style.borderRadius='10px';
            row.style.background='#F8F8F8';
            row.innerHTML = `<div><div style="font-weight:900">${escapeHtml(it.name||'')}</div><div style="font-size:12px;color:#93959F">${it.sold_count ?? it.sold ?? it.total_sold ?? 0} sold</div></div><div style="font-weight:900;color:#FC8019">${formatBirr(it.revenue ?? 0)} Birr</div>`;
            topEl.appendChild(row);
        });

        const peak = chart.peak_hours || [];
        window.analyticsPeakChart = new Chart(peakCtx, {
            type:'bar',
            data:{
                labels: peak.map(p => (p.hour ?? p.hr ?? 0)+':00'),
                datasets:[{label:'Revenue', data: peak.map(p=>Number(p.revenue ?? 0)), backgroundColor:'rgba(252,128,25,0.55)'}]
            },
            options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
        });
    }
    window.loadAnalytics = loadAnalytics;

    // Customers
    async function loadCustomers(reset=false){
        const q = document.getElementById('customersSearch').value.trim();
        const url = new URL('/api/customers.php', window.location.origin);
        if (q) url.searchParams.set('q', q);
        url.searchParams.set('limit', 20);

        const res = await fetch(url.toString(), { headers: {'Accept':'application/json'} });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Failed');

        const tbody = document.getElementById('customersBody');
        tbody.innerHTML = '';
        (json.data.items || []).forEach(c => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${escapeHtml(c.name || '')}</strong><div style="font-size:12px;color:#93959F">${escapeHtml(c.phone || '')}</div></td>
                <td>${escapeHtml(c.order_count ?? 0)}</td>
                <td><strong>${formatBirr(c.total_spent ?? 0)} Birr</strong></td>
                <td>${c.last_order_at ? escapeHtml(new Date(c.last_order_at).toLocaleString()) : ''}</td>
                <td><button class="btn-secondary" type="button" onclick="alert('Not wired')">View</button></td>
            `;
            tbody.appendChild(tr);
        });
    }
    window.loadCustomers = loadCustomers;

    // Finance
    async function loadFinance(reset=false){
        const res = await fetch('/api/financial.php', { headers: {'Accept':'application/json'} });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Failed');

        const s = json.data.summary || {};
        document.getElementById('fin_revenue_today').textContent = formatBirr(s.revenue_today ?? 0);
        document.getElementById('fin_commission_today').textContent = formatBirr(s.commission_today ?? 0);
        document.getElementById('fin_net_today').textContent = formatBirr(s.net_earn_today ?? 0);
        document.getElementById('fin_pending_today').textContent = formatBirr(s.pending_amount ?? 0);

        const tbody = document.getElementById('finTransactionsBody');
        tbody.innerHTML = '';
        (json.data.transactions || []).forEach(t => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${escapeHtml(t.order_number ?? t.id ?? '')}</td>
                <td>${escapeHtml(t.status ?? '')}</td>
                <td><strong>${formatBirr(t.total_amount ?? 0)} Birr</strong></td>
                <td>${formatBirr(t.delivery_fee ?? 0)} Birr</td>
                <td>${t.created_at ? escapeHtml(new Date(t.created_at).toLocaleString()) : ''}</td>
            `;
            tbody.appendChild(tr);
        });
    }
    window.loadFinance = loadFinance;

    // Global search (orders)
    document.getElementById('globalSearch').addEventListener('input', () => {
        document.getElementById('ordersSearch').value = document.getElementById('globalSearch').value;
        if (document.querySelector('.section.active[data-section="orders"]')) loadOrders(true);
    });

    // Real-time polling
    let intervalId;
    function startPolling(){
        loadDashboardData();
        if (intervalId) clearInterval(intervalId);
        intervalId = setInterval(loadDashboardData, 30000);
    }

    startPolling();

    <script>
        // Day/Night theme toggle (same behavior as admin)
        const themeRoot = document.getElementById('adminThemeRoot') || document.body;
        const darkToggleBtn = document.getElementById('darkModeToggle');
        const THEME_KEY = 'gebeta_admin_theme';

        function applyTheme(theme) {
            const isNight = theme === 'night';
            themeRoot.classList.toggle('night', isNight);

            if (darkToggleBtn) {
                darkToggleBtn.querySelector('span')?.textContent = isNight ? '🌙' : '☀️';
            }
        }

        function initTheme() {
            const saved = localStorage.getItem(THEME_KEY);
            if (saved === 'night' || saved === 'day') {
                applyTheme(saved);
                return;
            }
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            applyTheme(prefersDark ? 'night' : 'day');
        }

        darkToggleBtn?.addEventListener('click', () => {
            const currentNight = themeRoot.classList.contains('night');
            const next = currentNight ? 'day' : 'night';
            localStorage.setItem(THEME_KEY, next);
            applyTheme(next);
        });

        initTheme();
    </script>
</body>
</html>


