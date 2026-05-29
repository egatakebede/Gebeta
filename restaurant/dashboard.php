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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#FC8019">
    <title><?= htmlspecialchars($restaurant['name'] ?? 'Restaurant') ?> · Dashboard</title>

    <link rel="stylesheet" href="/assets/css/admin-layout.css">
    <link rel="stylesheet" href="/assets/css/admin-components.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    
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
        .dash-action-btn { background:#fff; border:1px solid #E8E8E8; border-radius: 12px; padding:14px; cursor:pointer; text-align:center; font-weight:700; color:#282C3F; transition: border-color .2s; }
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
        .dash-control { padding:10px 12px; border:1px solid #E8E8E8; border-radius: 8px; font-size:13px; }

        .status-badge { display:inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight:900; }
        .status-pending  { background:#FFF3E0; color:#E65100; }
        .status-preparing { background:#FCE4EC; color:#C2185B; }
        .status-ready    { background:#E8F5E9; color:#2E7D32; }
        .status-delivered,
        .status-completed { background:#E3F2FD; color:#1565C0; }

        .btn-primary   { background:#FC8019; border:none; color:#fff; padding:10px 14px; border-radius:10px; cursor:pointer; font-weight:900; }
        .btn-secondary { background:#F5F5F5; border:1px solid #E8E8E8; color:#282C3F; padding:10px 14px; border-radius:10px; cursor:pointer; font-weight:900; }

        /* Refined Menu Styles */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }
        .menu-card {
            background: #fff;
            border: 1px solid #E8E8E8;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.08);
            border-color: #FC8019;
        }
        .menu-card-img {
            height: 140px;
            background: #F3F4F6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            position: relative;
        }
        .menu-card-body {
            padding: 16px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .menu-card-title { font-weight: 800; font-size: 16px; color: #282C3F; }
        .menu-card-cat { font-size: 11px; color: #FC8019; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .menu-card-desc { font-size: 13px; color: #686B78; line-height: 1.4; min-height: 36px; }
        .menu-card-footer {
            padding: 12px 16px;
            border-top: 1px solid #F3F4F6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #FAFAFA;
        }
        .menu-card-price { font-weight: 900; font-size: 16px; color: #282C3F; }
        .menu-card-sold { font-size: 11px; color: #93959F; margin-top: 2px; font-weight: 600; }
        
        .admin-theme.night .menu-card { background: #2d2d2d; border-color: #404040; }
        .admin-theme.night .menu-card-title { color: #fff; }
        .admin-theme.night .menu-card-footer { background: #353535; border-top-color: #404040; }
        .admin-theme.night .menu-card-price { color: #fff; }
        .admin-theme.night .menu-card-img { background: #3d3d3d; }
        .admin-theme.night .menu-card-desc { color: #b0b0b0; }

        #darkModeToggle {
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.25);
            color: #fff;
            width: 36px; height: 36px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            display:flex; align-items:center; justify-content:center;
        }
        
        .admin-theme.night {
            background: #1a1a1a;
        }
        .admin-theme.night .admin-main,
        .admin-theme.night .dash-kpi-card,
        .admin-theme.night .dash-chart-card,
        .admin-theme.night .dash-table-wrap,
        .admin-theme.night .dash-action-btn {
            background: #2d2d2d;
            border-color: #404040;
            color: #e0e0e0;
        }
        .admin-theme.night .dash-kpi-value {
            color: #e0e0e0;
        }
        .admin-theme.night td {
            border-bottom-color: #404040;
        }
        
        .refresh-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.6s linear infinite;
            margin-right: 6px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .btn-refresh {
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.25);
            color: #fff;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 13px;
            transition: all .2s;
        }
        
        .btn-refresh:hover {
            background: rgba(255,255,255,0.28);
        }
        
        .btn-refresh:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Mobile sidebar fixes */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                z-index: 101;
                transition: transform 0.3s ease;
            }
            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }
            #sidebarOverlay.active {
                display: block;
            }
        }
    </style>
</head>
<body>
<div id="sidebarOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100;"></div>
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
                <button id="darkModeToggle" title="Toggle dark mode">☀️</button>
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
                <p class="content-subtitle">Management &amp; performance analytics</p>
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
                    <button class="btn-refresh" id="refreshDashboardBtn" type="button">
                        🔄 Refresh Dashboard
                    </button>
                    <button class="dash-header-btn" type="button" onclick="goTab('analytics')">📊 Analytics</button>
                    <button class="dash-header-btn" type="button" onclick="goTab('settings')">⚙️ Settings</button>
                    <button class="dash-header-btn" type="button" onclick="window.location='/restaurant/profile.php'">👤 Profile</button>
                </div>
            </div>

            <!-- HOME -->
            <section class="section active" data-section="home">
                <div id="dashboardError" style="display:none;background:#FFF3E0;border:1px solid #FC8019;color:#E65100;padding:10px 12px;border-radius:10px;margin-bottom:14px;">
                    <strong>Dashboard error:</strong> <span id="dashboardErrorText"></span>
                </div>
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
                    <button class="dash-action-btn" type="button" onclick="goTab('analytics')">📊 Analytics</button>
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
                        <div style="font-weight:900; font-size: 16px;">🍽️ Menu Management</div>
                        <select class="dash-control" id="menuCategoryFilter"><option value="">All Categories</option></select>
                        <select class="dash-control" id="menuSortBy">
                            <option value="popularity">By Popularity</option>
                            <option value="rating">By Rating</option>
                            <option value="price">By Price</option>
                            <option value="sales">By Sales</option>
                        </select>
                        <button class="btn-primary" type="button" onclick="window.location='/restaurant/menu.php'">➕ New Item</button>
                        <button class="btn-secondary" type="button" onclick="loadMenu(true)">Refresh</button>
                    </div>
                    <div id="menuItemsGrid" class="menu-grid"></div>
                </div>
            </section>

            <!-- ANALYTICS -->
            <section class="section" data-section="analytics">
                <div class="dash-table-wrap">
                    <div class="dash-controls" style="margin-top:0">
                        <div style="font-weight:900">📊 Analytics &amp; Reports</div>
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

            <!-- STAFF / SETTINGS / MARKETING -->
            <section class="section" data-section="staff">
                <div class="dash-table-wrap">
                    <div style="font-weight:900">🧑‍🍳 Staff &amp; Performance Tracking</div>
                    <p style="color:#93959F; margin-top: 8px;">Staff performance UI is not wired to backend yet.</p>
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
                    <div style="font-weight:900">📢 Promotions &amp; Campaigns</div>
                    <p style="color:#93959F; margin-top: 8px;">Marketing UI placeholder.</p>
                </div>
            </section>

        </div>
    </main>
</div>

<script>
// ─────────────────────────────────────────────
//  UTILITIES
// ─────────────────────────────────────────────
function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function formatBirr(n) {
    return Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
}

function parseDate(str) {
    if (!str) return null;
    const iso = String(str).replace(' ', 'T');
    const d = new Date(iso);
    return isNaN(d.getTime()) ? null : d;
}

function formatDateTime(str) {
    const d = parseDate(str);
    return d ? d.toLocaleString() : '';
}

function formatTime(str) {
    const d = parseDate(str);
    return d ? d.toLocaleTimeString() : '';
}

// Chart instances
let ordersTrendChartInstance = null;
let revenueTrendChartInstance = null;
let peakHoursChartInstance = null;
let analyticsOrdersChartInstance = null;
let analyticsRevenueChartInstance = null;
let analyticsPeakChartInstance = null;

function safeDestroy(instanceVar) {
    if (window[instanceVar]) {
        window[instanceVar].destroy();
        window[instanceVar] = null;
    }
}

// ─────────────────────────────────────────────
//  TAB NAVIGATION
// ─────────────────────────────────────────────
const sections = document.querySelectorAll('.section');
const navItems = document.querySelectorAll('.nav-item[data-tab]');
const _tabLoaded = {};

function goTab(tab) {
    sections.forEach(s => s.classList.toggle('active', s.getAttribute('data-section') === tab));
    navItems.forEach(a => a.classList.toggle('active', a.getAttribute('data-tab') === tab));

    const firstVisit = !_tabLoaded[tab];
    _tabLoaded[tab] = true;

    if (tab === 'orders' && typeof loadOrders === 'function') loadOrders(firstVisit);
    if (tab === 'menu' && typeof loadMenu === 'function') loadMenu(firstVisit);
    if (tab === 'analytics' && typeof loadAnalytics === 'function') loadAnalytics(firstVisit);
    if (tab === 'customers' && typeof loadCustomers === 'function') loadCustomers(firstVisit);
    if (tab === 'finance' && typeof loadFinance === 'function') loadFinance(firstVisit);
}
window.goTab = goTab;

navItems.forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        goTab(a.getAttribute('data-tab'));
    });
});

document.getElementById('sidebarToggle').addEventListener('click', () => {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');

    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('active');
    } else {
        sidebar.classList.toggle('collapsed');
        main.classList.toggle('expanded');
    }
});

document.getElementById('sidebarOverlay').addEventListener('click', function() {
    document.getElementById('sidebar').classList.remove('mobile-open');
    this.classList.remove('active');
});

// ─────────────────────────────────────────────
//  CHARTS - Create once
// ─────────────────────────────────────────────
function createOrdersTrendChart(data) {
    if (typeof Chart === 'undefined') return;
    const canvas = document.getElementById('ordersTrendChart');
    if (!canvas) return;
    
    safeDestroy('ordersTrendChartInstance');

    const ordersSeries = data?.chart?.orders_trend || [];
    if (ordersSeries.length === 0) return;

    const ctx = canvas.getContext('2d');
    const labels = ['-6d', '-5d', '-4d', '-3d', '-2d', '-1d', 'Today'];
    
    ordersTrendChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.slice(-ordersSeries.length),
            datasets: [{ 
                label: 'Orders', 
                data: ordersSeries,
                borderColor: '#48C479', 
                backgroundColor: 'rgba(72,196,121,0.12)', 
                tension: 0.35,
                fill: true
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }, 
            scales: { y: { beginAtZero: true } }
        }
    });
}

function createRevenueTrendChart(data) {
    if (typeof Chart === 'undefined') return;
    const canvas = document.getElementById('revenueTrendChart');
    if (!canvas) return;
    
    safeDestroy('revenueTrendChartInstance');

    const revenueSeries = data?.chart?.revenue_trend || [];
    if (revenueSeries.length === 0) return;

    const ctx = canvas.getContext('2d');
    const labels = ['-6d', '-5d', '-4d', '-3d', '-2d', '-1d', 'Today'];
    
    revenueTrendChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.slice(-revenueSeries.length),
            datasets: [{ 
                label: 'Revenue (Birr)', 
                data: revenueSeries,
                borderColor: '#FC8019', 
                backgroundColor: 'rgba(252,128,25,0.14)', 
                tension: 0.35,
                fill: true
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }, 
            scales: { y: { beginAtZero: true } }
        }
    });
}

function createPeakHoursChart(data) {
    if (typeof Chart === 'undefined') return;
    const canvas = document.getElementById('peakHoursChart');
    if (!canvas) return;
    
    safeDestroy('peakHoursChartInstance');

    const peak = data?.chart?.peak_hours || [];
    if (peak.length === 0) return;

    const ctx = canvas.getContext('2d');
    
    const peakLabels = peak.map(p => {
        const h = p.hour || p.hr || p.hour_of_day || 0;
        return String(h).padStart(2, '0') + ':00';
    });
    const peakData = peak.map(p => Number(p.revenue || p.revenue_amount || p.amount || 0));
    
    peakHoursChartInstance = new Chart(ctx, {
        type: 'bar',
        data: { 
            labels: peakLabels,
            datasets: [{ 
                label: 'Revenue', 
                data: peakData, 
                backgroundColor: 'rgba(252,128,25,0.55)',
                borderRadius: 6
            }] 
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }, 
            scales: { y: { beginAtZero: true } }
        }
    });
}

function renderTopItems(items, containerId = 'topItemsList') {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = '';
    (items || []).slice(0, 5).forEach(it => {
        const row = document.createElement('div');
        Object.assign(row.style, {
            display: 'flex', justifyContent: 'space-between', alignItems: 'center',
            padding: '10px 12px', border: '1px solid #E8E8E8', borderRadius: '10px', background: '#F8F8F8'
        });
        const sold = it.total_sold || it.sold_count || it.sold || 0;
        row.innerHTML = `
            <div>
                <div style="font-weight:900">${escapeHtml(it.name || '')}</div>
                <div style="font-size:12px;color:#93959F">${sold} sold</div>
            </div>
            <div style="font-weight:900;color:#FC8019">${formatBirr(it.revenue || 0)} Birr</div>`;
        el.appendChild(row);
    });
}

function statusBadge(status) {
    const s = (status || '').toString().toLowerCase();
    const cls = s === 'pending' ? 'status-pending' :
                s === 'preparing' ? 'status-preparing' :
                s === 'ready' ? 'status-ready' : 'status-delivered';
    return `<span class="status-badge ${cls}">${escapeHtml(s.replace('_', ' '))}</span>`;
}

// ─────────────────────────────────────────────
//  DASHBOARD DATA - MANUAL REFRESH ONLY
// ─────────────────────────────────────────────
let isLoadingDashboard = false;

async function loadDashboardData() {
    if (isLoadingDashboard) return;
    
    const errorEl = document.getElementById('dashboardError');
    const errorTextEl = document.getElementById('dashboardErrorText');
    const refreshBtn = document.getElementById('refreshDashboardBtn');
    
    isLoadingDashboard = true;
    
    // Show loading state on button
    if (refreshBtn) {
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<span class="refresh-spinner"></span> Loading...';
    }
    
    try {
        if (errorEl) errorEl.style.display = 'none';

        const res = await fetch('/api/dashboard-data.php', { headers: { 'Accept': 'application/json' } });
        let json;
        try { json = await res.json(); }
        catch { throw new Error('Invalid JSON from /api/dashboard-data.php'); }
        
        if (!json.success) throw new Error(json.error || 'API returned success:false');

        const d = json.data;
        const m = d.metrics || {};

        const todayOrdersEl = document.getElementById('kpi_today_orders');
        const pendingOrdersEl = document.getElementById('kpi_pending_orders');
        const totalRevenueEl = document.getElementById('kpi_total_revenue');
        const todayRevenueEl = document.getElementById('kpi_today_revenue');
        
        if (todayOrdersEl) todayOrdersEl.textContent = m.today_orders || 0;
        if (pendingOrdersEl) pendingOrdersEl.textContent = m.pending_orders || 0;
        if (totalRevenueEl) totalRevenueEl.textContent = formatBirr(m.total_revenue || 0);
        if (todayRevenueEl) todayRevenueEl.textContent = formatBirr(m.today_revenue || 0);

        const pending = Number(m.pending_orders || 0);
        const pendingSubEl = document.getElementById('kpi_pending_sub');
        const todayOrdersSubEl = document.getElementById('kpi_today_orders_sub');
        const totalRevenueSubEl = document.getElementById('kpi_total_revenue_sub');
        const todayRevenueSubEl = document.getElementById('kpi_today_revenue_sub');
        
        if (pendingSubEl) pendingSubEl.textContent = pending > 0 ? 'Action needed' : 'All clear!';
        if (todayOrdersSubEl) todayOrdersSubEl.textContent = (m.total_orders || 0) + ' total orders';
        if (totalRevenueSubEl) totalRevenueSubEl.textContent = 'Total to date';
        if (todayRevenueSubEl) todayRevenueSubEl.textContent = 'Today revenue';

        const badge = document.getElementById('pendingBadge');
        if (badge) {
            badge.style.display = pending > 0 ? 'inline-block' : 'none';
            badge.textContent = pending;
        }

        const updatedAt = new Date((d.updated_at || Date.now() / 1000) * 1000);
        const statusLine = document.getElementById('statusLine');
        if (statusLine) {
            statusLine.textContent = `Status: ${d.restaurant?.status || 'active'} · Last Updated: ${updatedAt.toLocaleTimeString()}`;
        }

        // Create charts (only once)
        createOrdersTrendChart(d);
        createRevenueTrendChart(d);
        createPeakHoursChart(d);
        
        renderTopItems(d.top_items, 'topItemsList');
        renderRecentOrders(d.recent_orders);
    } catch(e) {
        console.error('Dashboard error:', e);
        if (errorEl && errorTextEl) {
            errorTextEl.textContent = e?.message || String(e);
            errorEl.style.display = 'block';
        }
    } finally {
        isLoadingDashboard = false;
        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '🔄 Refresh Dashboard';
        }
    }
}

function renderRecentOrders(orders) {
    const tbody = document.getElementById('recentOrdersBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    (orders || []).slice(0, 5).forEach(o => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${escapeHtml(o.order_number || o.id || '')}</strong></td>
            <td>
                <div>${escapeHtml(o.customer_name || '')}</div>
                <small style="color:#93959F">${escapeHtml(o.customer_phone || '')}</small>
            </td>
            <td><strong>${formatBirr(o.total_amount || 0)} Birr</strong></td>
            <td>${statusBadge(o.status)}</td>
            <td>${escapeHtml(formatDateTime(o.created_at))}</td>
            <td><a class="btn-secondary" href="/restaurant/order-detail.php?id=${escapeHtml(o.id)}">View</a></td>`;
        tbody.appendChild(tr);
    });
}

// ─────────────────────────────────────────────
//  ORDERS
// ─────────────────────────────────────────────
let ordersPage = 1;
let ordersTotalPages = 1;

async function loadOrders(reset = false) {
    if (reset) ordersPage = 1;

    const status = document.getElementById('ordersStatusFilter')?.value;
    const q = document.getElementById('ordersSearch')?.value.trim() || '';

    const url = new URL('/api/orders-list.php', window.location.origin);
    url.searchParams.set('page', ordersPage);
    url.searchParams.set('pageSize', 10);
    if (status) url.searchParams.set('status', status);
    if (q) url.searchParams.set('q', q);

    try {
        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Failed');

        ordersTotalPages = json.data.meta?.totalPages || 1;
        const paginationText = document.getElementById('ordersPaginationText');
        if (paginationText) {
            paginationText.textContent = `Page ${json.data.meta?.page || ordersPage} / ${ordersTotalPages}`;
        }

        const tbody = document.getElementById('ordersBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        (json.data.items || []).forEach(o => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${escapeHtml(o.order_number || o.id)}</strong></td>
                <td>
                    <div>${escapeHtml(o.customer_name || '')}</div>
                    <small style="color:#93959F">${escapeHtml(o.customer_phone || '')}</small>
                </td>
                <td>${escapeHtml(o.items_count || '-')}</td>
                <td><strong>${formatBirr(o.total_amount || 0)} Birr</strong></td>
                <td>${statusBadge(o.status)}</td>
                <td>${escapeHtml(formatTime(o.created_at))}</td>
                <td><a class="btn-secondary" href="/restaurant/order-detail.php?id=${escapeHtml(o.id)}">View</a></td>
            `;
            tbody.appendChild(tr);
        });
    } catch(e) {
        console.error('loadOrders error:', e);
    }
}

function setOrdersStatusTab(status) {
    const filter = document.getElementById('ordersStatusFilter');
    if (filter) filter.value = status;
    loadOrders(true);
}

function ordersPrev() { if (ordersPage > 1) { ordersPage--; loadOrders(false); } }
function ordersNext() { if (ordersPage < ordersTotalPages) { ordersPage++; loadOrders(false); } }

const ordersFilter = document.getElementById('ordersStatusFilter');
const ordersSearch = document.getElementById('ordersSearch');
if (ordersFilter) ordersFilter.addEventListener('change', () => loadOrders(true));
if (ordersSearch) ordersSearch.addEventListener('input', () => loadOrders(true));

window.loadOrders = loadOrders;
window.ordersPrev = ordersPrev;
window.ordersNext = ordersNext;
window.setOrdersStatusTab = setOrdersStatusTab;

// ─────────────────────────────────────────────
//  MENU
// ─────────────────────────────────────────────
async function loadMenu(reset = false) {
    const catSelect = document.getElementById('menuCategoryFilter');
    const sortBy = document.getElementById('menuSortBy')?.value;
    const categoryId = catSelect?.value || '';

    const url = new URL('/api/menu-items.php', window.location.origin);
    url.searchParams.set('page', 1);
    url.searchParams.set('pageSize', 50);
    if (categoryId) url.searchParams.set('categoryId', categoryId);
    if (sortBy) url.searchParams.set('sortBy', sortBy);

    try {
        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Failed');

        const categories = json.data.categories || [];
        if (catSelect && (reset || catSelect.options.length <= 1)) {
            catSelect.innerHTML = '<option value="">All Categories</option>' +
                categories.map(c => `<option value="${escapeHtml(c.id)}">${escapeHtml(c.name)}</option>`).join('');
        }

        const grid = document.getElementById('menuItemsGrid');
        if (!grid) return;
        grid.innerHTML = '';
        (json.data.items || []).forEach(it => {
            const card = document.createElement('div');
            card.className = 'menu-card';

            const available = it.is_available !== false;
            card.innerHTML = `
                <div class="menu-card-img">🍲</div>
                <div class="menu-card-body">
                    <div class="menu-card-cat">${escapeHtml(it.category_name || 'Main')}</div>
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
                        <div class="menu-card-title">${escapeHtml(it.name || '')}</div>
                        <span class="status-badge ${available ? 'status-ready' : 'status-pending'}">${available ? 'Available' : 'Unavailable'}</span>
                    </div>
                    <div class="menu-card-desc">${escapeHtml(it.description || 'No description provided.')}</div>
                </div>
                <div class="menu-card-footer">
                    <div>
                        <div class="menu-card-price">${formatBirr(it.price || 0)} Birr</div>
                        <div class="menu-card-sold">Sold today: ${it.sold_today || 0}</div>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button class="btn-secondary" style="padding:6px 12px; font-size:12px;" onclick="window.location='/restaurant/menu.php'">Manage</button>
                    </div>
                </div>`;
            grid.appendChild(card);
        });
    } catch(e) {
        console.error('loadMenu error:', e);
    }
}

const menuCategory = document.getElementById('menuCategoryFilter');
const menuSort = document.getElementById('menuSortBy');
if (menuCategory) menuCategory.addEventListener('change', () => loadMenu(false));
if (menuSort) menuSort.addEventListener('change', () => loadMenu(false));
window.loadMenu = loadMenu;

// ─────────────────────────────────────────────
//  ANALYTICS
// ─────────────────────────────────────────────
(function setDefaultAnalyticsDates() {
    const to = new Date();
    const from = new Date();
    from.setDate(from.getDate() - 30);
    const toInput = document.getElementById('analyticsTo');
    const fromInput = document.getElementById('analyticsFrom');
    if (toInput) toInput.value = to.toISOString().split('T')[0];
    if (fromInput) fromInput.value = from.toISOString().split('T')[0];
})();

async function loadAnalytics(reset = false) {
    const from = document.getElementById('analyticsFrom')?.value;
    const to = document.getElementById('analyticsTo')?.value;

    const url = new URL('/api/analytics.php', window.location.origin);
    if (from) url.searchParams.set('from', from);
    if (to) url.searchParams.set('to', to);

    try {
        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Failed');

        const chart = json.data.chart || {};
        const labels = chart.labels || [];

        // Destroy old analytics charts
        safeDestroy('analyticsOrdersChartInstance');
        safeDestroy('analyticsRevenueChartInstance');
        safeDestroy('analyticsPeakChartInstance');
        
        if (typeof Chart === 'undefined') return;

        const ordersCanvas = document.getElementById('analyticsOrdersChart');
        if (ordersCanvas && chart.orders_trend && chart.orders_trend.length > 0) {
            analyticsOrdersChartInstance = new Chart(ordersCanvas.getContext('2d'), {
                type: 'line',
                data: { 
                    labels, 
                    datasets: [{ 
                        label: 'Orders',
                        data: chart.orders_trend,
                        borderColor: '#48C479', 
                        backgroundColor: 'rgba(72,196,121,0.12)', 
                        tension: 0.35,
                        fill: true
                    }] 
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }, 
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        const revenueCanvas = document.getElementById('analyticsRevenueChart');
        if (revenueCanvas && chart.revenue_trend && chart.revenue_trend.length > 0) {
            analyticsRevenueChartInstance = new Chart(revenueCanvas.getContext('2d'), {
                type: 'line',
                data: { 
                    labels, 
                    datasets: [{ 
                        label: 'Revenue',
                        data: chart.revenue_trend,
                        borderColor: '#FC8019', 
                        backgroundColor: 'rgba(252,128,25,0.14)', 
                        tension: 0.35,
                        fill: true
                    }] 
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }, 
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        renderTopItems(chart.top_items || [], 'analyticsTopItems');

        const peakCanvas = document.getElementById('analyticsPeakChart');
        const peak = chart.peak_hours || [];
        if (peakCanvas && peak.length > 0) {
            const peakLabels = peak.map(p => String(p.hour || p.hr || p.hour_of_day || 0).padStart(2, '0') + ':00');
            const peakData = peak.map(p => Number(p.revenue || p.revenue_amount || 0));
            
            analyticsPeakChartInstance = new Chart(peakCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: peakLabels,
                    datasets: [{ 
                        label: 'Revenue',
                        data: peakData,
                        backgroundColor: 'rgba(252,128,25,0.55)',
                        borderRadius: 6
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }, 
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    } catch(e) {
        console.error('loadAnalytics error:', e);
    }
}
window.loadAnalytics = loadAnalytics;

// ─────────────────────────────────────────────
//  CUSTOMERS
// ─────────────────────────────────────────────
async function loadCustomers(reset = false) {
    const q = document.getElementById('customersSearch')?.value.trim() || '';
    const url = new URL('/api/customers.php', window.location.origin);
    if (q) url.searchParams.set('q', q);
    url.searchParams.set('limit', 20);

    try {
        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Failed');

        const tbody = document.getElementById('customersBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        (json.data.items || []).forEach(c => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${escapeHtml(c.name || '')}</strong><div style="font-size:12px;color:#93959F">${escapeHtml(c.phone || '')}</div></td>
                <td>${escapeHtml(c.order_count || 0)}</td>
                <td><strong>${formatBirr(c.total_spent || 0)} Birr</strong></td>
                <td>${escapeHtml(formatDateTime(c.last_order_at))}</td>
                <td><button class="btn-secondary" type="button" onclick="alert('Not wired')">View</button></td>
            `;
            tbody.appendChild(tr);
        });
    } catch(e) {
        console.error('loadCustomers error:', e);
    }
}

const customersSearch = document.getElementById('customersSearch');
if (customersSearch) customersSearch.addEventListener('input', () => loadCustomers(false));
window.loadCustomers = loadCustomers;

// ─────────────────────────────────────────────
//  FINANCE
// ─────────────────────────────────────────────
async function loadFinance(reset = false) {
    try {
        const res = await fetch('/api/financial.php', { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'Failed');

        const s = json.data.summary || {};
        const revToday = document.getElementById('fin_revenue_today');
        const commToday = document.getElementById('fin_commission_today');
        const netToday = document.getElementById('fin_net_today');
        const pendingToday = document.getElementById('fin_pending_today');
        
        if (revToday) revToday.textContent = formatBirr(s.revenue_today || 0);
        if (commToday) commToday.textContent = formatBirr(s.commission_today || 0);
        if (netToday) netToday.textContent = formatBirr(s.net_earn_today || 0);
        if (pendingToday) pendingToday.textContent = formatBirr(s.pending_amount || 0);

        const tbody = document.getElementById('finTransactionsBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        (json.data.transactions || []).forEach(t => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${escapeHtml(t.order_number || t.id || '')}</td>
                <td>${statusBadge(t.status)}</td>
                <td><strong>${formatBirr(t.total_amount || 0)} Birr</strong></td>
                <td>${formatBirr(t.delivery_fee || 0)} Birr</div></td>
                <td>${escapeHtml(formatDateTime(t.created_at))}</td>
            `;
            tbody.appendChild(tr);
        });
    } catch(e) {
        console.error('loadFinance error:', e);
    }
}
window.loadFinance = loadFinance;

// ─────────────────────────────────────────────
//  GLOBAL SEARCH
// ─────────────────────────────────────────────
const globalSearch = document.getElementById('globalSearch');
if (globalSearch) {
    globalSearch.addEventListener('input', () => {
        const ordersSearchInput = document.getElementById('ordersSearch');
        if (ordersSearchInput) ordersSearchInput.value = globalSearch.value;
        goTab('orders');
        loadOrders(true);
    });
}

// ─────────────────────────────────────────────
//  INITIAL LOAD - ONLY ON PAGE LOAD, NO POLLING
// ─────────────────────────────────────────────
// Load dashboard data once on page load
loadDashboardData();

// ─────────────────────────────────────────────
//  REFRESH BUTTON - MANUAL REFRESH ONLY
// ─────────────────────────────────────────────
const refreshBtn = document.getElementById('refreshDashboardBtn');
if (refreshBtn) {
    refreshBtn.addEventListener('click', () => {
        loadDashboardData();
    });
}

// ─────────────────────────────────────────────
//  DARK MODE TOGGLE
// ─────────────────────────────────────────────
const themeRoot = document.getElementById('adminThemeRoot') || document.body;
const darkToggleBtn = document.getElementById('darkModeToggle');
const THEME_KEY = 'gebeta_admin_theme';

function applyTheme(theme) {
    const isNight = theme === 'night';
    themeRoot.classList.toggle('night', isNight);
    if (darkToggleBtn) darkToggleBtn.textContent = isNight ? '🌙' : '☀️';
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

if (darkToggleBtn) {
    darkToggleBtn.addEventListener('click', () => {
        const next = themeRoot.classList.contains('night') ? 'day' : 'night';
        localStorage.setItem(THEME_KEY, next);
        applyTheme(next);
    });
}

initTheme();

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    safeDestroy('ordersTrendChartInstance');
    safeDestroy('revenueTrendChartInstance');
    safeDestroy('peakHoursChartInstance');
    safeDestroy('analyticsOrdersChartInstance');
    safeDestroy('analyticsRevenueChartInstance');
    safeDestroy('analyticsPeakChartInstance');
});
</script>

<!-- RESPONSIVE JS -->
<script src="/assets/js/responsive.js"></script>
</body>
</html>