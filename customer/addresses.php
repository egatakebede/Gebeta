<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
if (!isset($_SESSION['delivery_address'])) {
    $_SESSION['delivery_address'] = 'Bole, Addis Ababa, Building 12, Apt 5A';
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = sanitize($_POST['delivery_address'] ?? '');
    if ($address) {
        $_SESSION['delivery_address'] = $address;
        $message = 'Address saved.';
    } else {
        $message = 'Please enter a valid address.';
    }
}
$cartCount = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Addresses · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>Manage addresses</h1>
        <a class="pill-button" href="/customer/profile.php">Profile</a>
    </header>
    <main class="page-content">
        <?php if ($message): ?>
            <div class="notice"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post" class="profile-form">
            <label>Default delivery address</label>
            <textarea name="delivery_address" rows="4"><?= htmlspecialchars($_SESSION['delivery_address']) ?></textarea>
            <button class="primary-btn" type="submit">Save address</button>
        </form>
    </main>
    <footer class="bottom-bar">
        <a href="/customer/dashboard.php">🏠 Home</a>
        <a href="/customer/cart.php">🛒 Cart (<?= $cartCount ?>)</a>
        <a href="/customer/orders.php">📄 Orders</a>
        <a href="/customer/profile.php">👤 Profile</a>
    </footer>
</body>
</html>
