<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/db.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('/index.php');
    }

    $email    = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        flash_set('error', 'Please enter valid credentials.');
        redirect('/index.php');
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        flash_set('error', 'Email or password is incorrect.');
        redirect('/index.php');
    }

    if ($user['status'] === 'suspended') {
        flash_set('error', 'Your account has been suspended.');
        redirect('/index.php');
    }

    // Update location if provided
    if (isset($_POST['latitude']) && is_numeric($_POST['latitude']) && isset($_POST['longitude']) && is_numeric($_POST['longitude'])) {
        $stmt = $pdo->prepare('UPDATE users SET latitude = ?, longitude = ?, location_name = ?, location_updated_at = NOW() WHERE id = ?');
        $stmt->execute([
            (float)$_POST['latitude'], 
            (float)$_POST['longitude'], 
            sanitize($_POST['location_name'] ?? ''), 
            $user['id']
        ]);
        
        // Update session with location
        $user['latitude'] = (float)$_POST['latitude'];
        $user['longitude'] = (float)$_POST['longitude'];
        $user['location_name'] = sanitize($_POST['location_name'] ?? '');
    }

    // Login user directly without OTP
    login_user($user);

    // Redirect based on role
    if ($user['role'] === 'restaurant') {
        // Check if restaurant setup is complete
        $stmt = $pdo->prepare('SELECT id FROM restaurants WHERE user_id = ? LIMIT 1');
        $stmt->execute([$user['id']]);
        if (!$stmt->fetch()) {
            redirect('/restaurant/setup.php');
        }
        redirect('/restaurant/dashboard.php');
    } elseif ($user['role'] === 'admin') {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/customer/dashboard.php');
    }
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    flash_set('error', 'An error occurred during login. Please try again.');
    redirect('/index.php');
}
