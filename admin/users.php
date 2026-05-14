<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->query('SELECT id, name, email, phone, role, status, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>Manage Users</h1>
        <a class="pill-button" href="/admin/dashboard.php">Dashboard</a>
    </header>
    <main class="page-content">
        <?php foreach ($users as $user): ?>
            <div class="admin-card">
                <div>
                    <h3><?= htmlspecialchars($user['name']) ?></h3>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                    <p><?= htmlspecialchars(ucfirst($user['role'])) ?> • Joined <?= htmlspecialchars(date('M Y', strtotime($user['created_at']))) ?></p>
                </div>
                <div class="admin-actions">
                    <a class="secondary-btn" href="#">View</a>
                </div>
            </div>
        <?php endforeach; ?>
    </main>
    <footer class="bottom-bar">
        <a href="/admin/dashboard.php">Dashboard</a>
        <a href="/admin/restaurants.php">Restaurants</a>
        <a href="/admin/users.php">Users</a>
        <a href="/admin/orders.php">Orders</a>
        <a href="/admin/reports.php">Reports</a>
    </footer>
</body>
</html>
