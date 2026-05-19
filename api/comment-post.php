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
$comment = trim($_POST['comment'] ?? '');

if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO post_comments (post_id, user_id, comment) VALUES (?, ?, ?)');
    $stmt->execute([$postId, $_SESSION['user']['id'], $comment]);
    
    // Get the new comment with user info
    $commentId = $pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT c.*, u.name as user_name FROM post_comments c JOIN users u ON c.user_id = u.id WHERE c.id = ?');
    $stmt->execute([$commentId]);
    $newComment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'comment' => $newComment
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
