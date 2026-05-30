<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function is_logged_in() {
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function get_dashboard_url($role) {
    return match ($role) {
        'admin'      => '/admin/dashboard.php',
        'restaurant' => '/restaurant/dashboard.php',
        'delivery'   => '/delivery/dashboard.php',
        default      => '/customer/dashboard.php',
    };
}

function require_login(array $roles = []) {
    if (!is_logged_in()) {
        redirect('/index.php');
        exit;
    }
    
    if (!empty($roles)) {
        $userRole = $_SESSION['user']['role'] ?? '';
        $currentPath = $_SERVER['PHP_SELF'];

        if (!in_array($userRole, $roles, true)) {
            if ($userRole === 'admin' && strpos($currentPath, '/admin/') === false) redirect('/admin/dashboard.php');
            elseif ($userRole === 'restaurant' && strpos($currentPath, '/restaurant/') === false) redirect('/restaurant/dashboard.php');
            elseif ($userRole === 'delivery' && strpos($currentPath, '/delivery/') === false) redirect('/delivery/dashboard.php');
            elseif ($userRole !== 'admin' && $userRole !== 'restaurant' && $userRole !== 'delivery' && strpos($currentPath, '/customer/') === false) redirect('/customer/dashboard.php');
            exit;
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
        'logged_in_at' => time()
    ];
}

function logout_user() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
}
?>
