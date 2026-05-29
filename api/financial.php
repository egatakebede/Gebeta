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

    // Summary cards. Schema may not have commission; use placeholder if columns don't exist.
    $revenueToday = 0;
    $pendingAmount = 0;
    $commission = 0;
    $netEarn = 0;

    $stmt = $pdo->prepare('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE restaurant_id = ? AND DATE(created_at) = CURDATE()');
    $stmt->execute([$rid]);
    $revenueToday = (float)($stmt->fetchColumn() ?? 0);

    // Orders pending (status-based)
    try {
        $stmt = $pdo->prepare('SELECT SUM(total_amount + delivery_fee) FROM orders WHERE restaurant_id = ? AND status = "pending"');
        $stmt->execute([$rid]);
        $pendingAmount = (float)($stmt->fetchColumn() ?? 0);
    } catch (Throwable $e) {
        $pendingAmount = 0;
    }

    // Commission/net: This is a simplified calculation. In production, use a proper financial model.
    $commission = $revenueToday * 0.15;
    $netEarn = $revenueToday - $commission;

    // Payment history: use orders as proxy transactions
    $stmt = $pdo->prepare('SELECT order_number, status, total_amount, delivery_fee, created_at
                           FROM orders
                           WHERE restaurant_id = ?
                           ORDER BY created_at DESC
                           LIMIT 15');
    $stmt->execute([$rid]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => [
                'revenue_today' => $revenueToday,
                'commission_today' => $commission,
                'net_earn_today' => $netEarn,
                'pending_amount' => $pendingAmount
            ],
            'transactions' => $transactions,
            'updated_at' => time()
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
