<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in and is customer
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    // Get customer stats
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
    $stmt->execute([$userId]);
    $totalOrders = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount + delivery_fee), 0) FROM orders WHERE user_id = ?');
    $stmt->execute([$userId]);
    $totalSpent = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(DISTINCT restaurant_id) FROM orders WHERE user_id = ?');
    $stmt->execute([$userId]);
    $restaurantsOrdered = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = "delivered"');
    $stmt->execute([$userId]);
    $completedOrders = (int)$stmt->fetchColumn();

    // Recent orders
    $stmt = $pdo->prepare('
        SELECT o.id, o.order_number, o.status, o.total_amount, o.created_at,
               r.name AS restaurant_name, r.cuisine_type
        FROM orders o
        JOIN restaurants r ON o.restaurant_id = r.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
        LIMIT 5
    ');
    $stmt->execute([$userId]);
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Favorite restaurants
    $stmt = $pdo->prepare('
        SELECT r.id, r.name, r.cuisine_type, r.location, r.rating,
               COUNT(o.id) AS order_count
        FROM restaurants r
        JOIN orders o ON r.id = o.restaurant_id
        WHERE o.user_id = ?
        GROUP BY r.id
        ORDER BY order_count DESC
        LIMIT 6
    ');
    $stmt->execute([$userId]);
    $favoriteRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recommended restaurants
    $stmt = $pdo->query('
        SELECT id, name, cuisine_type, location, rating
        FROM restaurants
        WHERE status = "active"
        ORDER BY rating DESC
        LIMIT 6
    ');
    $recommendedRestaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'metrics' => [
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
                'restaurants_ordered' => $restaurantsOrdered,
                'completed_orders' => $completedOrders
            ],
            'recent_orders' => $recentOrders,
            'favorite_restaurants' => $favoriteRestaurants,
            'recommended_restaurants' => $recommendedRestaurants,
            'updated_at' => time()
        ]
    ]);

} catch (PDOException $e) {
    error_log('Customer dashboard API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('Customer dashboard API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error occurred']);
}
?>
