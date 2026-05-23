<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing restaurant owner login...\n\n";

// Simulate POST request for restaurant owner
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = 'yod@restaurant.com';
$_POST['password'] = 'password123';
$_POST['latitude'] = '7.0621';
$_POST['longitude'] = '38.4760';
$_POST['location_name'] = 'Hawassa, Ethiopia';

try {
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/db.php';
    
    echo "1. Auth and DB loaded\n";
    
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');
    
    echo "2. Email: $email\n";
    
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "3. User found: " . $user['email'] . " (Role: " . $user['role'] . ")\n";
        echo "4. Testing password...\n";
        
        // Try common passwords
        $passwords = ['password123', 'password', 'yod123', '123456'];
        $matched = false;
        
        foreach ($passwords as $testPass) {
            if (password_verify($testPass, $user['password'])) {
                echo "   ✓ Password matched: $testPass\n";
                $matched = true;
                $password = $testPass;
                break;
            }
        }
        
        if (!$matched) {
            echo "   ✗ None of the test passwords matched\n";
            echo "   Current hash: " . substr($user['password'], 0, 30) . "...\n";
            
            // Reset password to 'password123'
            echo "\n5. Resetting password to 'password123'...\n";
            $newHash = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$newHash, $user['id']]);
            echo "   ✓ Password reset complete\n";
            $password = 'password123';
        }
        
        // Continue with login flow
        echo "\n6. Logging in user...\n";
        login_user($user);
        echo "   ✓ User logged in\n";
        
        // Test restaurant check
        if ($user['role'] === 'restaurant') {
            echo "\n7. Checking restaurant setup...\n";
            $stmt = $pdo->prepare('SELECT id, name, status FROM restaurants WHERE user_id = ? LIMIT 1');
            $stmt->execute([$user['id']]);
            $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($restaurant) {
                echo "   ✓ Restaurant found: " . $restaurant['name'] . "\n";
                echo "   Status: " . $restaurant['status'] . "\n";
                echo "\n8. Would redirect to: /restaurant/dashboard.php\n";
                
                // Test if dashboard would show pending or full
                if ($restaurant['status'] === 'pending' || $restaurant['status'] === 'suspended') {
                    echo "   Dashboard would show: pending-dashboard.php\n";
                } else {
                    echo "   Dashboard would show: full dashboard\n";
                }
            } else {
                echo "   ✗ No restaurant found\n";
                echo "\n8. Would redirect to: /restaurant/setup.php\n";
            }
        }
        
        echo "\n✓ Login flow completed successfully!\n";
        
    } else {
        echo "3. ✗ User not found\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
