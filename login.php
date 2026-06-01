<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $role = $_SESSION['user']['role'] ?? 'customer';
    redirect(get_dashboard_url($role));
}

$error = flash_get('login_error') ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        flash_set('login_error', 'Email and password are required');
        redirect('/index.php');
    } else {
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                error_log("Login failed for email: $email");
                flash_set('login_error', 'Invalid email or password');
                redirect('/index.php');
            } elseif (strtolower($user['status']) !== 'active') {
                $msg = strtolower($user['status']) === 'suspended' ? 'Your account has been suspended' : 'Your account is pending approval';
                flash_set('login_error', $msg);
                redirect('/index.php');
            } else {
                login_user($user);

                // Redirect based on role
                redirect(get_dashboard_url($user['role']));
                exit;
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            flash_set('login_error', 'Database error. Please try again later.');
            redirect('/index.php');
        }
    }
}

// If accessed via GET, redirect to home
redirect('/index.php');