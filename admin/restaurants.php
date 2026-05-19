<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $restaurantId = (int)$_POST['restaurant_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare('UPDATE restaurants SET status = ? WHERE id = ?');
        $stmt->execute(['active', $restaurantId]);
        flash_set('success', 'Restaurant approved successfully!');
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare('UPDATE restaurants SET status = ? WHERE id = ?');
        $stmt->execute(['suspended', $restaurantId]);
        flash_set('success', 'Restaurant rejected.');
    } elseif ($action === 'suspend') {
        $stmt = $pdo->prepare('UPDATE restaurants SET status = ? WHERE id = ?');
        $stmt->execute(['suspended', $restaurantId]);
        flash_set('success', 'Restaurant suspended.');
    } elseif ($action === 'activate') {
        $stmt = $pdo->prepare('UPDATE restaurants SET status = ? WHERE id = ?');
        $stmt->execute(['active', $restaurantId]);
        flash_set('success', 'Restaurant activated.');
    }
    redirect('/admin/restaurants.php');
}

$filter = $_GET['filter'] ?? 'all';

// Get counts for each status
$stmt = $pdo->query('SELECT status, COUNT(*) as count FROM restaurants GROUP BY status');
$counts = ['all' => 0, 'pending' => 0, 'active' => 0, 'suspended' => 0];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $counts[$row['status']] = $row['count'];
    $counts['all'] += $row['count'];
}

// Build query based on filter
$query = 'SELECT r.*, u.name AS owner_name, u.email AS owner_email FROM restaurants r JOIN users u ON r.user_id = u.id';
if ($filter === 'pending') {
    $query .= ' WHERE r.status = "pending"';
} elseif ($filter === 'active') {
    $query .= ' WHERE r.status = "active"';
} elseif ($filter === 'suspended') {
    $query .= ' WHERE r.status = "suspended"';
}
$query .= ' ORDER BY r.created_at DESC';

$stmt = $pdo->query($query);
$restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Restaurants · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding: 4px;
        }
        .filter-tab {
            padding: 10px 20px;
            border-radius: 999px;
            background: #fff;
            border: 2px solid var(--border-gray);
            font-weight: 600;
            font-size: 14px;
            color: var(--gray-text);
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .filter-tab.active {
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-dark));
            color: #fff;
            border-color: var(--primary-orange);
        }
        .restaurant-card-admin {
            background: #fff;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
        }
        .restaurant-card-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .restaurant-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }
        .restaurant-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 4px;
        }
        .restaurant-meta {
            font-size: 13px;
            color: var(--gray-text);
            margin-bottom: 2px;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        .action-btn {
            padding: 8px 16px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-approve {
            background: linear-gradient(135deg, #48C479, #00A878);
            color: #fff;
        }
        .btn-reject {
            background: linear-gradient(135deg, #E53935, #D32F2F);
            color: #fff;
        }
        .btn-suspend {
            background: #FFA726;
            color: #fff;
        }
        .btn-activate {
            background: #66BB6A;
            color: #fff;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <header class="page-header">
        <h1>🏪 Manage Restaurants</h1>
        <a class="pill-button" href="/admin/dashboard.php">Dashboard</a>
    </header>
    
    <?php if ($success = flash_get('success')): ?>
        <div style="background:#E8F5E9;border:2px solid #66BB6A;border-radius:16px;padding:16px;margin:20px;color:#2E7D32;font-weight:600;">
            ✅ <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <main class="page-content">
        <div class="filter-tabs">
            <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">All (<?= $counts['all'] ?>)</a>
            <a href="?filter=pending" class="filter-tab <?= $filter === 'pending' ? 'active' : '' ?>">Pending (<?= $counts['pending'] ?>)</a>
            <a href="?filter=active" class="filter-tab <?= $filter === 'active' ? 'active' : '' ?>">Active (<?= $counts['active'] ?>)</a>
            <a href="?filter=suspended" class="filter-tab <?= $filter === 'suspended' ? 'active' : '' ?>">Suspended (<?= $counts['suspended'] ?>)</a>
        </div>
        
        <?php if (empty($restaurants)): ?>
            <div class="empty-state">No restaurants found.</div>
        <?php else: ?>
            <?php foreach ($restaurants as $rest): ?>
                <div class="restaurant-card-admin">
                    <div class="restaurant-header">
                        <div>
                            <div class="restaurant-title"><?= htmlspecialchars($rest['name']) ?></div>
                            <div class="restaurant-meta">👤 Owner: <?= htmlspecialchars($rest['owner_name']) ?> (<?= htmlspecialchars($rest['owner_email']) ?>)</div>
                            <div class="restaurant-meta">🍴 Cuisine: <?= htmlspecialchars($rest['cuisine_type']) ?></div>
                            <div class="restaurant-meta">📍 Location: <?= htmlspecialchars($rest['location']) ?></div>
                            <div class="restaurant-meta">📞 Phone: <?= htmlspecialchars($rest['phone']) ?></div>
                        </div>
                        <span class="status-badge" style="background:<?= $rest['status'] === 'active' ? '#E8F5E9' : ($rest['status'] === 'pending' ? '#FFF3E0' : '#FFEBEE') ?>;color:<?= $rest['status'] === 'active' ? '#2E7D32' : ($rest['status'] === 'pending' ? '#F57C00' : '#C62828') ?>;padding:8px 14px;border-radius:999px;font-size:12px;font-weight:700;"><?= ucfirst($rest['status']) ?></span>
                    </div>
                    
                    <?php if ($rest['description']): ?>
                        <p style="color:var(--gray-text);font-size:14px;margin-bottom:12px;"><?= htmlspecialchars($rest['description']) ?></p>
                    <?php endif; ?>
                    
                    <div style="display:flex;gap:12px;font-size:13px;color:var(--gray-text);margin-bottom:12px;">
                        <span>⭐ Rating: <?= number_format($rest['rating'], 1) ?></span>
                        <span>📅 Joined: <?= date('M d, Y', strtotime($rest['created_at'])) ?></span>
                    </div>
                    
                    <form method="post" class="action-buttons">
                        <input type="hidden" name="restaurant_id" value="<?= $rest['id'] ?>">
                        <?php if ($rest['status'] === 'pending'): ?>
                            <button type="submit" name="action" value="approve" class="action-btn btn-approve">✅ Approve</button>
                            <button type="submit" name="action" value="reject" class="action-btn btn-reject" onclick="return confirm('Reject this restaurant?')">❌ Reject</button>
                        <?php elseif ($rest['status'] === 'active'): ?>
                            <button type="submit" name="action" value="suspend" class="action-btn btn-suspend" onclick="return confirm('Suspend this restaurant?')">⚠️ Suspend</button>
                        <?php elseif ($rest['status'] === 'suspended'): ?>
                            <button type="submit" name="action" value="activate" class="action-btn btn-activate">✅ Activate</button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    
    <footer class="bottom-bar">
        <a href="/admin/dashboard.php">
            <span>🏠</span>
            <span>Dashboard</span>
        </a>
        <a href="/admin/restaurants.php" class="active">
            <span>🏪</span>
            <span>Restaurants</span>
        </a>
        <a href="/admin/users.php">
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
