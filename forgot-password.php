<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        flash_set('forgot_error', 'Please enter a valid email address.');
        header('Location: /forgot-password.php'); exit;
    }
    
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        flash_set('forgot_error', 'No account found with that email.');
        header('Location: /forgot-password.php'); exit;
    }
    
    $_SESSION['pending_reset'] = [
        'user_id' => $user['id'],
        'email' => $email,
        'name' => $user['name']
    ];
    
    // Dispatch OTP
    $sent = send_otp_email($email, $user['name'], 'reset');
    if (!$sent) {
        // We still redirect to verify.php because they can try "Resend" 
        // or check the server debug logs for the code.
        flash_set('forgot_error', 'There was an issue sending the email, but you can request a resend shortly.');
    }
    header('Location: /verify.php?purpose=reset'); exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .forgot-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, var(--bg-orange-tint) 0%, var(--bg-white) 100%);
            padding: 24px 20px;
        }

        /* Ensure content doesn't get clipped on small screens */
        @media (max-width: 480px), (max-height: 700px) {
            .forgot-wrap { display: block; height: auto; }
            .forgot-card { margin: 0 auto; }
        }

        .forgot-card {
            background: #fff;
            border: 1px solid var(--border-gray);
            border-radius: 24px;
            padding: 36px 28px;
            width: 100%;
            max-width: 420px;
        }
        .forgot-card h1 { font-size: 22px; margin-bottom: 8px; }
        .forgot-card p { color: var(--gray-text); font-size: 14px; margin-bottom: 24px; }
        .forgot-card label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; }
        .forgot-card input { width: 100%; padding: 12px; border: 2px solid var(--border-gray); border-radius: 12px; font-size: 15px; margin-bottom: 16px; }
        .forgot-card .primary-btn { width: 100%; justify-content: center; }
        .back-link { display: inline-block; margin-top: 16px; font-size: 14px; color: var(--primary-orange); }
    </style>
</head>
<body>
    <div class="forgot-wrap">
        <div class="forgot-card">
            <h1>Forgot Password?</h1>
            <p>Enter your email and we'll send you a code to reset your password.</p>
            
            <?php if ($error = flash_get('forgot_error')): ?>
                <div style="background:#FFF0F0;border:1px solid #FFCDD2;color:var(--error-red);border-radius:12px;padding:10px 14px;font-size:13px;margin-bottom:16px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <?= csrf_field() ?>
                <input type="email" name="email" placeholder="you@example.com" required autofocus>
                <button class="primary-btn" type="submit">Send Reset Code</button>
            </form>
            
            <a class="back-link" href="/login.php">← Back to login</a>
        </div>
    </div>
</body>
</html>
