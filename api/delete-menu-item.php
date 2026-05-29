<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['restaurant']);
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    // Ensure item belongs to this restaurant
    $stmt = $pdo->prepare('
        SELECT mi.id FROM menu_items mi 
        JOIN categories c ON mi.category_id = c.id 
        JOIN restaurants r ON c.restaurant_id = r.id 
        WHERE mi.id = ? AND r.user_id = ?
    ');
    $stmt->execute([$id, $_SESSION['user']['id']]);
    if ($stmt->fetch()) {
        $del = $pdo->prepare('DELETE FROM menu_items WHERE id = ?');
        $del->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }
}
echo json_encode(['success' => false]);