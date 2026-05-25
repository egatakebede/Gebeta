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

    $rid = (int)$restaurant['id'];
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;

    // Date filters with safe fallbacks
    $dateWhere = 'o.restaurant_id = :rid';
    $params = [':rid' => $rid];

    if ($from) {
        $dateWhere .= ' AND o.created_at >= :from';
        $params[':from'] = $from . ' 00:00:00';
    }
    if ($to) {
        $dateWhere .= ' AND o.created_at <= :to';
        $params[':to'] = $to . ' 23:59:59';
    }

    // Last 7 days labels (used when no explicit date range)
    $labels = [];
    $daysToGenerate = 7;
    if (!$from && !$to) {
        for ($i = $daysToGenerate - 1; $i >= 0; $i--) {
            $labels[] = (new DateTime('today'))->modify("-$i days")->format('D');
        }
    } else {
        $labels = [];
    }

    // Orders trend (count by day)
    $ordersTrend = [];
    $revenueTrend = [];

    if (!$from && !$to) {
        $stmt = $pdo->prepare('
            SELECT DATE(o.created_at) AS day,
                   COUNT(*) AS order_count,
                   SUM(o.total_amount + o.delivery_fee) AS revenue
            FROM orders o
            WHERE o.restaurant_id = :rid
              AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(o.created_at)
            ORDER BY day ASC
        ');
        $stmt->execute([':rid' => $rid]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mapCnt = [];
        $mapRev = [];
        foreach ($rows as $r) {
            $mapCnt[$r['day']] = (int)$r['order_count'];
            $mapRev[$r['day']] = (float)$r['revenue'];
        }
        for ($i = $daysToGenerate - 1; $i >= 0; $i--) {
            $d = (new DateTime('today'))->modify("-$i days")->format('Y-m-d');
            $ordersTrend[] = (int)($mapCnt[$d] ?? 0);
            $revenueTrend[] = (float)($mapRev[$d] ?? 0);
        }
    } else {
        $stmt = $pdo->prepare('
            SELECT DATE(o.created_at) AS day,
                   COUNT(*) AS order_count,
                   SUM(o.total_amount + o.delivery_fee) AS revenue
            FROM orders o
            WHERE ' . $dateWhere . '
            GROUP BY DATE(o.created_at)
            ORDER BY day ASC
        ');
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $labels[] = (new DateTime($r['day']))->format('M j');
            $ordersTrend[] = (int)$r['order_count'];
            $revenueTrend[] = (float)$r['revenue'];
        }
    }

    // Top items
    $stmt = $pdo->prepare('
        SELECT mi.name,
               SUM(oi.quantity) AS sold_count,
               SUM(oi.price * oi.quantity) AS revenue
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        JOIN orders o ON oi.order_id = o.id
        WHERE ' . $dateWhere . '
        GROUP BY mi.id
        ORDER BY sold_count DESC
        LIMIT 8
    ');
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();
    $topItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Peak hours (top 6)
    $stmt = $pdo->prepare('
        SELECT HOUR(o.created_at) AS hour,
               COUNT(*) AS order_count,
               SUM(o.total_amount + o.delivery_fee) AS revenue
        FROM orders o
        WHERE ' . $dateWhere . '
        GROUP BY HOUR(o.created_at)
        ORDER BY order_count DESC
        LIMIT 6
    ');
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();
    $peakHours = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'filters' => ['from' => $from, 'to' => $to],
            'chart' => [
                'labels' => $labels,
                'orders_trend' => $ordersTrend,
                'revenue_trend' => $revenueTrend,
                'top_items' => $topItems,
                'peak_hours' => $peakHours,
            ],
            'updated_at' => time()
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}

