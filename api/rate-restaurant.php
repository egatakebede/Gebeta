<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$restaurantId = (int)($_POST['restaurant_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$review = trim($_POST['review'] ?? '');

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}

try {
    // Check if user already rated
    $stmt = $pdo->prepare('SELECT id FROM restaurant_ratings WHERE restaurant_id = ? AND user_id = ?');
    $stmt->execute([$restaurantId, $_SESSION['user']['id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing rating
        $stmt = $pdo->prepare('UPDATE restaurant_ratings SET rating = ?, review = ? WHERE id = ?');
        $stmt->execute([$rating, $review, $existing['id']]);
    } else {
        // Insert new rating
        $stmt = $pdo->prepare('INSERT INTO restaurant_ratings (restaurant_id, user_id, rating, review) VALUES (?, ?, ?, ?)');
        $stmt->execute([$restaurantId, $_SESSION['user']['id'], $rating, $review]);
    }
    
    // Update restaurant average rating
    $stmt = $pdo->prepare('SELECT AVG(rating) as avg_rating FROM restaurant_ratings WHERE restaurant_id = ?');
    $stmt->execute([$restaurantId]);
    $avgRating = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare('UPDATE restaurants SET rating = ? WHERE id = ?');
    $stmt->execute([round($avgRating, 1), $restaurantId]);
    
    echo json_encode([
        'success' => true,
        'average_rating' => round($avgRating, 1)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
