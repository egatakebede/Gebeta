<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (!is_logged_in()) {
    redirect('/index.php');
}

// If user already has a role selected, redirect to dashboard
if (!empty($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'customer') {
    redirect('/index.php');
}

// Check if user needs to select role (new users only)
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user']['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $user['role'] !== 'customer') {
    redirect('/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = in_array($_POST['role'] ?? '', ['customer', 'restaurant', 'delivery'], true) ? $_POST['role'] : 'customer';
    
    $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
    $stmt->execute([$role, $_SESSION['user']['id']]);
    
    $_SESSION['user']['role'] = $role;
    
    redirect('/index.php');
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Role · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .role-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, var(--bg-orange-tint) 0%, var(--bg-white) 100%);
            padding: 24px 20px;
        }
        .role-card {
            background: #fff;
            border: 1px solid var(--border-gray);
            border-radius: 24px;
            padding: 40px 32px;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        .role-card h1 {
            font-size: 26px;
            margin-bottom: 12px;
            color: var(--text-dark);
        }
        .role-card p {
            color: var(--gray-text);
            font-size: 15px;
            margin-bottom: 32px;
        }
        .role-options {
            display: grid;
            gap: 16px;
            margin-bottom: 24px;
        }
        .role-option {
            border: 3px solid var(--border-gray);
            border-radius: 16px;
            padding: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
        }
        .role-option:hover {
            border-color: var(--primary-orange);
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(252, 128, 25, 0.15);
        }
        .role-option.selected {
            border-color: var(--primary-orange);
            background: var(--bg-orange-tint);
        }
        .role-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        .role-option h3 {
            font-size: 20px;
            margin-bottom: 8px;
            color: var(--text-dark);
        }
        .role-option p {
            font-size: 14px;
            color: var(--gray-text);
            margin: 0;
        }
        .role-option input[type="radio"] {
            display: none;
        }
        .continue-btn {
            width: 100%;
            padding: 16px;
            background: var(--primary-orange);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .continue-btn:hover {
            background: #e67316;
            transform: translateY(-2px);
        }
        .continue-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="role-wrap">
        <div class="role-card">
            <h1>Welcome to Gebeta Hawassa</h1>
            <p>How would you like to use Gebeta?</p>
            
            <form method="post" id="role-form">
                <div class="role-options">
                    <label class="role-option" data-role="customer">
                        <input type="radio" name="role" value="customer" required>
                        <div class="role-icon"><img src="/assets/images/food/coffee.jpg" alt="Customer"></div>
                        <h3>I'm a Customer</h3>
                        <p>Order from cafes and restaurants in Hawassa</p>
                    </label>
                    
                    <label class="role-option" data-role="restaurant">
                        <input type="radio" name="role" value="restaurant" required>
                        <div class="role-icon"><img src="/assets/images/food/injera.jpg" alt="Restaurant"></div>
                        <h3>I'm a Restaurant Owner</h3>
                        <p>Manage my restaurant and receive orders</p>
                    </label>
                    
                    <label class="role-option" data-role="delivery">
                        <input type="radio" name="role" value="delivery" required>
                        <div class="role-icon">🚚</div>
                        <h3>I'm a Delivery Partner</h3>
                        <p>Deliver food and earn money on your schedule</p>
                    </label>
                </div>
                
                <button type="submit" class="continue-btn">Continue</button>
            </form>
        </div>
    </div>
    
    <script>
        const options = document.querySelectorAll('.role-option');
        const form = document.getElementById('role-form');
        
        options.forEach(option => {
            option.addEventListener('click', function() {
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
        
        form.addEventListener('submit', function(e) {
            const selected = document.querySelector('input[name="role"]:checked');
            if (!selected) {
                e.preventDefault();
                alert('Please select an option');
            }
        });
    </script>
</body>
</html>
