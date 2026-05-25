<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save settings logic here
    $success = "Settings saved successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings · Gebeta Admin</title>
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
                    <a href="/admin/payments.php" class="nav-item">
                        <span class="nav-item-icon">💰</span>
                        <span>Payments</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Settings</div>
                    <a href="/admin/settings.php" class="nav-item active">
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
                    <input type="text" class="header-search-input" placeholder="Search...">
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
                    <h1 class="content-title">⚙️ Settings</h1>
                    <p class="content-subtitle">Configure platform settings</p>
                </div>
                
                <?php if (isset($success)): ?>
                <div style="background: var(--green-50); color: var(--green-700); padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                    ✓ <?= $success ?>
                </div>
                <?php endif; ?>
                
                <!-- Commission & Fees -->
                <div class="data-table-container" style="margin-bottom: 24px;">
                    <div class="table-header">
                        <h2 class="table-title">💰 Commission & Fees</h2>
                    </div>
                    <div style="padding: 24px;">
                        <form method="POST">
                            <div style="display: grid; gap: 16px; max-width: 600px;">
                                <div>
                                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Platform Commission Rate (%)</label>
                                    <input type="number" name="commission_rate" value="15" step="0.1" style="width: 100%; padding: 12px; border: 1px solid var(--gray-300); border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Delivery Base Fee (Birr)</label>
                                    <input type="number" name="delivery_base" value="50" step="1" style="width: 100%; padding: 12px; border: 1px solid var(--gray-300); border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Per KM Fee (Birr)</label>
                                    <input type="number" name="per_km_fee" value="10" step="1" style="width: 100%; padding: 12px; border: 1px solid var(--gray-300); border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Service Fee (%)</label>
                                    <input type="number" name="service_fee" value="2" step="0.1" style="width: 100%; padding: 12px; border: 1px solid var(--gray-300); border-radius: 8px;">
                                </div>
                                <button type="submit" class="action-btn action-btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Payment Settings -->
                <div class="data-table-container" style="margin-bottom: 24px;">
                    <div class="table-header">
                        <h2 class="table-title">💳 Payment Settings</h2>
                    </div>
                    <div style="padding: 24px;">
                        <form method="POST">
                            <div style="display: grid; gap: 16px; max-width: 600px;">
                                <label style="display: flex; align-items: center; gap: 12px;">
                                    <input type="checkbox" name="enable_cash" checked style="width: 20px; height: 20px;">
                                    <span style="font-weight: 500;">Enable Cash Payments</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 12px;">
                                    <input type="checkbox" name="enable_bank" checked style="width: 20px; height: 20px;">
                                    <span style="font-weight: 500;">Enable Bank Transfer</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 12px;">
                                    <input type="checkbox" name="enable_telebirr" checked style="width: 20px; height: 20px;">
                                    <span style="font-weight: 500;">Enable Telebirr</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 12px;">
                                    <input type="checkbox" name="enable_mpesa" checked style="width: 20px; height: 20px;">
                                    <span style="font-weight: 500;">Enable M-Pesa</span>
                                </label>
                                <button type="submit" class="action-btn action-btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- System Settings -->
                <div class="data-table-container" style="margin-bottom: 24px;">
                    <div class="table-header">
                        <h2 class="table-title">🌐 System Settings</h2>
                    </div>
                    <div style="padding: 24px;">
                        <form method="POST">
                            <div style="display: grid; gap: 16px; max-width: 600px;">
                                <div>
                                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Currency</label>
                                    <select name="currency" style="width: 100%; padding: 12px; border: 1px solid var(--gray-300); border-radius: 8px;">
                                        <option value="ETB" selected>Ethiopian Birr (ETB)</option>
                                        <option value="USD">US Dollar (USD)</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Timezone</label>
                                    <select name="timezone" style="width: 100%; padding: 12px; border: 1px solid var(--gray-300); border-radius: 8px;">
                                        <option value="Africa/Addis_Ababa" selected>Africa/Addis_Ababa</option>
                                        <option value="UTC">UTC</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Tax Rate (%)</label>
                                    <input type="number" name="tax_rate" value="15" step="0.1" style="width: 100%; padding: 12px; border: 1px solid var(--gray-300); border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Site URL</label>
                                    <input type="url" name="site_url" value="https://gebeta.com" style="width: 100%; padding: 12px; border: 1px solid var(--gray-300); border-radius: 8px;">
                                </div>
                                <button type="submit" class="action-btn action-btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Database & Backup -->
                <div class="data-table-container">
                    <div class="table-header">
                        <h2 class="table-title">💾 Database & Backup</h2>
                    </div>
                    <div style="padding: 24px;">
                        <div style="max-width: 600px;">
                            <p style="margin-bottom: 16px; color: var(--gray-600);">Last Backup: Today at 03:00 AM</p>
                            <div style="display: flex; gap: 12px;">
                                <button class="action-btn action-btn-primary" onclick="alert('Creating backup...')">Create Backup</button>
                                <button class="action-btn action-btn-secondary" onclick="alert('Downloading backup...')">Download</button>
                                <button class="action-btn action-btn-secondary" onclick="alert('Restore from backup...')">Restore</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainContent').classList.toggle('expanded');
        });
    </script>
</body>
</html>
