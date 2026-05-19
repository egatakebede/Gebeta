<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['restaurant']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE user_id = ? LIMIT 1');
$stmt->execute([$_SESSION['user']['id']]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$restaurant) {
    redirect('/');
}

// Redirect to dashboard if restaurant is not active
if ($restaurant['status'] !== 'active') {
    redirect('/restaurant/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = number_format(max(0, (float)($_POST['price'] ?? 0)), 2);
    $categoryName = sanitize($_POST['category'] ?? 'Main');
    if ($name && $price > 0) {
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE restaurant_id = ? AND name = ? LIMIT 1');
        $stmt->execute([$restaurant['id'], $categoryName]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$category) {
            $insertCat = $pdo->prepare('INSERT INTO categories (restaurant_id, name) VALUES (?, ?)');
            $insertCat->execute([$restaurant['id'], $categoryName]);
            $categoryId = $pdo->lastInsertId();
        } else {
            $categoryId = $category['id'];
        }
        $insert = $pdo->prepare('INSERT INTO menu_items (category_id, name, description, price) VALUES (?, ?, ?, ?)');
        $insert->execute([$categoryId, $name, $description, $price]);
    }
}

$stmt = $pdo->prepare('SELECT mi.*, c.name AS category_name FROM menu_items mi JOIN categories c ON mi.category_id = c.id WHERE c.restaurant_id = ? ORDER BY mi.created_at DESC');
$stmt->execute([$restaurant['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cartCount = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>Manage Menu</h1>
        <a class="pill-button" href="/restaurant/dashboard.php">Dashboard</a>
    </header>
    <main class="page-content">
        <section class="menu-form-card">
            <h2>Add new menu item</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input name="name" required>
                <label>Description</label>
                <input name="description">
                <label>Price</label>
                <input name="price" type="number" step="0.01" required>
                <label>Category</label>
                <input name="category" value="Main">
                <button class="primary-btn" type="submit">Add item</button>
            </form>
        </section>

        <section class="menu-list">
            <?php if (empty($items)): ?>
                <div class="empty-state">No menu items yet. Add your first dish.</div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <div class="menu-item-card">
                        <div>
                            <h3><?= htmlspecialchars($item['name']) ?> <span class="tag"><?= htmlspecialchars($item['category_name']) ?></span></h3>
                            <p><?= htmlspecialchars($item['description']) ?></p>
                            <strong><?= format_price($item['price']) ?></strong>
                        </div>
                        <span class="availability <?= $item['is_available'] ? 'active' : '' ?>"><?= $item['is_available'] ? 'Available' : 'Unavailable' ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
    <footer class="bottom-bar">
        <a href="/restaurant/dashboard.php">🏠 Dashboard</a>
        <a href="/restaurant/menu.php">🍽️ Menu</a>
        <a href="/restaurant/analytics.php">📊 Analytics</a>
        <a href="/restaurant/profile.php">👤 Profile</a>
    </footer>
</body>
</html>
