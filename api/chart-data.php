<?php
// Compatibility endpoint: fetch everything needed for charts.
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
    $rid = (int)$restaurant['id'];

    // Revenue trend last 7 days
    $rev = [];
    $labels = [];
    $stmt = $pdo->prepare('SELECT DATE(created_at) AS day, SUM(total_amount + delivery_fee) AS revenue
                           FROM orders
                           WHERE restaurant_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                           GROUP BY DATE(created_at)
                           ORDER BY day ASC');
    $stmt->execute([$rid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $r) $map[$r['day']] = (float)$r['revenue'];

    for ($i = 6; $i >= 0; $i--) {
        $d = (new DateTime('today'))->modify("-$i days");
        $dayKey = $d->format('Y-m-d');
        $labels[] = $d->format('D');
        $rev[] = (float)($map[$dayKey] ?? 0);
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'labels' => $labels,
            'revenue_trend' => $rev,
            'updated_at' => time()
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}

