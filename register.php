<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

$name = sanitize($_POST['name'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = trim($_POST['password'] ?? '');
$role = in_array($_POST['role'] ?? 'customer', ['customer', 'restaurant'], true) ? $_POST['role'] : 'customer';

if (!$name || !$phone || !$email || strlen($password) < 6) {
    flash_set('error', 'Please complete all fields with valid data.');
    redirect('/index.php');
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1');
$stmt->execute([$email, $phone]);
if ($stmt->fetch()) {
    flash_set('error', 'Email or phone already exists.');
    redirect('/index.php');
}

$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$name, $email, $phone, $hashed, $role]);
$userId = $pdo->lastInsertId();

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
login_user($user);
redirect('/index.php');
