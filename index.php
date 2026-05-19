<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/includes/auth.php';
} catch (Exception $e) {
    die('Error loading auth: ' . $e->getMessage());
}

if (is_logged_in()) {
    $role = $_SESSION['user']['role'];
    
    // Redirect based on role
    if ($role === 'restaurant') {
        redirect('/restaurant/dashboard.php');
    }
    if ($role === 'admin') {
        redirect('/admin/dashboard.php');
    }
    // Customer role
    redirect('/customer/dashboard.php');
}

require_once __DIR__ . '/includes/db.php';
$topRestaurants = $pdo->query("SELECT id, name, cuisine_type, location, rating FROM restaurants WHERE status = 'active' ORDER BY rating DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gebeta · Food Delivery in Hawassa</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#FC8019">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing-hero-fix.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <header class="hero-header">
        <nav class="hero-nav">
            <div class="brand"><span class="brand-mark">G</span><strong>Gebeta</strong></div>
            <div class="nav-actions">
                <button class="sign-btn" id="open-login">Sign In</button>
                <button class="sign-btn sign-btn-alt" id="open-register">Sign Up</button>
            </div>
        </nav>

        <div class="hero-body">
            <div class="hero-copy">
                <p class="eyebrow hero-eyebrow">$0 delivery fee on first order</p>
                <h1>Order from Gebeta cafes and restaurants.</h1>
                <p>Find injera, buna and more with fast delivery.</p>

                <form id="hero-search-form" action="/customer/dashboard.php" method="get" class="hero-search-form">
                    <input id="hero-search-input" name="q" type="search" placeholder="Enter delivery address" aria-label="Enter delivery address" required>
                    <button class="primary-btn" type="submit">→</button>
                </form>

                <div class="hero-action-row">
                    <button type="button" class="pill-button" onclick="openModal('login-modal')">Sign in for saved address</button>
                    <button type="button" class="pill-button" id="use-location-btn">Use current location</button>
                </div>
            </div>

            <div class="hero-visual">
                <div class="hero-card-image hero-card-image--big">
                    <img src="/assets/images/food/doro-wat.jpg" alt="Delicious food">
                </div>
                <div class="hero-card-image hero-card-image--small">
                    <img src="/assets/images/food/coffee.jpg" alt="Fresh food items">
                </div>
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
        <section class="hero-feature-row">
            <div class="hero-feature-card">
                <div class="feature-icon">🚚</div>
                <h3>Fast delivery</h3>
                <p>Order from nearby restaurants and get food in under 30 minutes.</p>
            </div>
            <div class="hero-feature-card">
                <div class="feature-icon">⭐</div>
                <h3>Top picks</h3>
                <p>Browse highly rated restaurants, groceries, and convenience stores.</p>
            </div>
            <div class="hero-feature-card">
                <div class="feature-icon">💳</div>
                <h3>Secure checkout</h3>
                <p>Save your details and pay safely in just a few taps.</p>
            </div>
        </section>

        <section class="promo-section">
            <div class="promo-panel promo-panel-left">
                <div>
                    <span class="promo-label">Gebeta Plus</span>
                    <h2>Delivery for less</h2>
                    <p>Enjoy lower delivery fees, priority service, and exclusive member offers on every order.</p>
                    <button class="primary-btn" type="button">Join Gebeta Plus</button>
                </div>
                <img src="/assets/images/food/pizza.jpg" alt="Featured meal">
            </div>

            <div class="promo-panel promo-panel-right">
                <div>
                    <span class="promo-label">Convenience</span>
                    <h2>Grocery essentials in minutes</h2>
                    <p>Get fresh produce, snacks, and daily essentials delivered straight to your door.</p>
                    <button class="primary-btn" type="button">Shop groceries</button>
                </div>
                <img src="/assets/images/food/coffee.jpg" alt="Convenience items">
            </div>
        </section>

        <section class="restaurant-showcase">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Popular this week</p>
                    <h2>Top rated restaurants</h2>
                </div>
            </div>
            <div class="cards-grid">
                <?php if (empty($topRestaurants)): ?>
                    <div class="empty-state">No featured restaurants are available right now.</div>
                <?php endif; ?>
                <?php foreach ($topRestaurants as $restaurant): ?>
                    <a href="/customer/restaurant.php?id=<?= htmlspecialchars($restaurant['id']) ?>" class="restaurant-card">
                        <div class="restaurant-image">
                            <img src="/assets/images/food/injera.jpg" alt="<?= htmlspecialchars($restaurant['name']) ?>">
                            <div class="restaurant-overlay"></div>
                            <div class="restaurant-labels">
                                <span class="rating-pill">Rating <?= htmlspecialchars(number_format($restaurant['rating'], 1)) ?></span>
                            </div>
                        </div>
                        <div class="restaurant-card-content">
                            <h3><?= htmlspecialchars($restaurant['name']) ?></h3>
                            <p><?= htmlspecialchars($restaurant['cuisine_type']) ?> · <?= htmlspecialchars($restaurant['location']) ?></p>
                            <div class="restaurant-details">
                                <span>Top pick</span>
                                <span><?= htmlspecialchars(number_format($restaurant['rating'], 1)) ?> rating</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
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
            <a href="/forgot-password.php" style="font-size:13px;color:var(--primary-orange);text-decoration:none;display:block;margin-top:-8px;margin-bottom:12px;">Forgot password?</a>
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
        const clientId = '<?php echo GOOGLE_CLIENT_ID; ?>';
        if (clientId) {
            google.accounts.id.initialize({
                client_id: clientId,
                callback: handleCredentialResponse
            });
            
            // Render login button
            google.accounts.id.renderButton(
                document.getElementById('google-login-btn'),
                { theme: 'outline', size: 'large' }
            );
        }
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

    document.addEventListener('DOMContentLoaded', () => {
        const useLocationBtn = document.getElementById('use-location-btn');
        const searchInput = document.getElementById('hero-search-input');
        const searchForm = document.getElementById('hero-search-form');

        if (useLocationBtn && searchInput && searchForm) {
            useLocationBtn.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    alert('Geolocation is not supported by your browser.');
                    return;
                }

                useLocationBtn.disabled = true;
                useLocationBtn.textContent = 'Locating…';

                navigator.geolocation.getCurrentPosition(position => {
                    searchInput.value = `Current location`;
                    searchForm.submit();
                }, () => {
                    useLocationBtn.disabled = false;
                    useLocationBtn.textContent = 'Use current location';
                    alert('Unable to retrieve location. Please try again.');
                }, {
                    timeout: 10000,
                });
            });
        }
    });
    </script>
</body>
</html>
