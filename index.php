<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if user is already logged in
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? 'customer';
    
    if ($role === 'restaurant') {
        header('Location: /restaurant/dashboard.php');
        exit();
    }
    if ($role === 'admin') {
        header('Location: /admin/dashboard.php');
        exit();
    }
    if ($role === 'delivery') {
        header('Location: /delivery/dashboard.php');
        exit();
    }
    header('Location: /customer/dashboard.php');
    exit();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Check for login error from redirect
$loginError = flash_get('login_error');
$registerError = flash_get('register_error');
$forgotError = flash_get('forgot_error');
 
// Safe stats fetching with fallbacks
$totalRestaurants = 0;
$totalOrders = 15234;
$totalCustomers = 8756;
$avgRating = 4.8;

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE status = 'active'");
    $totalRestaurants = $stmt->fetchColumn() ?: 125;
} catch (PDOException $e) { 
    error_log('Error fetching restaurants count: ' . $e->getMessage()); 
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $totalOrders = $stmt->fetchColumn() ?: 15234;
} catch (PDOException $e) { 
    error_log('Error fetching orders count: ' . $e->getMessage()); 
}

try {
    // Check if role column exists
    $checkStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($checkStmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
        $totalCustomers = $stmt->fetchColumn() ?: 8756;
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $totalCustomers = $stmt->fetchColumn() ?: 8756;
    }
} catch (PDOException $e) { 
    error_log('Error fetching customers count: ' . $e->getMessage()); 
}

try {
    $stmt = $pdo->query("SELECT AVG(rating) FROM restaurants WHERE rating > 0");
    $avgRating = number_format($stmt->fetchColumn() ?: 4.8, 1);
} catch (PDOException $e) { 
    error_log('Error fetching avg rating: ' . $e->getMessage()); 
}

$topRestaurants = [];
$allFoodImages = [
    'doro-wat.jpg', 'burger.jpg', 'coffee.jpg', 'injera.jpg', 'tibs.jpg'
];

$restaurantImages = [
    'Yod Abyssinia' => 'doro-wat.jpg',
    'Kategna' => 'kitfo.jpg',
    'Tomoca Coffee' => 'coffee.jpg',
    'Pizza Hut' => 'burger.jpg',
    'Kaldi\'s Coffee' => 'injera.jpg',
    'Mama\'s Kitchen' => 'doro-wat.jpg',
];

try {
    // Check if delivery_time column exists
    $hasDeliveryTime = false;
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM restaurants LIKE 'delivery_time'");
        $hasDeliveryTime = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) { }
    
    // Build query based on available columns
    if ($hasDeliveryTime) {
        $stmt = $pdo->prepare("SELECT id, name, cuisine_type, location, rating, delivery_time, delivery_fee FROM restaurants WHERE status = ? ORDER BY rating DESC LIMIT 6");
    } else {
        $stmt = $pdo->prepare("SELECT id, name, cuisine_type, location, rating, delivery_fee FROM restaurants WHERE status = ? ORDER BY rating DESC LIMIT 6");
    }
    $stmt->execute(['active']);
    $topRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add delivery_time if missing
    foreach ($topRestaurants as &$r) {
        if (!isset($r['delivery_time']) || empty($r['delivery_time'])) {
            $r['delivery_time'] = rand(20, 35) . '-' . rand(35, 50);
        }
        if (!isset($r['delivery_fee'])) {
            $r['delivery_fee'] = rand(0, 30);
        }
    }
} catch (PDOException $e) { }

// Fallback data if no restaurants in database
if (empty($topRestaurants)) {
    $topRestaurants = [
        ['id' => 1, 'name' => 'Yod Abyssinia', 'cuisine_type' => 'Ethiopian', 'location' => 'Piassa', 'rating' => 4.8, 'delivery_time' => '25-35', 'delivery_fee' => 0],
        ['id' => 2, 'name' => 'Kategna', 'cuisine_type' => 'Ethiopian', 'location' => 'Bole', 'rating' => 4.7, 'delivery_time' => '20-30', 'delivery_fee' => 20],
        ['id' => 3, 'name' => 'Tomoca Coffee', 'cuisine_type' => 'Cafe', 'location' => 'Piazza', 'rating' => 4.7, 'delivery_time' => '15-25', 'delivery_fee' => 0],
        ['id' => 4, 'name' => 'Pizza Hut', 'cuisine_type' => 'Pizza', 'location' => 'Bole', 'rating' => 4.5, 'delivery_time' => '30-40', 'delivery_fee' => 30],
        ['id' => 5, 'name' => 'Kaldi\'s Coffee', 'cuisine_type' => 'Cafe', 'location' => 'Piazza', 'rating' => 4.6, 'delivery_time' => '10-20', 'delivery_fee' => 15],
        ['id' => 6, 'name' => 'Mama\'s Kitchen', 'cuisine_type' => 'International', 'location' => 'Megenagna', 'rating' => 4.4, 'delivery_time' => '25-35', 'delivery_fee' => 25],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#FC8019">
    <title>Gebeta · Premium Food Delivery in Hawassa</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- RESPONSIVE CSS -->
    <link rel="stylesheet" href="/assets/css/responsive.css">
    
    <script src="/assets/js/script.js" defer></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #FC8019;
            --primary-dark: #E56B0F;
            --primary-light: #FFF5ED;
            --secondary: #FFB800;
            --success: #10B981;
            --danger: #EF4444;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: white;
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-60px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(60px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes cartShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-4px); }
            75% { transform: translateX(4px); }
        }
        
        @keyframes popupSlide {
            from { opacity: 0; transform: translate(-50%, -60%) scale(0.9); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }
        
        @keyframes adSlideIn {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .animate-fadeUp { animation: fadeInUp 0.8s ease forwards; }
        .animate-fadeLeft { animation: fadeInLeft 0.8s ease forwards; }
        .animate-fadeRight { animation: fadeInRight 0.8s ease forwards; }
        .animate-float { animation: float 4s ease-in-out infinite; }
        .animate-pulse { animation: pulse 2s infinite; }
        
        .delay-1 { animation-delay: 0.15s; opacity: 0; }
        .delay-2 { animation-delay: 0.3s; opacity: 0; }
        .delay-3 { animation-delay: 0.45s; opacity: 0; }
        .delay-4 { animation-delay: 0.6s; opacity: 0; }
        
        /* Hero Header */
        .hero-header {
            background: linear-gradient(135deg, #FFF8F0 0%, #FFFFFF 50%, #FFF5ED 100%);
            padding: 1rem 2rem 6rem;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        .hero-header::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 60%;
            height: 120%;
            background: radial-gradient(circle, rgba(252,128,25,0.08) 0%, transparent 70%);
            animation: float 8s ease-in-out infinite;
        }
        
        .hero-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0.5rem 0;
            position: relative;
            z-index: 10;
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .brand:hover { transform: scale(1.05); }
        
        .brand-mark {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.75rem;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(252,128,25,0.3);
        }
        
        .brand strong {
            font-size: 1.75rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .nav-actions { display: flex; gap: 1rem; }
        
        .sign-btn {
            padding: 0.7rem 1.8rem;
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .sign-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--primary);
            transition: left 0.3s;
            z-index: -1;
        }
        
        .sign-btn:hover {
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        
        .sign-btn:hover::before { left: 0; }
        
        .sign-btn-alt {
            background: var(--primary);
            color: white;
            border: none;
        }
        
        .sign-btn-alt::before { background: var(--primary-dark); }
        
        /* Hero Body */
        .hero-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            max-width: 1280px;
            margin: 4rem auto 0;
            align-items: center;
            position: relative;
            z-index: 5;
        }
        
        .eyebrow {
            display: inline-block;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1rem;
            letter-spacing: 3px;
            background: rgba(252,128,25,0.1);
            padding: 0.3rem 1rem;
            border-radius: 40px;
        }
        
        .hero-copy h1 {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gray-800), var(--primary-dark));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .hero-copy p {
            font-size: 1.1rem;
            color: var(--gray-500);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .hero-search-form {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            background: white;
            padding: 0.3rem;
            border-radius: 60px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        
        .hero-search-form input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 60px;
            font-size: 1rem;
            background: transparent;
        }
        
        .hero-search-form input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(252,128,25,0.2);
        }
        
        .hero-search-form .primary-btn {
            padding: 1rem 2rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 60px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .hero-search-form .primary-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.02);
        }
        
        .hero-action-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .pill-button {
            padding: 0.6rem 1.2rem;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 40px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .pill-button:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }
        
        .hero-visual {
            position: relative;
            min-height: 450px;
        }
        
        .hero-card-image {
            position: absolute;
            border-radius: 2rem;
            overflow: hidden;
            box-shadow: 0 30px 50px rgba(0,0,0,0.2);
            transition: all 0.4s;
            cursor: pointer;
        }
        
        .hero-card-image:hover {
            transform: scale(1.03) translateY(-8px);
        }
        
        .hero-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s;
        }
        
        .hero-card-image:hover img { transform: scale(1.08); }
        
        .hero-card-image--big {
            width: 320px;
            height: 380px;
            right: 0;
            top: 0;
            animation: float 4s ease-in-out infinite;
        }
        
        .hero-card-image--small {
            width: 220px;
            height: 270px;
            left: 0;
            bottom: 0;
            animation: float 5s ease-in-out infinite reverse;
        }
        
        /* Stats Section */
        .stats-section {
            background: var(--gray-900);
            padding: 4rem 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .stats-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--primary));
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
        
        .stat-card {
            padding: 2rem;
            background: rgba(255,255,255,0.05);
            border-radius: 1.5rem;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            background: rgba(255,255,255,0.1);
            border-color: var(--primary);
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
            font-family: monospace;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--gray-400);
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 500;
        }
        
        /* Restaurant Showcase */
        .restaurant-showcase {
            max-width: 1200px;
            margin: 5rem auto;
            padding: 0 2rem;
        }
        
        .section-header-center {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-badge {
            display: inline-block;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: var(--primary);
            font-weight: 700;
            letter-spacing: 2px;
            background: var(--primary-light);
            padding: 0.3rem 1rem;
            border-radius: 40px;
            margin-bottom: 1rem;
        }
        
        .section-header-center h2 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--gray-800);
            margin-bottom: 1rem;
        }
        
        .section-header-center p {
            color: var(--gray-500);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .filter-chips {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            margin: 1rem 0 1.5rem;
            padding-bottom: 0.5rem;
        }
        
        .filter-chips::-webkit-scrollbar { display: none; }
        
        .chip {
            padding: 0.4rem 1rem;
            background: var(--gray-100);
            border-radius: 40px;
            font-size: 0.75rem;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .chip.active {
            background: var(--primary);
            color: white;
        }
        
        .restaurants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
        }
        
        .restaurant-card {
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            border: 1px solid var(--gray-200);
            transition: all 0.4s;
            position: relative;
            cursor: pointer;
        }
        
        .restaurant-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 45px rgba(0,0,0,0.15);
        }
        
        .restaurant-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        
        .restaurant-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s;
        }
        
        .restaurant-card:hover .restaurant-image img { transform: scale(1.1); }
        
        .restaurant-rating {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0,0,0,0.8);
            color: var(--secondary);
            padding: 0.3rem 0.8rem;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .popular-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(0,0,0,0.8);
            color: var(--secondary);
            padding: 0.3rem 0.8rem;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .delivery-time-badge {
            position: absolute;
            bottom: 1rem;
            left: 1rem;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .restaurant-info {
            padding: 1.5rem;
            background: white;
        }
        
        .restaurant-info h3 {
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
            color: var(--gray-800);
        }
        
        .restaurant-info p {
            font-size: 0.85rem;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .restaurant-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin: 0.5rem 0;
            font-size: 0.7rem;
            color: var(--gray-500);
        }
        
        .restaurant-meta span {
            display: flex;
            align-items: center;
            gap: 0.2rem;
        }
        
        .free-delivery {
            color: var(--success);
            font-weight: 600;
        }
        
        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--gray-100);
        }
        
        .order-price {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-800);
        }
        
        .order-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 600;
            font-size: 0.75rem;
            cursor: pointer;
            padding: 0.3rem 0.8rem;
            border-radius: 40px;
            transition: all 0.3s;
        }
        
        .order-btn:hover {
            background: var(--primary);
            color: white;
        }
        
        /* Floating Cart */
        .floating-cart {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary);
            color: white;
            padding: 0.8rem 1.2rem;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 99;
            transition: all 0.3s;
        }
        
        .floating-cart:hover {
            transform: scale(1.05);
        }
        
        /* Download App */
        .download-section {
            background: var(--gray-900);
            padding: 5rem 2rem;
            text-align: center;
        }
        
        .download-content h2 {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 1rem;
        }
        
        .download-content p {
            color: var(--gray-400);
            margin-bottom: 2rem;
        }
        
        .app-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .app-btn {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            background: rgba(255,255,255,0.1);
            padding: 1rem 2rem;
            border-radius: 1rem;
            text-decoration: none;
            color: white;
            transition: all 0.3s;
        }
        
        .app-btn:hover {
            background: var(--primary);
            transform: translateY(-5px);
        }
        
        .app-btn i { font-size: 2rem; }
        
        /* Footer */
        .footer {
            background: var(--gray-900);
            padding: 3rem 2rem 2rem;
            border-top: 1px solid var(--gray-800);
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }
        
        .footer-col h4 {
            color: white;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .footer-col p {
            color: var(--gray-400);
            font-size: 0.85rem;
            line-height: 1.6;
        }
        
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 0.5rem; }
        .footer-col ul li a {
            color: var(--gray-400);
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        .footer-col ul li a:hover { color: var(--primary); }
        
        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .social-icons a {
            width: 38px;
            height: 38px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .social-icons a:hover {
            background: var(--primary);
            transform: translateY(-3px) rotate(360deg);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid var(--gray-800);
            color: var(--gray-500);
            font-size: 0.8rem;
        }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }
        
        .modal-overlay.active { display: block; }
        
        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 2rem;
            width: 90%;
            max-width: 450px;
            z-index: 1001;
            overflow: hidden;
        }
        
        .modal.active { display: block; }
        
        .modal-header {
            padding: 1.5rem 1.5rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 { font-size: 1.5rem; color: var(--gray-800); }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-500);
        }
        
        .modal-body { padding: 1.5rem; }
        
        .form-group { margin-bottom: 1rem; }
        
        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.3rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 0.8rem;
            font-size: 0.9rem;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .modal-btn {
            width: 100%;
            padding: 0.8rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.8rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .modal-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        /* Form Refinements */
        .modal-alert {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            border: 1px solid transparent;
        }
        .modal-alert-error {
            background: #FEE2E2;
            color: #DC2626;
            border-color: #FECACA;
        }
        .password-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-toggle {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: var(--gray-400);
        }
        .form-hint { font-size: 11px; color: var(--gray-500); margin-top: 4px; display: block; }
        
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
            color: var(--gray-400);
        }
        
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: var(--gray-200);
        }
        
        .divider::before { left: 0; }
        .divider::after { right: 0; }
        
        .google-btn {
            width: 100%;
            padding: 0.8rem;
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .google-btn:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        
        .modal-footer-text {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.8rem;
            color: var(--gray-500);
        }
        
        .modal-footer-text button {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
        }
        
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: 0.8rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 2000;
            border-left: 4px solid var(--success);
            animation: fadeInUp 0.3s ease;
        }
        
        .toast.error {
            border-left-color: var(--danger);
        }
        
        @media (max-width: 768px) {
            .hero-header { padding: 1rem 1rem 4rem; }
            .hero-body { grid-template-columns: 1fr; gap: 2rem; }
            .hero-visual { display: none; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
            .restaurants-grid { grid-template-columns: 1fr; }
            .footer-content { grid-template-columns: 1fr; text-align: center; }
            .hero-copy h1 { font-size: 2rem; }
            .section-header-center h2 { font-size: 1.8rem; }
            .social-icons { justify-content: center; }
            .floating-cart { bottom: 80px; }
            .ad-popup { right: 15px; left: 15px; width: auto; bottom: 150px; }
            .welcome-popup { width: 95%; max-height: 90vh; overflow-y: auto; }
            .welcome-popup .popup-header { padding: 1.5rem 1rem; }
            .welcome-popup .popup-body { padding: 1.5rem 1rem; }
            .popup-actions { flex-direction: column; gap: 0.8rem; }
            .popup-actions button { width: 100%; }
        }

        /* Welcome Popup */
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(8px);
            z-index: 1000;
            animation: fadeInUp 0.3s ease;
        }
        
        .popup-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .welcome-popup {
            background: white;
            border-radius: 2rem;
            max-width: 450px;
            width: 90%;
            overflow: hidden;
            animation: popupSlide 0.4s ease;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }
        
        .welcome-popup .popup-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 2rem;
            text-align: center;
            color: white;
            position: relative;
        }
        
        .welcome-popup .popup-header h3 {
            font-size: 1.8rem;
            margin-bottom: 0.3rem;
        }
        
        .welcome-popup .popup-header p {
            opacity: 0.9;
        }
        
        .welcome-popup .popup-body {
            padding: 2rem;
            text-align: center;
        }
        
        .welcome-popup .popup-body p {
            color: var(--gray-600);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .welcome-popup .offer-badge {
            background: var(--primary-light);
            color: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: 40px;
            display: inline-block;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .popup-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
        }
        
        .popup-actions button {
            padding: 0.8rem 1.5rem;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .popup-actions .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
        }
        
        .popup-actions .btn-primary:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }
        
        .popup-actions .btn-secondary {
            background: white;
            color: var(--gray-600);
            border: 1px solid var(--gray-200);
        }
        
        .popup-actions .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        /* Ad Popup (Bottom Right) */
        .ad-popup {
            position: fixed;
            bottom: 100px;
            right: 30px;
            background: white;
            border-radius: 1rem;
            width: 320px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            z-index: 1999;
            animation: adSlideIn 0.5s ease;
            display: none;
            overflow: hidden;
        }
        
        .ad-popup.active {
            display: block;
        }
        
        .ad-popup .ad-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 0.8rem 1rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ad-popup .ad-header h4 {
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .ad-popup .ad-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1.2rem;
            transition: transform 0.3s;
        }
        
        .ad-popup .ad-close:hover {
            transform: rotate(90deg);
        }
        
        .ad-popup .ad-body {
            padding: 1.2rem;
            text-align: center;
        }
        
        .ad-popup .ad-discount {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            animation: pulse 2s infinite;
        }
        
        .ad-popup .ad-body h3 {
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
            color: var(--gray-800);
        }
        
        .ad-popup .ad-body p {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-bottom: 1rem;
        }
        
        .ad-popup .ad-code {
            background: var(--primary-light);
            padding: 0.5rem;
            border-radius: 0.5rem;
            font-family: monospace;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.8rem;
            font-size: 1.1rem;
        }
        
        .ad-popup .ad-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.6rem;
            border-radius: 0.5rem;
            width: 100%;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .ad-popup .ad-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Welcome Popup -->
    <div class="popup-overlay" id="welcomeOverlay">
        <div class="welcome-popup">
            <div class="popup-header">
                <h3>🎉 Welcome to Gebeta!</h3>
                <p>Hawassa's #1 Food Delivery</p>
            </div>
            <div class="popup-body">
                <div class="offer-badge">✨ FIRST ORDER OFFER ✨</div>
                <p>Get <strong>50% OFF</strong> on your first order + <strong>FREE delivery</strong> on orders over 200 Birr!</p>
                <div class="popup-actions">
                    <button class="btn-primary" onclick="dismissWelcomeAndSignup()">Start Ordering →</button>
                    <button class="btn-secondary" onclick="closeWelcomePopup()">Maybe Later</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Ad Popup -->
    <div class="ad-popup" id="adPopup">
        <div class="ad-header">
            <h4><i class="fas fa-fire"></i> Limited Time Offer!</h4>
            <button class="ad-close" onclick="closeAdPopup()">&times;</button>
        </div>
        <div class="ad-body">
            <div class="ad-discount">50% OFF</div>
            <h3>🔥 FLASH SALE! 🔥</h3>
            <p>Order now and get 50% OFF + FREE delivery on orders over 200 Birr</p>
            <div class="ad-code">Use Code: <strong>GEBETA50</strong></div>
            <button class="ad-btn" onclick="orderNow()">Order Now →</button>
        </div>
    </div>

    <header class="hero-header">
        <nav class="hero-nav">
            <div class="brand">
                <div class="brand-mark">G</div>
                <strong>Gebeta</strong>
            </div>
            <div class="nav-actions">
                <button class="sign-btn" onclick="openModal('login-modal')">Sign In</button>
                <button class="sign-btn sign-btn-alt" onclick="openModal('register-modal')">Sign Up</button>
            </div>
        </nav>

        <div class="hero-body">
            <div class="hero-copy">
                <span class="eyebrow animate-fadeLeft">✨ Premium Food Delivery</span>
                <h1 class="animate-fadeLeft delay-1">Experience the Spirit of<br>Gebeta in Hawassa</h1>
                <p class="animate-fadeLeft delay-2">Discover local Ethiopian cuisine and international favorites.<br>Fast delivery, best prices.</p>

                <form action="/customer/dashboard.php" method="get" class="hero-search-form animate-fadeLeft delay-3">
                    <input type="text" name="q" placeholder="Enter your delivery address..." required>
                    <button type="submit" class="primary-btn">Find Food →</button>
                </form>

                <div class="hero-action-row animate-fadeLeft delay-4">
                    <button class="pill-button" onclick="openModal('login-modal')">
                        <i class="fas fa-key"></i> Sign in for saved address
                    </button>
                    <button class="pill-button" id="use-location-btn">
                        <i class="fas fa-map-marker-alt"></i> Use current location
                    </button>
                </div>
            </div>

            <div class="hero-visual">
                <div class="hero-card-image hero-card-image--big animate-float">
                    <img src="/assets/images/food/tibs.jpg" alt="Tibs">
                </div>
                <div class="hero-card-image hero-card-image--small animate-float">
                    <img src="/assets/images/food/injera.jpg" alt="Ayinet">
                </div>
            </div>
        </div>
    </header>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="stat-restaurants" data-target="<?= $totalRestaurants ?>">0</div>
                <div class="stat-label">Restaurant Partners</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-orders" data-target="<?= $totalOrders ?>">0</div>
                <div class="stat-label">Happy Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-delivery" data-target="30">0</div>
                <div class="stat-label">Average Delivery (min)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-rating" data-target="<?= $avgRating ?>">0.0</div>
                <div class="stat-label">Customer Rating ⭐</div>
            </div>
        </div>
    </section>

    <!-- Restaurant Showcase -->
    <div class="restaurant-showcase">
        <div class="section-header-center animate-fadeUp">
            <span class="section-badge">Top Rated</span>
            <h2>Popular restaurants in Hawassa</h2>
            <p>Discover the most loved spots by our community</p>
        </div>
        
        <div class="filter-chips">
            <span class="chip active" data-filter="all" onclick="filterRestaurants('all')">All</span>
            <span class="chip" data-filter="popular" onclick="filterRestaurants('popular')">🔥 Popular</span>
            <span class="chip" data-filter="fast" onclick="filterRestaurants('fast')">⚡ Fastest</span>
            <span class="chip" data-filter="free" onclick="filterRestaurants('free')">🚚 Free Delivery</span>
            <span class="chip" data-filter="rating" onclick="filterRestaurants('rating')">⭐ Top Rated</span>
        </div>

        <div class="restaurants-grid" id="restaurantsGrid">
            <?php foreach ($topRestaurants as $index => $restaurant): 
                $image = $restaurantImages[$restaurant['name']] ?? $allFoodImages[array_rand($allFoodImages)];
                $isPopular = $restaurant['rating'] >= 4.7;
                $deliveryTime = $restaurant['delivery_time'] ?? '25-35';
                $deliveryFee = $restaurant['delivery_fee'] ?? 0;
                $isFast = strpos($deliveryTime, '15') !== false || strpos($deliveryTime, '20') !== false;
            ?>
                <div class="restaurant-card" data-name="<?= strtolower($restaurant['name']) ?>" data-cuisine="<?= strtolower($restaurant['cuisine_type']) ?>" data-rating="<?= $restaurant['rating'] ?>" data-fee="<?= $deliveryFee ?>" data-popular="<?= $isPopular ? '1' : '0' ?>" data-fast="<?= $isFast ? '1' : '0' ?>" onclick="goToRestaurant(<?= $restaurant['id'] ?>)">
                    <div class="restaurant-image">
                        <img src="/assets/images/food/<?= $image ?>" alt="<?= htmlspecialchars($restaurant['name']) ?>">
                        <?php if ($isPopular): ?>
                            <div class="popular-badge">🔥 Popular</div>
                        <?php endif; ?>
                        <div class="restaurant-rating">
                            <i class="fas fa-star" style="color: #FFB800;"></i> <?= number_format($restaurant['rating'], 1) ?>
                        </div>
                        <div class="delivery-time-badge">
                            <i class="fas fa-clock"></i> <?= $deliveryTime ?> min
                        </div>
                    </div>
                    <div class="restaurant-info">
                        <h3><?= htmlspecialchars($restaurant['name']) ?></h3>
                        <p><i class="fas fa-utensils"></i> <?= htmlspecialchars($restaurant['cuisine_type']) ?> • <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($restaurant['location']) ?></p>
                        <div class="restaurant-meta">
                            <span><i class="fas fa-star" style="color: #FFB800;"></i> <?= number_format($restaurant['rating'], 1) ?></span>
                            <span><i class="fas fa-clock"></i> <?= $deliveryTime ?> min</span>
                            <span><i class="fas fa-motorcycle"></i> <?= $deliveryFee == 0 ? 'Free' : $deliveryFee . ' Birr' ?></span>
                        </div>
                        <div class="order-footer">
                            <span class="order-price">From <?= $deliveryFee == 0 ? 'Free delivery' : $deliveryFee . ' Birr delivery' ?></span>
                            <button class="order-btn" onclick="event.stopPropagation(); addToCart('<?= htmlspecialchars($restaurant['name']) ?>')">Order →</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Download App -->
    <section class="download-section">
        <div class="download-content animate-fadeUp">
            <h2>Download the Gebeta App</h2>
            <p>Get exclusive offers, faster checkout, and real-time tracking</p>
            <div class="app-buttons">
                <a href="#" class="app-btn"><i class="fab fa-apple"></i> App Store</a>
                <a href="#" class="app-btn"><i class="fab fa-google-play"></i> Google Play</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-col">
                <h4>Gebeta</h4>
                <p>Premium food delivery service in Hawassa. Connecting you with the best local restaurants.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-telegram"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Company</h4>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Press</a></li>
                    <li><a href="#">Blog</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <ul>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> Hawassa, Ethiopia</li>
                    <li><i class="fas fa-phone"></i> +251 912 345 678</li>
                    <li><i class="fas fa-envelope"></i> support@gebeta.com</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Gebeta. All rights reserved. Delivering happiness to your doorstep.</p>
        </div>
    </footer>

    <!-- Floating Cart -->
    <div class="floating-cart" id="floatingCart" onclick="viewCart()">
        <i class="fas fa-shopping-cart"></i>
        <span id="cartCount">0</span>
        <span>Items</span>
    </div>

    <!-- Modals -->
    <div class="modal-overlay" id="modal-overlay" onclick="closeAllModals()"></div>
    
    <div class="modal" id="login-modal">
        <div class="modal-header">
            <h2>Welcome Back 👋</h2>
            <button class="modal-close" onclick="closeModal('login-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="login.php" onsubmit="handleFormSubmit(this)">
                <?= csrf_field() ?>
                <?php if ($loginError): ?>
                    <div class="modal-alert modal-alert-error">
                        ❌ <?= htmlspecialchars($loginError) ?>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="login_password" name="password" placeholder="Enter your password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword(this)"></i>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 0.8rem; font-weight: 500; cursor: pointer;">
                        <input type="checkbox" name="remember" style="width: auto;"> Remember me
                    </label>
                    <button type="button" onclick="switchModal('login-modal', 'forgot-password-modal')" style="background: none; border: none; color: var(--primary); cursor: pointer; font-size: 0.8rem; padding: 0;">Forgot password?</button>
                </div>
                <input type="hidden" id="login-lat" value="">
                <input type="hidden" id="login-lng" value="">
                <input type="hidden" id="login-loc" value="">
                <button type="submit" class="modal-btn">Sign In</button>
            </form>
            <div class="divider">or</div>
            <button id="google-login-btn" class="google-btn" onclick="googleLogin('login')">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Continue with Google
            </button>
            <div class="modal-footer-text">
                Don't have an account? <button onclick="switchModal('login-modal', 'register-modal')">Sign up</button>
            </div>
        </div>
    </div>

    <div class="modal" id="register-modal">
        <div class="modal-header">
            <h2>Create Account ✨</h2>
            <button class="modal-close" onclick="closeModal('register-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="register.php">
                <?= csrf_field() ?>
                <?php if ($registerError): ?>
                    <div class="modal-alert modal-alert-error">
                        ❌ <?= htmlspecialchars($registerError) ?>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="John Doe" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" placeholder="+251 912 345 678" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Register As</label>
                    <select name="role" class="form-input" style="width: 100%;" required>
                        <option value="customer">Customer</option>
                        <option value="restaurant">Restaurant Owner</option>
                        <option value="delivery">Delivery Partner</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="reg_password" name="password" placeholder="At least 8 characters" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword(this)"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="reg_confirm_password" name="confirm_password" placeholder="Repeat password" required>
                    </div>
                </div>

                <div class="password-requirements" id="reg-requirements" style="background: var(--gray-50); padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 12px;">
                    <div id="req-len" style="color: var(--gray-500); margin-bottom: 4px;">
                        <i class="fas fa-circle" style="font-size: 8px;"></i> At least 8 characters
                    </div>
                    <div id="req-up" style="color: var(--gray-500); margin-bottom: 4px;">
                        <i class="fas fa-circle" style="font-size: 8px;"></i> One uppercase letter
                    </div>
                    <div id="req-num" style="color: var(--gray-500); margin-bottom: 4px;">
                        <i class="fas fa-circle" style="font-size: 8px;"></i> One number
                    </div>
                    <div id="req-match" style="color: var(--gray-500);">
                        <i class="fas fa-circle" style="font-size: 8px;"></i> Passwords match
                    </div>
                </div>

                <input type="hidden" name="latitude" id="reg-lat" value="">
                <input type="hidden" name="longitude" id="reg-lng" value="">
                <input type="hidden" name="location_name" id="reg-loc" value="">
                <button type="submit" class="modal-btn">Create Account</button>
            </form>
            <div class="divider">or</div>
            <button id="google-signup-btn" class="google-btn" onclick="googleLogin('signup')">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Continue with Google
            </button>
            <div class="modal-footer-text">
                Already have an account? <button onclick="switchModal('register-modal', 'login-modal')">Sign in</button>
            </div>
        </div>
    </div>

    <div class="modal" id="forgot-password-modal">
        <div class="modal-header">
            <h2>Reset Password 🔑</h2>
            <button class="modal-close" onclick="closeModal('forgot-password-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="color: var(--gray-500); font-size: 0.875rem; margin-bottom: 1.5rem; text-align: center;">Enter your email and we'll send you a code to reset your password.</p>
            <form method="POST" action="forgot-password.php" onsubmit="handleFormSubmit(this)">
                <?= csrf_field() ?>
                <?php if ($forgotError): ?>
                    <div class="modal-alert modal-alert-error">
                        ❌ <?= htmlspecialchars($forgotError) ?>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </div>
                <button type="submit" class="modal-btn">Send Reset Code</button>
            </form>
        </div>
    </div>

    <script>
        // Popup Management
        let cartCount = 0;
        
        function showWelcomePopup() {
            const hasSeen = localStorage.getItem('gebeta_welcome_seen');
            if (!hasSeen) {
                setTimeout(() => {
                    document.getElementById('welcomeOverlay').classList.add('active');
                }, 1000);
            }
        }
        
        function closeWelcomePopup() {
            document.getElementById('welcomeOverlay').classList.remove('active');
            localStorage.setItem('gebeta_welcome_seen', 'true');
        }
        
        function dismissWelcomeAndSignup() {
            closeWelcomePopup();
            setTimeout(() => openModal('register-modal'), 300);
        }
        
        let adShown = false;
        
        function showAdPopup() {
            const hasSeen = localStorage.getItem('gebeta_ad_seen');
            if (!hasSeen && !adShown) {
                setTimeout(() => {
                    document.getElementById('adPopup').classList.add('active');
                    adShown = true;
                }, 5000);
            }
        }
        
        function closeAdPopup() {
            document.getElementById('adPopup').classList.remove('active');
            localStorage.setItem('gebeta_ad_seen', 'true');
        }
        
        function orderNow() {
            closeAdPopup();
            window.location.href = '/customer/dashboard.php';
        }
        
        // Cart Functions
        function addToCart(name) {
            cartCount++;
            document.getElementById('cartCount').textContent = cartCount;
            showToast(`Added ${name} to cart!`, 'success');
            const cart = document.getElementById('floatingCart');
            cart.style.animation = 'cartShake 0.3s ease';
            setTimeout(() => cart.style.animation = '', 300);
        }
        
        function viewCart() {
            if (cartCount > 0) {
                showToast(`${cartCount} item${cartCount > 1 ? 's' : ''} in your cart`, 'success');
            } else {
                showToast('Your cart is empty. Add some food!', 'info');
            }
        }
        
        // Filter Functions
        function filterRestaurants(filter) {
            document.querySelectorAll('.chip').forEach(chip => {
                chip.classList.remove('active');
                if (chip.dataset.filter === filter) chip.classList.add('active');
            });
            
            document.querySelectorAll('.restaurant-card').forEach(card => {
                if (filter === 'all') card.style.display = '';
                else if (filter === 'popular') card.style.display = card.dataset.popular === '1' ? '' : 'none';
                else if (filter === 'fast') card.style.display = card.dataset.fast === '1' ? '' : 'none';
                else if (filter === 'free') card.style.display = card.dataset.fee === '0' ? '' : 'none';
                else if (filter === 'rating') card.style.display = parseFloat(card.dataset.rating) >= 4.6 ? '' : 'none';
            });
        }
        
        function goToRestaurant(id) {
            window.location.href = `/customer/restaurant.php?id=${id}`;
        }
        
        function handleFormSubmit(form) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Processing...';
            }
        }
        
        // Toast
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type === 'error' ? 'error' : ''}`;
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i> ${message}`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
        
        // Location
        document.getElementById('use-location-btn')?.addEventListener('click', () => {
            if (navigator.geolocation) {
                const btn = document.getElementById('use-location-btn');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Locating...';
                navigator.geolocation.getCurrentPosition(position => {
                    window.location.href = `/customer/dashboard.php?lat=${position.coords.latitude}&lng=${position.coords.longitude}`;
                }, () => {
                    btn.innerHTML = '<i class="fas fa-map-marker-alt"></i> Use current location';
                    showToast('Unable to get location', 'error');
                });
            } else {
                showToast('Geolocation not supported', 'error');
            }
        });
        
        // Modal Functions
        function openModal(modalId) {
            document.getElementById('modal-overlay').classList.add('active');
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            // Only hide overlay if no other modals are active
            if (!document.querySelector('.modal.active')) {
                document.getElementById('modal-overlay').classList.remove('active');
            }
            document.body.style.overflow = '';
        }
        
        function closeAllModals() {
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
            document.getElementById('modal-overlay').classList.remove('active');
            document.body.style.overflow = '';
        }
        
        function switchModal(closeId, openId) {
            // Deactivate the current modal but leave the overlay active for the next one
            document.getElementById(closeId).classList.remove('active');
            setTimeout(() => openModal(openId), 200);
        }

        function togglePassword(icon) {
            const input = icon.parentElement.querySelector('input');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Password Validation Logic for Register Modal
        const regPass = document.getElementById('reg_password');
        const regConf = document.getElementById('reg_confirm_password');
        const regSubmit = document.querySelector('#register-modal .modal-btn');

        function validateRegForm() {
            const val = regPass.value;
            const confVal = regConf.value;
            
            const hasLen = val.length >= 8;
            const hasUpper = /[A-Z]/.test(val);
            const hasNum = /[0-9]/.test(val);
            const matches = val === confVal && confVal !== '';

            updateReqStyle('req-len', hasLen);
            updateReqStyle('req-up', hasUpper);
            updateReqStyle('req-num', hasNum);
            updateReqStyle('req-match', matches);

            regSubmit.disabled = !(hasLen && hasUpper && hasNum && matches);
            regSubmit.style.opacity = regSubmit.disabled ? '0.6' : '1';
        }

        function updateReqStyle(id, isValid) {
            const el = document.getElementById(id);
            if (!el) return;
            const icon = el.querySelector('i');
            if (isValid) {
                el.style.color = '#10B981'; // Success Green
                icon.className = 'fas fa-check-circle';
            } else {
                el.style.color = '#6B7280'; // Gray
                icon.className = 'fas fa-circle';
            }
        }

        if (regPass && regConf) {
            regPass.addEventListener('input', validateRegForm);
            regConf.addEventListener('input', validateRegForm);
            // Initial check
            validateRegForm();
        }
        
        function googleLogin() {
            showToast('Google Sign-In is coming soon! Please use email for now.', 'info');
        }
        
        // Animate numbers on scroll
        function animateNumber(element, start, end, duration, isDecimal = false) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const current = start + (end - start) * progress;
                element.textContent = isDecimal ? current.toFixed(1) : Math.floor(current).toLocaleString();
                if (progress < 1) requestAnimationFrame(step);
                else element.textContent = isDecimal ? end.toFixed(1) : end.toLocaleString();
            };
            requestAnimationFrame(step);
        }
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.querySelectorAll('.stat-number').forEach(el => {
                        const target = parseFloat(el.dataset.target);
                        animateNumber(el, 0, target, 2000, target !== Math.floor(target));
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });
        
        document.querySelector('.stats-section') && observer.observe(document.querySelector('.stats-section'));
        
        // Initialize popups
        setTimeout(() => {
            showWelcomePopup();
            showAdPopup();
        }, 500);
        
        // Close modal on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAllModals();
        });

        // Auto-reopen login modal if there was an error
        <?php if ($loginError): ?>
            window.addEventListener('load', () => openModal('login-modal'));
        <?php endif; ?>

        // Auto-reopen register modal if there was an error (e.g. session expired during verification)
        <?php if ($registerError): ?>
            window.addEventListener('load', () => openModal('register-modal'));
        <?php endif; ?>

        // Auto-reopen forgot password modal if there was an error
        <?php if ($forgotError): ?>
            window.addEventListener('load', () => openModal('forgot-password-modal'));
        <?php endif; ?>
    </script>
    
    <!-- RESPONSIVE JS -->
    <script src="/assets/js/responsive.js"></script>
</body>
</html>