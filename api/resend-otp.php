<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    return;
}

// Determine pending context
if (!empty($_SESSION['pending_email'])) {
    $email   = $_SESSION['pending_email'];
    $purpose = 'register';
    // Get name from registration_pending table
    $stmt = $pdo->prepare('SELECT name FROM registration_pending WHERE email = ?');
    $stmt->execute([$email]);
    $pending = $stmt->fetch(PDO::FETCH_ASSOC);
    $name = $pending['name'] ?? 'User';
} elseif (!empty($_SESSION['pending_reset'])) {
    $email   = $_SESSION['pending_reset']['email'];
    $name    = $_SESSION['pending_reset']['name'] ?? 'User';
    $purpose = 'reset';
} else {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please start again.']);
    return;
}

// Rate limit: max 1 resend per 60 seconds
$stmt = $pdo->prepare(
    'SELECT UNIX_TIMESTAMP(created_at) as ts FROM otps WHERE email = ? AND purpose = ? ORDER BY id DESC LIMIT 1'
);
$stmt->execute([$email, $purpose]);
$last = $stmt->fetch(PDO::FETCH_ASSOC);

if ($last && (time() - (int)$last['ts']) < 60) {
    echo json_encode(['success' => false, 'message' => 'Please wait before requesting a new code.']);
    return;
}

if (!send_otp_email($email, $name, $purpose)) {
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
    return;
}

echo json_encode(['success' => true]);
