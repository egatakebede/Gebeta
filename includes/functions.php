<?php
// Don't require db.php here to avoid circular dependency
// Files that need $pdo should require db.php directly

function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function format_price($amount) {
    return number_format((float)$amount, 2) . ' Birr';
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function flash_set($key, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $_SESSION['flash'][$key] = $message;
}

function flash_get($key) {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (!empty($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    return null;
}

function get_cart_count() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

function get_cart_items() {
    global $pdo;

    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return [];
    }

    $cartItems = [];
    $ids = array_column($_SESSION['cart'], 'menu_item_id');
    if (empty($ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT mi.*, c.restaurant_id, r.name AS restaurant_name FROM menu_items mi JOIN categories c ON mi.category_id = c.id JOIN restaurants r ON c.restaurant_id = r.id WHERE mi.id IN ($placeholders) AND mi.is_available = 1");
    $stmt->execute($ids);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $itemsById = [];
    foreach ($items as $item) {
        $itemsById[$item['id']] = $item;
    }

    foreach ($_SESSION['cart'] as $cartItem) {
        if (isset($itemsById[$cartItem['menu_item_id']])) {
            $item = $itemsById[$cartItem['menu_item_id']];
            $item['quantity'] = max(1, (int)$cartItem['quantity']);
            $item['subtotal'] = $item['price'] * $item['quantity'];
            $cartItems[] = $item;
        }
    }
    return $cartItems;
}

function get_cart_total() {
    $items = get_cart_items();
    $total = 0;
    foreach ($items as $item) {
        $total += $item['subtotal'];
    }
    return $total;
}

/* ── OTP helpers ── */
function generate_otp(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function send_otp_email(string $email, string $name, string $purpose): bool {
    global $pdo;

    // Invalidate any previous unused OTPs for this email+purpose
    $pdo->prepare('UPDATE otps SET used = 1 WHERE email = ? AND purpose = ? AND used = 0')
        ->execute([$email, $purpose]);

    $code    = generate_otp();
    $pdo->prepare('INSERT INTO otps (email, code, purpose, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))')
        ->execute([$email, $code, $purpose]);

    $subject = $purpose === 'register' ? 'Verify your Gebeta account' : ($purpose === 'reset' ? 'Reset your Gebeta password' : 'Your Gebeta login code');
    $message = $purpose === 'reset' ? 'Use this code to reset your password:' : 'Your one-time verification code is:';
    
    $html = '
        <div style="font-family:sans-serif;max-width:480px;margin:0 auto;padding:32px;">
            <h2 style="color:#FC8019;margin-bottom:8px;">🍽️ Gebeta</h2>
            <p style="color:#282C3F;font-size:16px;">Hi ' . htmlspecialchars($name) . ',</p>
            <p style="color:#686B78;">' . $message . '</p>
            <div style="font-size:40px;font-weight:700;letter-spacing:12px;color:#282C3F;margin:24px 0;text-align:center;">' . $code . '</div>
            <p style="color:#686B78;font-size:13px;">This code expires in <strong>15 minutes</strong>. Do not share it with anyone.</p>
            <hr style="border:none;border-top:1px solid #E8E8E8;margin:24px 0;">
            <p style="color:#93959F;font-size:12px;">If you did not request this, you can safely ignore this email.</p>
        </div>';

    $payload = json_encode([
        'sender'     => ['name' => BREVO_SENDER_NAME, 'email' => BREVO_SENDER_EMAIL],
        'to'         => [['email' => $email, 'name' => $name]],
        'subject'    => $subject,
        'htmlContent'=> $html,
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . BREVO_API_KEY,
            'content-type: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        error_log("Brevo API Error [{$status}]: {$response}");
        return false;
    }
    
    return true;
}

function verify_otp(string $email, string $code, string $purpose): bool {
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT id, code FROM otps WHERE email = ? AND purpose = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$email, $purpose]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || $row['code'] !== $code) {
        return false;
    }

    $pdo->prepare('UPDATE otps SET used = 1 WHERE id = ?')->execute([$row['id']]);
    return true;
}

/* ── CSRF protection ── */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Invalid request.');
    }
}
