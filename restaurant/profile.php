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
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name        = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $cuisine     = sanitize($_POST['cuisine_type'] ?? '');
    $location    = sanitize($_POST['location'] ?? '');
    $phone       = sanitize($_POST['phone'] ?? '');
    if ($name && $location && $phone) {
        $update = $pdo->prepare('UPDATE restaurants SET name = ?, description = ?, cuisine_type = ?, location = ?, phone = ? WHERE id = ?');
        $update->execute([$name, $description, $cuisine, $location, $phone, $restaurant['id']]);
        $message = 'Restaurant settings saved.';
        $restaurant = array_merge($restaurant, ['name' => $name, 'description' => $description, 'cuisine_type' => $cuisine, 'location' => $location, 'phone' => $phone]);
    } else {
        $message = 'Please complete the required fields.';
    }
}
$cartCount = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Profile · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>Restaurant Settings</h1>
        <a class="pill-button" href="/restaurant/dashboard.php">Dashboard</a>
    </header>
    <main class="page-content">
        <?php if ($message): ?>
            <div class="notice"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post" class="profile-form">
            <?= csrf_field() ?>
            <label>Restaurant name</label>
            <input name="name" value="<?= htmlspecialchars($restaurant['name']) ?>" required>
            <label>Description</label>
            <textarea name="description" rows="3"><?= htmlspecialchars($restaurant['description']) ?></textarea>
            <label>Cuisine types</label>
            <input name="cuisine_type" value="<?= htmlspecialchars($restaurant['cuisine_type']) ?>">
            <label>Location</label>
            <input name="location" value="<?= htmlspecialchars($restaurant['location']) ?>" required>
            <label>Phone</label>
            <input name="phone" value="<?= htmlspecialchars($restaurant['phone']) ?>" required>
            <button class="primary-btn" type="submit">Save changes</button>
        </form>
    </main>
    <footer class="bottom-bar">
        <a href="/restaurant/dashboard.php">Home Dashboard</a>
        <a href="/restaurant/menu.php">Menu Menu</a>
        <a href="/restaurant/analytics.php">Analytics Analytics</a>
        <a href="/restaurant/profile.php">Profile Profile</a>
    </footer>
</body>
</html>
