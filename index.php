<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    $role = $_SESSION['user']['role'];
    if ($role === 'restaurant') {
        redirect('/restaurant/dashboard.php');
    }
    if ($role === 'admin') {
        redirect('/admin/dashboard.php');
    }
    redirect('/customer/dashboard.php');
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gebeta · Order Food in Addis Ababa</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="hero-header">
        <div class="topbar">
            <div class="brand">🍽️ <strong>Gebeta</strong></div>
            <button class="link-button" id="open-login">Login</button>
        </div>

        <section class="hero-content">
            <h1>Order food from favourite restaurants near you</h1>
            <p>Fresh food delivered to your doorstep in Addis Ababa.</p>
            <div class="hero-emojis">🫓 🍲 🥘</div>
            <button class="primary-btn" id="open-register">Get Started</button>
        </section>

        <div class="hero-features">
            <div><span>⚡</span><strong>Live tracking</strong></div>
            <div><span>💰</span><strong>Best deals</strong></div>
            <div><span>🏪</span><strong>500+ restaurants</strong></div>
        </div>
    </header>

    <?php if ($error = flash_get('error')): ?>
        <div class="notice" style="margin: 20px; max-width: 720px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <main class="landing-section">
        <div class="info-card">
            <h2>How Gebeta works</h2>
            <p>Search restaurants, add menu items to cart, checkout and track your order in real time.</p>
        </div>
    </main>

    <div class="modal-overlay hidden" id="modal-overlay"></div>
    <div class="modal-sheet hidden" id="login-modal">
        <button class="close-modal" id="close-login">✕</button>
        <h2>Login</h2>
        <form id="login-form" method="post" action="login.php">
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button class="primary-btn" type="submit">Login</button>
            <div class="small-note">No account? <button type="button" class="switch-tab" data-target="register">Create account</button></div>
        </form>
    </div>

    <div class="modal-sheet hidden" id="register-modal">
        <button class="close-modal" id="close-register">✕</button>
        <h2>Sign up</h2>
        <form id="register-form" method="post" action="register.php">
            <label>Full name</label>
            <input type="text" name="name" required>
            <label>Phone</label>
            <input type="tel" name="phone" required>
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <label>Account type</label>
            <select name="role">
                <option value="customer">Customer</option>
                <option value="restaurant">Restaurant</option>
            </select>
            <button class="primary-btn" type="submit">Continue</button>
            <div class="small-note">Already have an account? <button type="button" class="switch-tab" data-target="login">Login</button></div>
        </form>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
