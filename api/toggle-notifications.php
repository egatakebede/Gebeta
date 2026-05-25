<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$payload = json_decode(file_get_contents('php://input'), true);
$enabled = $payload['notifications_enabled'] ?? ($_POST['notifications_enabled'] ?? false);

require_login();

$user_id = $_SESSION['user']['id'];
$enabled = (bool)$enabled;

try {
    // No notifications table exists in this repo snapshot.
    // Store preference in session for now.
    $_SESSION['notifications_enabled'] = $enabled;

    jsonResponse([
        'success' => true,
        'enabled' => $enabled
    ]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error'], 500);
}

