<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user']['id'];

try {
    // Get customer stats
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
    $stmt->execute([$userId]);
    $totalOrders = $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE user_id = ?');
    $stmt->execute([$userId]);
    $totalSpent = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->prepare('SELECT COUNT(DISTINCT restaurant_id) FROM orders WHERE user_id = ?');
    $stmt->execute([$userId]);
    $restaurantsOrdered = $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = "delivered"');
    $stmt->execute([$userId]);
    $completedOrders = $stmt->fetchColumn();

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

    // Top restaurants
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
                'total_orders' => (int)$totalOrders,
                'completed_orders' => (int)$completedOrders,
                'total_spent' => (float)$totalSpent,
                'restaurants_ordered' => (int)$restaurantsOrdered
            ],
            'recent_orders' => $recentOrders,
            'favorite_restaurants' => $favoriteRestaurants,
            'recommended_restaurants' => $recommendedRestaurants
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>