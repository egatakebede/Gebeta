<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = (int)$_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'suspend') {
        $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->execute(['suspended', $userId]);
        flash_set('success', 'User suspended.');
    } elseif ($action === 'activate') {
        $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->execute(['active', $userId]);
        flash_set('success', 'User activated.');
    }
    redirect('/admin/users.php');
}

$filter = $_GET['filter'] ?? 'all';
$query = 'SELECT id, name, email, phone, role, status, created_at FROM users WHERE 1=1';
if ($filter === 'customer') {
    $query .= ' AND role = "customer"';
} elseif ($filter === 'restaurant') {
    $query .= ' AND role = "restaurant"';
} elseif ($filter === 'admin') {
    $query .= ' AND role = "admin"';
}
$query .= ' ORDER BY created_at DESC';

$stmt = $pdo->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .user-card {
            background: #fff;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
        }
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }
        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            margin-right: 12px;
        }
        .user-info {
            flex: 1;
        }
        .user-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 4px;
        }
        .user-meta {
            font-size: 13px;
            color: var(--gray-text);
            margin-bottom: 2px;
        }
        .role-badge {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
        }
        .role-customer { background: #E3F2FD; color: #1976D2; }
        .role-restaurant { background: #FFF3E0; color: #F57C00; }
        .role-admin { background: #F3E5F5; color: #7B1FA2; }
    </style>
</head>
<body>
    <header class="page-header">
        <h1>👥 Manage Users</h1>
        <a class="pill-button" href="/admin/dashboard.php">Dashboard</a>
    </header>
    
    <?php if ($success = flash_get('success')): ?>
        <div style="background:#E8F5E9;border:2px solid #66BB6A;border-radius:16px;padding:16px;margin:20px;color:#2E7D32;font-weight:600;">
            ✅ <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <main class="page-content">
        <div class="filter-tabs">
            <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">All (<?= count($users) ?>)</a>
            <a href="?filter=customer" class="filter-tab <?= $filter === 'customer' ? 'active' : '' ?>">Customers</a>
            <a href="?filter=restaurant" class="filter-tab <?= $filter === 'restaurant' ? 'active' : '' ?>">Restaurant Owners</a>
            <a href="?filter=admin" class="filter-tab <?= $filter === 'admin' ? 'active' : '' ?>">Admins</a>
        </div>
        
        <?php if (empty($users)): ?>
            <div class="empty-state">No users found.</div>
        <?php else: ?>
            <?php foreach ($users as $user): 
                $initials = strtoupper(substr($user['name'], 0, 1) . (strpos($user['name'], ' ') ? substr($user['name'], strpos($user['name'], ' ') + 1, 1) : ''));
            ?>
                <div class="user-card">
                    <div style="display:flex;align-items:start;">
                        <div class="user-avatar"><?= $initials ?></div>
                        <div class="user-info">
                            <div class="user-header">
                                <div>
                                    <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                                    <div class="user-meta">📧 <?= htmlspecialchars($user['email']) ?></div>
                                    <div class="user-meta">📞 <?= htmlspecialchars($user['phone']) ?></div>
                                    <div class="user-meta">📅 Joined: <?= date('M d, Y', strtotime($user['created_at'])) ?></div>
                                </div>
                                <div style="text-align:right;">
                                    <span class="role-badge role-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
                                    <br>
                                    <span class="status-badge" style="background:<?= $user['status'] === 'active' ? '#E8F5E9' : '#FFEBEE' ?>;color:<?= $user['status'] === 'active' ? '#2E7D32' : '#C62828' ?>;padding:6px 12px;border-radius:999px;font-size:11px;font-weight:700;margin-top:8px;display:inline-block;"><?= ucfirst($user['status']) ?></span>
                                </div>
                            </div>
                            
                            <?php if ($user['role'] !== 'admin'): ?>
                                <form method="post" style="margin-top:12px;">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <?php if ($user['status'] === 'active'): ?>
                                        <button type="submit" name="action" value="suspend" class="action-btn btn-suspend" onclick="return confirm('Suspend this user?')" style="padding:8px 16px;border-radius:12px;border:none;font-weight:600;font-size:13px;cursor:pointer;background:#FFA726;color:#fff;">⚠️ Suspend</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="activate" class="action-btn btn-activate" style="padding:8px 16px;border-radius:12px;border:none;font-weight:600;font-size:13px;cursor:pointer;background:#66BB6A;color:#fff;">✅ Activate</button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    
    <footer class="bottom-bar">
        <a href="/admin/dashboard.php">
            <span>🏠</span>
            <span>Dashboard</span>
        </a>
        <a href="/admin/restaurants.php">
            <span>🏪</span>
            <span>Restaurants</span>
        </a>
        <a href="/admin/users.php" class="active">
            <span>👥</span>
            <span>Users</span>
        </a>
        <a href="/admin/orders.php">
            <span>📦</span>
            <span>Orders</span>
        </a>
        <a href="/admin/reports.php">
            <span>📊</span>
            <span>Reports</span>
        </a>
    </footer>
</body>
</html>
