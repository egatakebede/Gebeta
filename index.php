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
    <title>Gebeta · Food Delivery in Hawassa</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#FC8019">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <header class="hero-header">
        <nav class="hero-nav">
            <div class="brand">Gebeta</div>
            <button class="pill-button" id="open-login">Sign in</button>
        </nav>

        <div class="hero-content">
            <h1>Food delivery from Hawassa's best restaurants</h1>
            <p>Order from your favorite local spots. Fast delivery to your door.</p>
            <div class="hero-cta">
                <button class="primary-btn hero-btn" id="open-register">Get started</button>
            </div>
        </div>

        <div class="hero-visual">
            <div class="food-grid">
                <div class="food-card"><img src="/assets/images/food/injera.jpg" alt="Injera"></div>
                <div class="food-card"><img src="/assets/images/food/doro-wat.jpg" alt="Doro Wat"></div>
                <div class="food-card"><img src="/assets/images/food/coffee.jpg" alt="Coffee"></div>
                <div class="food-card"><img src="/assets/images/food/pizza.jpg" alt="Pizza"></div>
                <div class="food-card"><img src="/assets/images/food/tibs.jpg" alt="Tibs"></div>
                <div class="food-card"><img src="/assets/images/food/burger.jpg" alt="Burger"></div>
            </div>
        </div>
    </header>

    <?php if ($error = flash_get('error')): ?>
        <div class="toast-message error-toast"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success = flash_get('success')): ?>
        <div class="toast-message success-toast"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <main class="landing-main">
        <section class="features-section">
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Fast delivery</h3>
                <p>Get your food in 30 minutes or less</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🍽️</div>
                <h3>Best restaurants</h3>
                <p>Curated selection of top-rated spots</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Easy ordering</h3>
                <p>Simple checkout, track in real-time</p>
            </div>
        </section>

        <section class="cuisines-section">
            <h2>Popular cuisines</h2>
            <div class="cuisine-grid">
                <div class="cuisine-pill">🍲 Ethiopian</div>
                <div class="cuisine-pill">☕ Coffee</div>
                <div class="cuisine-pill">🍕 Pizza</div>
                <div class="cuisine-pill">🍜 Asian</div>
                <div class="cuisine-pill">🥗 Healthy</div>
                <div class="cuisine-pill">🍔 Burgers</div>
            </div>
        </section>
    </main>

    <div class="modal-overlay" id="modal-overlay"></div>

    <div class="modal-sheet" id="login-modal">
        <div class="modal-drag-handle"></div>
        <button class="close-modal" id="close-login">✕</button>
        <h2>Welcome back</h2>
        <form id="login-form" method="post" action="login.php">
            <label>Email</label>
            <input type="email" name="email" placeholder="you@example.com" required>
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
            <input type="hidden" name="latitude" id="login-lat" value="7.0621">
            <input type="hidden" name="longitude" id="login-lng" value="38.4760">
            <input type="hidden" name="location_name" id="login-loc" value="Hawassa, Ethiopia">
            <button class="primary-btn" type="submit">Sign in</button>
        </form>
        <div class="social-divider">or</div>
        <button class="google-login-btn" id="google-login-btn" onclick="initGoogleLogin('login')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Continue with Google
        </button>
        <div class="small-note">Don't have an account? <button type="button" class="switch-tab" data-target="register">Sign up</button></div>
    </div>

    <div class="modal-sheet" id="register-modal">
        <div class="modal-drag-handle"></div>
        <button class="close-modal" id="close-register">✕</button>
        <h2>Create account</h2>
        <form id="register-form" method="post" action="register.php">
            <label>Full name</label>
            <input type="text" name="name" placeholder="John Doe" required>
            <label>Phone</label>
            <input type="tel" name="phone" placeholder="+251 912 345 678" required>
            <label>Email</label>
            <input type="email" name="email" placeholder="you@example.com" required>
            <label>Password</label>
            <input type="password" name="password" placeholder="At least 6 characters" required>
            <input type="hidden" name="role" value="customer">
            <input type="hidden" name="latitude" id="register-lat" value="7.0621">
            <input type="hidden" name="longitude" id="register-lng" value="38.4760">
            <input type="hidden" name="location_name" id="register-loc" value="Hawassa, Ethiopia">
            <button class="primary-btn" type="submit">Create account</button>
        </form>
        <div class="social-divider">or</div>
        <button class="google-login-btn" id="google-signup-btn" onclick="initGoogleLogin('register')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Continue with Google
        </button>
        <div class="small-note">Already have an account? <button type="button" class="switch-tab" data-target="login">Sign in</button></div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
    // Initialize Google Sign-In
    window.onload = function() {
        google.accounts.id.initialize({
            client_id: '1234567890-abcdefghijk.apps.googleusercontent.com', // Replace with your Google Client ID
            callback: handleCredentialResponse
        });
        
        // Render login button
        google.accounts.id.renderButton(
            document.getElementById('google-login-btn'),
            { theme: 'outline', size: 'large' }
        );
    };

    function handleCredentialResponse(response) {
        if (window.handleGoogleSignIn) {
            window.handleGoogleSignIn(response);
        }
    }

    // Custom Google login trigger
    function initGoogleLogin(mode) {
        const btn = mode === 'login' ? document.getElementById('google-login-btn') : document.getElementById('google-signup-btn');
        if (btn) {
            btn.click();
        }
    }
    </script>
</body>
</html>
