<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function is_logged_in() {
    return isset($_SESSION['user']) && isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']);
}

function require_login(array $roles = []) {
    // Check if user is logged in
    if (!is_logged_in()) {
        // Store the current URL to redirect back after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('/login.php');
        return;
    }
    
    // Check role restrictions
    if (!empty($roles)) {
        $userRole = $_SESSION['user']['role'] ?? '';
        if (!in_array($userRole, $roles, true)) {
            // Redirect to appropriate dashboard based on role
            switch($userRole) {
                case 'admin':
                    redirect('/admin/dashboard.php');
                    break;
                case 'restaurant':
                    redirect('/restaurant/dashboard.php');
                    break;
                case 'customer':
                    redirect('/customer/dashboard.php');
                    break;
                default:
                    redirect('/index.php');
            }
            return;
        }
    }
}

function login_user(array $user) {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? '',
        'role' => $user['role'],
        'login_time' => time()
    ];
    
    // Check if there was a redirect URL stored
    if (isset($_SESSION['redirect_after_login'])) {
        $redirect = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        redirect($redirect);
    } else {
        // Redirect to role-specific dashboard
        switch($user['role']) {
            case 'admin':
                redirect('/admin/dashboard.php');
                break;
            case 'restaurant':
                redirect('/restaurant/dashboard.php');
                break;
            case 'customer':
                redirect('/customer/dashboard.php');
                break;
            default:
                redirect('/index.php');
        }
    }
}

function logout_user() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}
?>