<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['restaurant']);
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

function getRestaurantId(PDO $pdo): int {
    $stmt = $pdo->prepare('SELECT * FROM restaurants WHERE user_id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user']['id']]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Restaurant not found']);
        exit;
    }
    return (int)$r['id'];
}

try {
    $rid = getRestaurantId($pdo);

    $status = trim($_GET['status'] ?? '');
    $search = trim($_GET['q'] ?? '');

    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(50, max(1, (int)($_GET['pageSize'] ?? 10)));
    $offset = ($page - 1) * $pageSize;

    $where = 'WHERE o.restaurant_id = :rid';
    $params = [':rid' => $rid];

    if ($status !== '') {
        // allow frontend values like pending/preparing/ready/delivered
        $where .= ' AND o.status = :status';
        $params[':status'] = $status;
    }

    if ($search !== '') {
        // support searching by order_number
        $where .= ' AND o.order_number LIKE :q';
        $params[':q'] = '%' . $search . '%';
    }

    $countSql = 'SELECT COUNT(*) FROM orders o ' . $where;
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $sql = 'SELECT o.*, u.name AS customer_name, u.phone AS customer_phone
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ' . $where . '
            ORDER BY o.created_at DESC
            LIMIT :limit OFFSET :offset';

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $echo = [
        'success' => true,
        'data' => [
            'items' => $rows,
            'meta' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => (int)ceil($total / $pageSize)
            ]
        ]
    ];
    echo json_encode($echo);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}

