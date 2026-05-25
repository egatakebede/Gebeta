<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Accept JSON or form-encoded
$payload = json_decode(file_get_contents('php://input'), true);
$status = $payload['status'] ?? ($_POST['status'] ?? '');

require_login();

$user_id = $_SESSION['user']['id'];

// Ensure delivery role
$role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? null);
if ($role !== 'delivery') {
    jsonResponse(['success' => false, 'message' => 'Forbidden'], 403);
}

$status = clean($status);
if (!in_array($status, ['online', 'offline'], true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid status'], 400);
}

try {
    $stmt = $pdo->prepare("UPDATE delivery_partners SET status = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->execute([$status, $user_id]);

    jsonResponse(['success' => true, 'status' => $status]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database error'], 500);
}

