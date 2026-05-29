cat > /home/e/Gebeta/includes/auth.php << 'EOF'
<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function is_logged_in() {
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function require_login(array $roles = []) {
    if (!is_logged_in()) {
        redirect('/login.php');
        exit;
    }
    
    if (!empty($roles)) {
        $userRole = $_SESSION['user']['role'] ?? '';
        if (!in_array($userRole, $roles, true)) {
            redirect('/index.php');
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
        'login_time' => time()
    ];
}

function logout_user() {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

function redirect($url) {
    // Check if headers have already been sent
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    } else {
        // Fallback if headers already sent
        echo "<script>window.location.href='$url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
        exit;
    }
}
?>
EOF