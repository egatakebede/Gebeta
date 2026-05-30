<?php
require_once __DIR__ . '/includes/db.php';

$accounts = [
    ['Admin User', 'admin@gebeta.com', '+251911111111', 'password123', 'admin'],
    ['Abebe Tadesse', 'abebe@customer.com', '+251922222222', 'password123', 'customer'],
    ['Yodit Abyssinia Restaurant', 'yod@restaurant.com', '+251911777777', 'password123', 'restaurant'],
    ['Abebe Driver', 'abebe.driver@delivery.com', '+251921222222', 'password123', 'delivery'],
];

try {
    // Delete old accounts
    $emails = array_column($accounts, 1);
    $placeholders = implode(',', array_fill(0, count($emails), '?'));
    $pdo->prepare("DELETE FROM users WHERE email IN ($placeholders)")->execute($emails);
    
    // Insert new accounts
    $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?)');
    
    foreach ($accounts as $account) {
        $password_hash = password_hash($account[3], PASSWORD_BCRYPT);
        $stmt->execute([
            $account[0],  // name
            $account[1],  // email
            $account[2],  // phone
            $password_hash,
            $account[4],  // role
            'active'      // status
        ]);
        
        echo "✅ Created: " . htmlspecialchars($account[1]) . " (" . htmlspecialchars($account[4]) . ")<br>";
    }
    
    echo "<br><strong>✅ All test accounts created and verified!</strong>";
    
} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
}