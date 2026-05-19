<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
if (!isset($_SESSION['delivery_address'])) {
    $_SESSION['delivery_address'] = 'Piassa, Hawassa, Building 12, Apt 5A';
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
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
        <div class="header-row">
            <h1>Location Delivery Address</h1>
            <a class="pill-button" href="/customer/profile.php">← Back</a>
        </div>
    </header>
    <main class="page-content">
        <?php if ($message): ?>
            <div class="notice"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post" class="profile-form">
            <?= csrf_field() ?>
            <label>📌 Your delivery location</label>
            <textarea name="delivery_address" rows="4" placeholder="Building name, floor, apartment number..."><?= htmlspecialchars($_SESSION['delivery_address']) ?></textarea>
            <button class="primary-btn" type="submit">💾 Save address</button>
        </form>
    </main>
    <nav class="bottom-nav">
        <a href="/customer/dashboard.php" class="nav-item">
            <span class="nav-icon">Home</span>
            <span class="nav-label">Home</span>
        </a>
        <a href="/customer/cart.php" class="nav-item">
            <span class="nav-icon">
                Cart
                <?php if ($cartCount > 0): ?><span class="nav-badge"><?= $cartCount ?></span><?php endif; ?>
            </span>
            <span class="nav-label">Cart</span>
        </a>
        <a href="/customer/orders.php" class="nav-item">
            <span class="nav-icon">Orders</span>
            <span class="nav-label">Orders</span>
        </a>
        <a href="/customer/profile.php" class="nav-item active">
            <span class="nav-icon">Profile</span>
            <span class="nav-label">Profile</span>
        </a>
    </nav>
</body>
</html>
