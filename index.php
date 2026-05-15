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
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#FC8019">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Google Sign-In SDK -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <header class="hero-header">
        <div class="topbar">
            <div class="brand">
                <span class="brand-mark">G</span>
                <div>
                    <p class="eyebrow">Food delivery in Addis Ababa</p>
                    <strong>Gebeta</strong>
                </div>
            </div>
            <div class="hero-actions">
                <button class="pill-button" id="open-login">Login</button>
            </div>
        </div>

        <div class="hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">Fresh meals from local kitchens</p>
                <h1>Order food from the best restaurants near you.</h1>
                <p class="hero-subtitle">Browse nearby kitchens, add favorites to your cart, and track every step until delivery.</p>
                <div class="hero-buttons">
                    <button class="primary-btn" id="open-register">Start ordering</button>
                    <a href="/customer/dashboard.php" class="secondary-btn">Browse restaurants</a>
                </div>
                <div class="hero-stats">
                    <div><strong>500+</strong><span>Restaurants</span></div>
                    <div><strong>30 min</strong><span>Average delivery</span></div>
                    <div><strong>Live</strong><span>Order tracking</span></div>
                </div>
            </div>
            <div class="hero-card">
                <div class="hero-card-inner">
                    <div class="hero-card-visual">
                        <span>🍽️</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-features">
            <div class="hero-feature">
                <div class="hero-feature-icon">⚡</div>
                <div>
                    <h3>Fast delivery</h3>
                    <p>Food from nearby kitchens delivered hot and on time.</p>
                </div>
            </div>
            <div class="hero-feature">
                <div class="hero-feature-icon">🛍️</div>
                <div>
                    <h3>Easy ordering</h3>
                    <p>Save your preferences and checkout in seconds.</p>
                </div>
            </div>
            <div class="hero-feature">
                <div class="hero-feature-icon">⭐</div>
                <div>
                    <h3>Top-rated spots</h3>
                    <p>Discover the most loved restaurants in your neighborhood.</p>
                </div>
            </div>
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
        <div class="landing-card-grid">
            <article class="landing-card">
                <div class="landing-card-thumb">🥘</div>
                <div class="landing-card-copy">
                    <h3>Yod Abyssinia</h3>
                    <p>Ethiopian • 30-40 min</p>
                </div>
            </article>
            <article class="landing-card">
                <div class="landing-card-thumb">☕</div>
                <div class="landing-card-copy">
                    <h3>Café Kebede</h3>
                    <p>Coffee & snacks • 18-25 min</p>
                </div>
            </article>
            <article class="landing-card">
                <div class="landing-card-thumb">🥗</div>
                <div class="landing-card-copy">
                    <h3>Fresh Bites</h3>
                    <p>Healthy bowls • 20-30 min</p>
                </div>
            </article>
        </div>
    </main>

    <div id="modal-overlay"></div>

    <div class="modal-sheet" id="login-modal">
        <div class="modal-drag-handle"></div>
        <button class="close-modal" id="close-login">✕</button>
        <h2>Login</h2>
        <form id="login-form" method="post" action="login.php">
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <input type="hidden" name="latitude" id="login-lat" value="9.145">
            <input type="hidden" name="longitude" id="login-lng" value="38.7335">
            <input type="hidden" name="location_name" id="login-loc" value="Addis Ababa, Ethiopia">
            <div class="location-row" id="login-location-row" onclick="openLocationModal('login')">
                <span class="location-icon">📍</span>
                <span id="login-location-text">Addis Ababa, Ethiopia</span>
                <span class="location-arrow">›</span>
            </div>
            <button class="primary-btn" type="submit">Login</button>
        </form>
        <div class="social-divider">Continue with</div>
        <button class="google-login-btn" id="google-login-btn" onclick="initGoogleLogin('login')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Google
        </button>
        <div class="small-note">No account? <button type="button" class="switch-tab" data-target="register">Create account</button></div>
    </div>

    <div class="modal-sheet" id="location-modal">
        <div class="modal-drag-handle"></div>
        <button class="close-modal" id="close-location">✕</button>
        <h2>Select your location</h2>
        <div class="location-options">
            <button class="location-option" onclick="setLocation('Addis Ababa, Ethiopia', 9.145, 38.7335)">
                <span class="location-icon">📍</span>
                <div>
                    <strong>Addis Ababa, Ethiopia</strong>
                    <p>Current location</p>
                </div>
            </button>
            <button class="location-option" onclick="setLocation('Bole, Addis Ababa', 8.991, 38.792)">
                <span class="location-icon">🏢</span>
                <div>
                    <strong>Bole, Addis Ababa</strong>
                    <p>Business district</p>
                </div>
            </button>
            <button class="location-option" onclick="setLocation('Kazanchis, Addis Ababa', 9.011, 38.772)">
                <span class="location-icon">🏠</span>
                <div>
                    <strong>Kazanchis, Addis Ababa</strong>
                    <p>Residential area</p>
                </div>
            </button>
        </div>
        <div class="location-manual">
            <label>Or enter manually</label>
            <input type="text" id="manual-location" placeholder="Enter your location">
            <button class="secondary-btn" onclick="setManualLocation()">Use this location</button>
        </div>
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
