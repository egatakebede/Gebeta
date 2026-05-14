<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
require_once __DIR__ . '/../includes/db.php';

$user = $_SESSION['user'];
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    if ($name && $phone && $email) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE (email = ? OR phone = ?) AND id != ? LIMIT 1');
        $stmt->execute([$email, $phone, $user['id']]);
        if (!$stmt->fetch()) {
            $update = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
            $update->execute([$name, $email, $phone, $user['id']]);
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['phone'] = $phone;
            $message = 'Profile saved successfully.';
        } else {
            $message = 'Email or phone is already in use.';
        }
    } else {
        $message = 'Please complete all profile fields.';
    }
}
$cartCount = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>Profile</h1>
        <a class="pill-button" href="/customer/dashboard.php">Home</a>
    </header>
    <main class="page-content">
        <?php if ($message): ?>
            <div class="notice"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post" class="profile-form">
            <label>Full name</label>
            <input name="name" value="<?= htmlspecialchars($user['name']) ?>">
            <label>Phone</label>
            <input name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
            <label>Email</label>
            <input name="email" value="<?= htmlspecialchars($user['email']) ?>">
            <button class="primary-btn" type="submit">Save changes</button>
        </form>
        <div class="profile-links">
            <a class="secondary-btn" href="/customer/addresses.php">Manage addresses</a>
            <a class="secondary-btn" href="/logout.php">Logout</a>
        </div>
    </main>
    <footer class="bottom-bar">
        <a href="/customer/dashboard.php">🏠 Home</a>
        <a href="/customer/cart.php">🛒 Cart (<?= $cartCount ?>)</a>
        <a href="/customer/orders.php">📄 Orders</a>
        <a href="/customer/profile.php">👤 Profile</a>
    </footer>
</body>
</html>
