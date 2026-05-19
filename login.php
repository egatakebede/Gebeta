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

// Update location if provided
if (is_numeric($_POST['latitude'] ?? '') && is_numeric($_POST['longitude'] ?? '')) {
    $pdo->prepare('UPDATE users SET latitude = ?, longitude = ?, location_name = ? WHERE id = ?')
        ->execute([(float)$_POST['latitude'], (float)$_POST['longitude'], sanitize($_POST['location_name'] ?? ''), $user['id']]);
}

// Login user directly without OTP
login_user($user);
redirect('/index.php');
