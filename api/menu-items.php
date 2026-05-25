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

    $categoryId = $_GET['categoryId'] ?? '';
    $sortBy = $_GET['sortBy'] ?? 'popularity';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(50, max(1, (int)($_GET['pageSize'] ?? 12)));
    $offset = ($page - 1) * $pageSize;

    $where = 'WHERE c.restaurant_id = :rid';
    $params = [':rid' => $rid];

    if ($categoryId !== '' && $categoryId !== 'all') {
        $where .= ' AND mi.category_id = :cat';
        $params[':cat'] = (int)$categoryId;
    }

    // Schema might not have popularity/sold metrics; use sold_today/reviews if exist.
    // We'll compute a lightweight sold_count from order_items.
    $baseSql = 'FROM menu_items mi
                 JOIN categories c ON mi.category_id = c.id
                 LEFT JOIN (
                    SELECT oi.menu_item_id, SUM(oi.quantity) AS sold_qty
                    FROM order_items oi
                    JOIN orders o ON o.id = oi.order_id
                    WHERE o.restaurant_id = :rid AND DATE(o.created_at) = CURDATE()
                    GROUP BY oi.menu_item_id
                 ) s ON s.menu_item_id = mi.id
                 ';

    $countSql = 'SELECT COUNT(*) AS cnt FROM ' . $baseSql . ' ' . $where;
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $orderSql = 'mi.created_at DESC';
    if ($sortBy === 'price') {
        $orderSql = 'mi.price ASC';
    } elseif ($sortBy === 'rating') {
        // If reviews/ratings exist later; for now fallback.
        $orderSql = 'mi.created_at DESC';
    } elseif ($sortBy === 'sales' || $sortBy === 'popularity') {
        $orderSql = 'COALESCE(s.sold_qty, 0) DESC';
    }

    $sql = 'SELECT mi.*, c.name AS category_name,
                   COALESCE(s.sold_qty, 0) AS sold_today
            ' . $baseSql . '
            ' . $where . '
            ORDER BY ' . $orderSql . '
            LIMIT :limit OFFSET :offset';

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Categories list
    $catStmt = $pdo->prepare('SELECT id, name FROM categories WHERE restaurant_id = ? ORDER BY name ASC');
    $catStmt->execute([$rid]);
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'items' => $items,
            'categories' => $categories,
            'meta' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => (int)ceil($total / $pageSize)
            ]
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}

