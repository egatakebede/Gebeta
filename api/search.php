<?php
require_once __DIR__ . '/../includes/db.php';
$query = trim($_GET['q'] ?? '');
header('Content-Type: application/json');
if (!$query) {
    echo json_encode(['restaurants' => [], 'dishes' => []]);
    exit;
}
$search = '%' . $query . '%';

$stmt = $pdo->prepare(
    'SELECT r.id, r.name, r.description, r.cuisine_type, r.location, r.rating
     FROM restaurants r
     WHERE r.status = ?
       AND (r.name LIKE ? OR r.description LIKE ? OR r.cuisine_type LIKE ? OR r.location LIKE ?)
     ORDER BY r.rating DESC
     LIMIT 10'
);
$stmt->execute(['active', $search, $search, $search, $search]);
$restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    'SELECT mi.id, mi.name AS dish_name, mi.description AS dish_description, mi.price, r.id AS restaurant_id, r.name AS restaurant_name, r.rating
     FROM menu_items mi
     JOIN categories c ON mi.category_id = c.id
     JOIN restaurants r ON c.restaurant_id = r.id
     WHERE mi.is_available = 1
       AND r.status = ?
       AND (mi.name LIKE ? OR mi.description LIKE ?)
     ORDER BY r.rating DESC
     LIMIT 10'
);
$stmt->execute(['active', $search, $search]);
$dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['restaurants' => $restaurants, 'dishes' => $dishes]);
