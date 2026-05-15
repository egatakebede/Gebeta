<?php
// Usage: include this file, set $active_nav = 'home' | 'cart' | 'orders' | 'profile'
$active_nav = $active_nav ?? 'home';
$cartCount  = $cartCount ?? get_cart_count();
$current    = $active_nav;
?>
<nav class="bottom-nav">
    <a href="/customer/dashboard.php" class="nav-item <?= $current === 'home'    ? 'active' : '' ?>">
        <span class="nav-icon">🏠</span>
        <span class="nav-label">Home</span>
    </a>
    <a href="/customer/cart.php" class="nav-item <?= $current === 'cart'    ? 'active' : '' ?>">
        <span class="nav-icon">
            🛒
            <?php if ($cartCount > 0): ?>
                <span class="nav-badge cart-count"><?= $cartCount ?></span>
            <?php endif; ?>
        </span>
        <span class="nav-label">Cart</span>
    </a>
    <a href="/customer/orders.php" class="nav-item <?= $current === 'orders'  ? 'active' : '' ?>">
        <span class="nav-icon">📄</span>
        <span class="nav-label">Orders</span>
    </a>
    <a href="/customer/profile.php" class="nav-item <?= $current === 'profile' ? 'active' : '' ?>">
        <span class="nav-icon">👤</span>
        <span class="nav-label">Profile</span>
    </a>
</nav>
