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

$postId = (int)($_POST['post_id'] ?? 0);
$reactionType = $_POST['reaction_type'] ?? '';

if (!in_array($reactionType, ['like', 'love', 'wow', 'sad', 'angry'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid reaction type']);
    exit;
}

try {
    // Check if user already reacted
    $stmt = $pdo->prepare('SELECT id, reaction_type FROM post_reactions WHERE post_id = ? AND user_id = ?');
    $stmt->execute([$postId, $_SESSION['user']['id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        if ($existing['reaction_type'] === $reactionType) {
            // Remove reaction
            $stmt = $pdo->prepare('DELETE FROM post_reactions WHERE id = ?');
            $stmt->execute([$existing['id']]);
            $action = 'removed';
        } else {
            // Update reaction
            $stmt = $pdo->prepare('UPDATE post_reactions SET reaction_type = ? WHERE id = ?');
            $stmt->execute([$reactionType, $existing['id']]);
            $action = 'updated';
        }
    } else {
        // Add new reaction
        $stmt = $pdo->prepare('INSERT INTO post_reactions (post_id, user_id, reaction_type) VALUES (?, ?, ?)');
        $stmt->execute([$postId, $_SESSION['user']['id'], $reactionType]);
        $action = 'added';
    }
    
    // Get reaction counts
    $stmt = $pdo->prepare('SELECT reaction_type, COUNT(*) as count FROM post_reactions WHERE post_id = ? GROUP BY reaction_type');
    $stmt->execute([$postId]);
    $reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $counts = [];
    foreach ($reactions as $r) {
        $counts[$r['reaction_type']] = (int)$r['count'];
    }
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'counts' => $counts
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
