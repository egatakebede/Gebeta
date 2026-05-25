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

    $search = trim($_GET['q'] ?? '');
    $limit = min(50, max(5, (int)($_GET['limit'] ?? 10)));

    $where = 'WHERE o.restaurant_id = :rid';
    $params = [':rid' => $rid];

    if ($search !== '') {
        $where .= ' AND (u.name LIKE :q OR u.phone LIKE :q)';
        $params[':q'] = '%' . $search . '%';
    }

    // Top customers by spend
    $sql = 'SELECT u.id, u.name, u.phone,
                   COUNT(o.id) AS order_count,
                   SUM(o.total_amount + o.delivery_fee) AS total_spent,
                   MAX(o.created_at) AS last_order_at
            FROM orders o
            JOIN users u ON u.id = o.user_id
            ' . $where . '
            GROUP BY u.id
            ORDER BY total_spent DESC
            LIMIT :limit';

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'items' => $customers,
            'updated_at' => time()
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}

