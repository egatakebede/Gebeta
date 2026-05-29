<?php
// Set custom session path to avoid permission issues
session_save_path('/tmp/gebeta_sessions');
if (!is_dir('/tmp/gebeta_sessions')) {
    mkdir('/tmp/gebeta_sessions', 0777, true);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
}

function require_login($roles = []) {
    // If not logged in, redirect to login
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
    
    // Check roles if specified
    if (!empty($roles)) {
        $userRole = $_SESSION['user']['role'] ?? '';
        if (!in_array($userRole, $roles)) {
            // Redirect to appropriate dashboard
            switch($userRole) {
                case 'admin': 
                    header('Location: /admin/dashboard.php'); 
                    break;
                case 'restaurant': 
                    header('Location: /restaurant/dashboard.php'); 
                    break;
                default: 
                    header('Location: /customer/dashboard.php');
            }
            exit;
        }
    }
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function login_user($user) {
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
}

function logout_user() {
    $_SESSION = array();
    session_destroy();
}
?>
