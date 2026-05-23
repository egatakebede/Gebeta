<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // Earth radius in kilometers
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earth_radius * $c;
    
    return round($distance, 2);
}

$customer_lat = $_SESSION['user']['latitude'] ?? null;
$customer_lon = $_SESSION['user']['longitude'] ?? null;

if (!$customer_lat || !$customer_lon) {
    echo json_encode(['success' => false, 'message' => 'Customer location not available']);
    exit;
}

$restaurant_id = (int)($_GET['restaurant_id'] ?? 0);

if ($restaurant_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid restaurant ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, latitude, longitude FROM restaurants WHERE id = ? AND status = 'active'");
$stmt->execute([$restaurant_id]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$restaurant || !$restaurant['latitude'] || !$restaurant['longitude']) {
    echo json_encode(['success' => false, 'message' => 'Restaurant location not available']);
    exit;
}

$distance = calculateDistance(
    $customer_lat,
    $customer_lon,
    $restaurant['latitude'],
    $restaurant['longitude']
);

// Calculate delivery fee: Base 50 Birr + 10 Birr per km
$delivery_fee = 50 + ($distance * 10);

// Estimate delivery time: 10 min prep + travel time (15 km/h average)
$prep_time = 10;
$travel_time = ceil(($distance / 15) * 60);
$total_time = $prep_time + $travel_time;

echo json_encode([
    'success' => true,
    'distance' => $distance,
    'delivery_fee' => round($delivery_fee, 2),
    'delivery_time_minutes' => $total_time,
    'restaurant_id' => $restaurant['id']
]);
