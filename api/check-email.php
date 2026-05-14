<?php
require_once __DIR__ . '/../includes/db.php';
$email = filter_var($_GET['email'] ?? '', FILTER_VALIDATE_EMAIL);
header('Content-Type: application/json');
if (!$email) {
    echo json_encode(['available' => false]);
    exit;
}
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$available = $stmt->fetch() ? false : true;
echo json_encode(['available' => $available]);
