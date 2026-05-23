<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$latitude = isset($data['latitude']) && is_numeric($data['latitude']) ? (float)$data['latitude'] : null;
$longitude = isset($data['longitude']) && is_numeric($data['longitude']) ? (float)$data['longitude'] : null;
$location_name = sanitize($data['location_name'] ?? '');

if (!$latitude || !$longitude) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE users SET latitude = ?, longitude = ?, location_name = ?, location_updated_at = NOW() WHERE id = ?');
    $stmt->execute([$latitude, $longitude, $location_name, $_SESSION['user']['id']]);
    
    // Update session
    $_SESSION['user']['latitude'] = $latitude;
    $_SESSION['user']['longitude'] = $longitude;
    $_SESSION['user']['location_name'] = $location_name;
    
    echo json_encode([
        'success' => true,
        'message' => 'Location saved successfully'
    ]);
} catch (PDOException $e) {
    error_log('Save location error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to save location']);
}
