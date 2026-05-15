<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

$name     = sanitize($_POST['name'] ?? '');
$phone    = sanitize($_POST['phone'] ?? '');
$email    = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = trim($_POST['password'] ?? '');
$role     = in_array($_POST['role'] ?? 'customer', ['customer', 'restaurant'], true) ? $_POST['role'] : 'customer';

if (!$name || !$phone || !$email || strlen($password) < 6) {
    flash_set('error', 'Please complete all fields. Password must be at least 6 characters.');
    redirect('/index.php');
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1');
$stmt->execute([$email, $phone]);
if ($stmt->fetch()) {
    flash_set('error', 'Email or phone already registered.');
    redirect('/index.php');
}

// Store pending registration in session
$_SESSION['pending_register'] = [
    'name'          => $name,
    'phone'         => $phone,
    'email'         => $email,
    'password'      => password_hash($password, PASSWORD_DEFAULT),
    'role'          => $role,
    'latitude'      => is_numeric($_POST['latitude'] ?? '')  ? (float)$_POST['latitude']  : null,
    'longitude'     => is_numeric($_POST['longitude'] ?? '') ? (float)$_POST['longitude'] : null,
    'location_name' => sanitize($_POST['location_name'] ?? ''),
];

// DEV MODE: Skip email verification
if (true) {
    // Create user directly
    $stmt = $pdo->prepare('INSERT INTO users (name, phone, email, password, role, latitude, longitude, location_name, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
    $stmt->execute([
        $name,
        $phone,
        $email,
        $_SESSION['pending_register']['password'],
        $role,
        $_SESSION['pending_register']['latitude'],
        $_SESSION['pending_register']['longitude'],
        $_SESSION['pending_register']['location_name']
    ]);
    
    unset($_SESSION['pending_register']);
    flash_set('success', 'Account created! Please login.');
    redirect('/index.php');
}

if (!send_otp_email($email, $name, 'register')) {
    flash_set('error', 'Could not send verification email. Please try again.');
    redirect('/index.php');
}

redirect('/verify.php?purpose=register');
