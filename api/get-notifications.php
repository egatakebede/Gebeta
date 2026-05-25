<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user_id = $_SESSION['user']['id'];
$role = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '');

// If notifications_enabled preference isn't set, default to true.
$notifications_enabled = isset($_SESSION['notifications_enabled']) ? (bool)$_SESSION['notifications_enabled'] : true;
if (!$notifications_enabled) {
    jsonResponse(['success' => true, 'notifications' => []]);
}

$notifications = [];

// Lightweight implementation: use order updates relevant to role.
// This can be expanded to cover delivery/restaurant specific tables.
try {
    switch ($role) {
        case 'customer':
            $stmt = $pdo->prepare(
                "SELECT o.id, o.order_number, o.status, o.updated_at
                 FROM orders o
                 WHERE o.user_id = ?
                 ORDER BY o.updated_at DESC
                 LIMIT 5"
            );
            $stmt->execute([$user_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $notifications[] = [
                    'id' => 'order-' . $r['id'],
                    'message' => 'Order #' . $r['order_number'] . ' status updated',
                    'type' => 'success',
                    'time' => $r['updated_at']
                ];
            }
            break;

        case 'restaurant':
            $stmt = $pdo->prepare(
                "SELECT o.id, o.order_number, o.status, o.updated_at
                 FROM orders o
                 WHERE o.restaurant_id = (SELECT id FROM restaurants WHERE user_id = ?)
                 ORDER BY o.updated_at DESC
                 LIMIT 5"
            );
            $stmt->execute([$user_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $notifications[] = [
                    'id' => 'order-' . $r['id'],
                    'message' => 'Order #' . $r['order_number'] . ' updated',
                    'type' => 'warning',
                    'time' => $r['updated_at']
                ];
            }
            break;

        case 'delivery':
            // Use order_deliveries assignments and status changes
            $stmt = $pdo->prepare(
                "SELECT od.id, o.order_number, od.status, od.updated_at
                 FROM order_deliveries od
                 JOIN orders o ON o.id = od.order_id
                 WHERE od.delivery_partner_id = (SELECT id FROM delivery_partners WHERE user_id = ?)
                 ORDER BY od.updated_at DESC
                 LIMIT 5"
            );
            $stmt->execute([$user_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $notifications[] = [
                    'id' => 'delivery-' . $r['id'],
                    'message' => 'Delivery for Order #' . $r['order_number'] . ' updated',
                    'type' => 'info',
                    'time' => $r['updated_at']
                ];
            }
            break;

        case 'admin':
            $pendingStmt = $pdo->query("SELECT COUNT(*) AS c FROM restaurants WHERE status = 'pending'");
            $count = (int)$pendingStmt->fetchColumn();
            if ($count > 0) {
                $notifications[] = [
                    'id' => 'pending_restaurants',
                    'message' => $count . ' restaurants waiting for approval',
                    'type' => 'warning',
                    'time' => date('Y-m-d H:i:s')
                ];
            }
            break;
    }
} catch (Exception $e) {
    // swallow and return empty list
}

jsonResponse(['success' => true, 'notifications' => $notifications]);

