<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$payload = json_decode(file_get_contents('php://input'), true);
$status = $payload['status'] ?? ($_POST['status'] ?? '');

require_login();

$user_id = $_SESSION['user']['id'];
$role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? null);
if ($role !== 'restaurant') {
    jsonResponse(['success' => false, 'message' => 'Forbidden'], 403);
}

$status = clean($status);
if (!in_array($status, ['open', 'closed'], true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid status'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM restaurants WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        jsonResponse(['success' => false, 'message' => 'Restaurant not found'], 404);
    }

    // restaurants.status stores 'active'/'suspended' in current dashboard code
    $dbStatus = $status === 'open' ? 'active' : 'suspended';

    $stmt = $pdo->prepare("UPDATE restaurants SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$dbStatus, $restaurant['id']]);

    jsonResponse(['success' => true, 'status' => $status]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database error'], 500);
}

