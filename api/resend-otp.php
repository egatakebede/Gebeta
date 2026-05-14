<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    return;
}

// Determine pending context
if (!empty($_SESSION['pending_register'])) {
    $email   = $_SESSION['pending_register']['email'];
    $name    = $_SESSION['pending_register']['name'];
    $purpose = 'register';
} elseif (!empty($_SESSION['pending_login'])) {
    $email   = $_SESSION['pending_login']['email'];
    $name    = $_SESSION['pending_login']['name'];
    $purpose = 'login';
} else {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please start again.']);
    return;
}

// Rate limit: max 1 resend per 60 seconds
$stmt = $pdo->prepare(
    'SELECT created_at FROM otps WHERE email = ? AND purpose = ? ORDER BY id DESC LIMIT 1'
);
$stmt->execute([$email, $purpose]);
$last = $stmt->fetch(PDO::FETCH_ASSOC);

if ($last && (time() - strtotime($last['created_at'])) < 60) {
    echo json_encode(['success' => false, 'message' => 'Please wait before requesting a new code.']);
    return;
}

if (!send_otp_email($email, $name, $purpose)) {
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
    return;
}

echo json_encode(['success' => true]);
