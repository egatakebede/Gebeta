<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON payload
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['token'])) {
    echo json_encode(['success' => false, 'message' => 'Missing token']);
    exit;
}

$token = $input['token'];
$mode = $input['mode'] ?? 'login';
$latitude = $input['latitude'] ?? null;
$longitude = $input['longitude'] ?? null;
$location_name = $input['location_name'] ?? null;

try {
    // Verify the Google ID token
    // For production, you should verify the token with Google's servers
    // Install via composer: google/auth
    // require_once __DIR__ . '/../vendor/autoload.php';
    // $client = new Google_Client(['client_id' => GOOGLE_CLIENT_ID]);
    // $payload = $client->verifyIdToken($token);
    
    // For now, simulate token parsing (JWT decoding)
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        throw new Exception('Invalid token format');
    }
    
    // Decode the payload (2nd part)
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    
    if (!$payload || empty($payload['email'])) {
        throw new Exception('Invalid token payload');
    }
    
    $email = $payload['email'];
    $name = $payload['name'] ?? 'Google User';
    $google_id = $payload['sub'] ?? null;
    
    // Check if user exists
    $stmt = $pdo->prepare('SELECT id, email, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // User exists - login
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Logged in successfully',
            'redirect' => $user['role'] === 'restaurant' 
                ? '/restaurant/dashboard.php' 
                : '/customer/dashboard.php'
        ]);
        exit;
    }
    
    // New user - create account based on mode
    if ($mode === 'signup') {
        // Create new customer account
        $role = 'customer';
        $phone = $payload['phone'] ?? '';
        
        // Generate a random password (user logged in via Google)
        $password = bin2hex(random_bytes(16));
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        try {
            $stmt = $pdo->prepare('
                INSERT INTO users (name, email, phone, password_hash, role, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ');
            $stmt->execute([$name, $email, $phone, $password_hash, $role, 'active']);
            $user_id = $pdo->lastInsertId();
            
            // Store Google ID if provided
            if ($google_id) {
                $stmt = $pdo->prepare('
                    UPDATE users SET google_id = ? WHERE id = ?
                ');
                $stmt->execute([$google_id, $user_id]);
            }
            
            // Save location if provided
            if ($latitude && $longitude) {
                $stmt = $pdo->prepare('
                    INSERT INTO user_addresses (user_id, address_type, name, latitude, longitude, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ');
                $stmt->execute([
                    $user_id,
                    'home',
                    $location_name ?? 'Home',
                    $latitude,
                    $longitude
                ]);
            }
            
            // Login the new user
            $_SESSION['user'] = [
                'id' => $user_id,
                'email' => $email,
                'role' => $role
            ];
            
            echo json_encode([
                'success' => true,
                'message' => 'Account created successfully',
                'redirect' => '/customer/dashboard.php'
            ]);
            exit;
            
        } catch (PDOException $e) {
            // Check if email already exists
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Email already registered'
                ]);
                exit;
            }
            throw $e;
        }
    } else {
        // Login mode but user doesn't exist
        echo json_encode([
            'success' => false,
            'message' => 'No account found. Please sign up first.'
        ]);
        exit;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication error: ' . $e->getMessage()
    ]);
    exit;
}
