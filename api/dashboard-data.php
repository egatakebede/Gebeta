<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['restaurant']);
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->prepare('SELECT * FROM restaurants WHERE user_id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user']['id']]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Restaurant not found']);
        exit;
    }

    $rid = $restaurant['id'];

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ?');
    $stmt->execute([$rid]);
    $totalOrders = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND DATE(created_at) = CURDATE()');
    $stmt->execute([$rid]);
    $todayOrders = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE restaurant_id = ?');
    $stmt->execute([$rid]);
    $totalRevenue = $stmt->fetchColumn();
    if ($totalRevenue === null) $totalRevenue = 0;

    $stmt = $pdo->prepare('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE restaurant_id = ? AND DATE(created_at) = CURDATE()');
    $stmt->execute([$rid]);
    $todayRevenue = $stmt->fetchColumn();
    if ($todayRevenue === null) $todayRevenue = 0;

    // Pending orders KPI (schema might differ)
    $pendingOrders = 0;
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND status = "pending"');
        $stmt->execute([$rid]);
        $pendingOrders = (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        $pendingOrders = 0;
    }

    $totalMenuItems = 0;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM menu_items mi JOIN categories c ON mi.category_id = c.id WHERE c.restaurant_id = ?');
    $stmt->execute([$rid]);
    $totalMenuItems = (int)$stmt->fetchColumn();

    $rating = isset($restaurant['rating']) ? (float)$restaurant['rating'] : 0.0;

    // Recent orders for quick widget
    $stmt = $pdo->prepare('
        SELECT o.*, u.name AS customer_name, u.phone AS customer_phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.restaurant_id = ?
        ORDER BY o.created_at DESC
        LIMIT 10
    ');
    $stmt->execute([$rid]);
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top selling items
    $stmt = $pdo->prepare('
        SELECT mi.name, SUM(oi.quantity) AS total_sold, SUM(oi.price * oi.quantity) AS revenue
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.restaurant_id = ?
        GROUP BY mi.id
        ORDER BY total_sold DESC
        LIMIT 5
    ');
    $stmt->execute([$rid]);
    $topItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Revenue & Orders trend (last 7 days)
    $revenueTrend = [];
    $ordersTrend = [];
    $stmt = $pdo->prepare('
        SELECT DATE(created_at) AS day, 
               COUNT(*) AS order_count,
               SUM(total_amount + delivery_fee) AS revenue
        FROM orders
        WHERE restaurant_id = ?
          AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ');
    $stmt->execute([$rid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $dayMap = [];
    foreach ($rows as $r) {
        $dayMap[$r['day']] = [
            'revenue' => (float)$r['revenue'],
            'orders' => (int)$r['order_count']
        ];
    }
    for ($i = 6; $i >= 0; $i--) {
        $d = (new DateTime('today'))->modify("-$i days")->format('Y-m-d');
        $revenueTrend[] = (float)($dayMap[$d]['revenue'] ?? 0);
        $ordersTrend[] = (int)($dayMap[$d]['orders'] ?? 0);
    }
    // Simplified peak hours (top 3 hours by order count in last 7 days)
    $peakHours = [];
    try {
        $stmt = $pdo->prepare('
            SELECT HOUR(created_at) AS hr, COUNT(*) AS cnt, SUM(total_amount + delivery_fee) AS revenue
            FROM orders
            WHERE restaurant_id = ?
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY HOUR(created_at)
            ORDER BY cnt DESC
            LIMIT 6
        ');
        $stmt->execute([$rid]);
        $peakHours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $peakHours = [];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'restaurant' => [
                'id' => (int)$restaurant['id'],
                'name' => $restaurant['name'],
                'status' => $restaurant['status'] ?? null,
                'rating' => $rating
            ],
            'metrics' => [
                'total_orders' => $totalOrders,
                'today_orders' => $todayOrders,
                'total_revenue' => (float)$totalRevenue,
                'today_revenue' => (float)$todayRevenue,
                'pending_orders' => $pendingOrders,
                'total_menu_items' => $totalMenuItems
            ],
            'recent_orders' => $recentOrders,
            'top_items' => $topItems,
            'chart' => [
                'orders_trend' => $ordersTrend,
                'revenue_trend' => $revenueTrend,
                'peak_hours' => $peakHours
            ],
            'updated_at' => time()
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
