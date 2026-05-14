<?php
require_once __DIR__ . '/../includes/db.php';
$query = trim($_GET['q'] ?? '');
header('Content-Type: application/json');
if (!$query) {
    echo json_encode(['results' => []]);
    exit;
}
$search = '%' . $query . '%';
$stmt = $pdo->prepare('SELECT r.id, r.name, r.description, r.cuisine_type, r.location, r.rating FROM restaurants r WHERE r.status = "active" AND (r.name LIKE ? OR r.description LIKE ? OR r.cuisine_type LIKE ?) ORDER BY r.rating DESC LIMIT 10');
$stmt->execute([$search, $search, $search]);
$restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['results' => $restaurants]);
