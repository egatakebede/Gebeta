<?php
// Helper functions

function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function format_money($amount) {
    return number_format($amount, 2) . ' Birr';
}

function format_price($amount) {
    return format_money($amount);
}

function format_date($date) {
    if (empty($date)) return '-';
    return date('M d, Y', strtotime($date));
}

function format_datetime($datetime) {
    if (empty($datetime)) return '-';
    return date('M d, Y H:i', strtotime($datetime));
}

function get_status_badge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'confirmed' => '<span class="badge badge-info">Confirmed</span>',
        'preparing' => '<span class="badge badge-info">Preparing</span>',
        'ready' => '<span class="badge badge-success">Ready</span>',
        'out_for_delivery' => '<span class="badge badge-primary">Out for Delivery</span>',
        'delivered' => '<span class="badge badge-success">Delivered</span>',
        'cancelled' => '<span class="badge badge-danger">Cancelled</span>'
    ];
    return $badges[$status] ?? '<span class="badge badge-secondary">' . $status . '</span>';
}

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

function flash_set($key, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'][$key] = $message;
}

function flash_get($key) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    return null;
}

function flash_has($key) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['flash'][$key]);
}

function clean($input) {
    return sanitize($input);
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function csrf_field() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

function csrf_verify() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        die('CSRF token mismatch. Please refresh the page and try again.');
    }
}

function get_cart_count() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) return 0;
    return array_sum(array_column($_SESSION['cart'], 'quantity'));
}

function get_cart_items() {
    global $pdo;
    if (empty($_SESSION['cart'])) return [];
    $items = [];
    foreach ($_SESSION['cart'] as $cartItem) {
        $stmt = $pdo->prepare('SELECT * FROM menu_items WHERE id = ?');
        $stmt->execute([$cartItem['menu_item_id']]);
        $item = $stmt->fetch();
        if ($item) {
            $item['quantity'] = $cartItem['quantity'];
            $item['restaurant_id'] = $cartItem['restaurant_id'];
            $items[] = $item;
        }
    }
    return $items;
}

function get_cart_total() {
    $items = get_cart_items();
    $total = 0;
    foreach ($items as $item) {
        $total += ($item['price'] * $item['quantity']);
    }
    return $total;
}

/**
 * Email and OTP Helpers
 */
function send_email($to, $subject, $message) {
    // Priority: Constant from config.php, then Environment Variable
    $apiKey = (defined('BREVO_API_KEY') && BREVO_API_KEY !== '') ? BREVO_API_KEY : env_get('BREVO_API_KEY');

    if ($apiKey) {
        $url = 'https://api.brevo.com/v3/smtp/email';
        $data = [
            'sender' => ['name' => defined('SITE_NAME') ? SITE_NAME : 'Gebeta', 'email' => 'egatakebede7@gmail.com'],
            'to' => [['email' => $to]],
            'subject' => $subject,
            'htmlContent' => $message
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'api-key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("Brevo: Email successfully sent to $to");
            return true;
        }
        
        if ($err) {
            error_log("Brevo: Curl Error: " . $err);
        } else {
            error_log("Brevo: API Error (HTTP $httpCode): " . $response);
        }
        return false;
    }

    // Fallback to mail() if Brevo is not configured
    error_log("Brevo: API Key not found, falling back to local mail()");
    $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: Gebeta <egatakebede7@gmail.com>\r\nReply-To: support@gebeta.com\r\n";
    return @mail($to, $subject, $message, $headers);
}

function generate_otp($email, $purpose) {
    global $pdo;
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    $stmt = $pdo->prepare("INSERT INTO otps (email, code, purpose, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$email, $code, $purpose, $expires]);
    
    return $code;
}

function verify_otp($email, $code, $purpose) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM otps WHERE email = ? AND code = ? AND purpose = ? AND expires_at >= ? AND used = 0 ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$email, $code, $purpose, date('Y-m-d H:i:s')]);
    $otp = $stmt->fetch();
    
    if ($otp) {
        $update = $pdo->prepare("UPDATE otps SET used = 1 WHERE id = ?");
        $update->execute([$otp['id']]);
        return true;
    }
    return false;
}

function send_otp_email($email, $name, $purpose) {
    $code = generate_otp($email, $purpose);
    
    // Log the OTP to the server error log so you can see it "on time" even if email fails
    error_log("Gebeta OTP DEBUG - To: $email, Code: $code, Purpose: $purpose");
    
    $subject = "Gebeta - Verification Code";
    $message = "
        <h2>Verification Code</h2>
        <p>Hello $name,</p>
        <p>Your verification code is: <strong style='font-size: 24px; color: #FC8019;'>$code</strong></p>
        <p>This code will expire in 15 minutes.</p>";
    return send_email($email, $subject, $message);
}

// Single redirect function
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    } else {
        echo "<script>window.location.href='$url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
        exit;
    }
}

/**
 * Role Check Helpers
 */
function is_admin() {
    return ($_SESSION['user']['role'] ?? '') === 'admin';
}

function is_restaurant() {
    return ($_SESSION['user']['role'] ?? '') === 'restaurant';
}

function is_customer() {
    return ($_SESSION['user']['role'] ?? '') === 'customer';
}

function is_delivery() {
    return ($_SESSION['user']['role'] ?? '') === 'delivery';
}

?>