<?php
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

// Store pending login in session
$_SESSION['pending_login'] = [
    'id'            => $user['id'],
    'email'         => $user['email'],
    'name'          => $user['name'],
    'latitude'      => is_numeric($_POST['latitude'] ?? '')  ? (float)$_POST['latitude']  : null,
    'longitude'     => is_numeric($_POST['longitude'] ?? '') ? (float)$_POST['longitude'] : null,
    'location_name' => sanitize($_POST['location_name'] ?? ''),
];

// Send OTP email (OTP is stored in DB regardless of email status)
send_otp_email($user['email'], $user['name'], 'login');

redirect('/verify.php?purpose=login');
