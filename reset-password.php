<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (empty($_SESSION['reset_verified'])) {
    redirect('/forgot-password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    
    if (strlen($password) < 6) {
        flash_set('error', 'Password must be at least 6 characters.');
        redirect('/reset-password.php');
    }
    
    if ($password !== $confirm) {
        flash_set('error', 'Passwords do not match.');
        redirect('/reset-password.php');
    }
    
    $userId = $_SESSION['reset_verified']['user_id'];
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->execute([$passwordHash, $userId]);
    
    unset($_SESSION['reset_verified']);
    flash_set('success', 'Password reset successfully! Please login.');
    redirect('/index.php');
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .reset-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, var(--bg-orange-tint) 0%, var(--bg-white) 100%);
            padding: 24px 20px;
        }

        /* Ensure content doesn't get clipped on small screens */
        @media (max-width: 480px), (max-height: 700px) {
            .reset-wrap { display: block; height: auto; }
            .reset-card { margin: 0 auto; }
        }

        .reset-card {
            background: #fff;
            border: 1px solid var(--border-gray);
            border-radius: 24px;
            padding: 36px 28px;
            width: 100%;
            max-width: 420px;
        }
        .reset-card h1 { font-size: 22px; margin-bottom: 8px; }
        .reset-card p { color: var(--gray-text); font-size: 14px; margin-bottom: 24px; }
        .reset-card label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; }
        .reset-card input { width: 100%; padding: 12px; border: 2px solid var(--border-gray); border-radius: 12px; font-size: 15px; margin-bottom: 16px; }
        .reset-card .primary-btn { width: 100%; justify-content: center; }
    </style>
</head>
<body>
    <div class="reset-wrap">
        <div class="reset-card">
            <h1>Set New Password</h1>
            <p>Enter your new password below.</p>
            
            <?php if ($error = flash_get('error')): ?>
                <div style="background:#FFF0F0;border:1px solid #FFCDD2;color:var(--error-red);border-radius:12px;padding:10px 14px;font-size:13px;margin-bottom:16px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <?= csrf_field() ?>
                <label>New Password</label>
                <input type="password" name="password" placeholder="At least 6 characters" required autofocus>
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Re-enter password" required>
                <button class="primary-btn" type="submit">Reset Password</button>
            </form>
        </div>
    </div>
</body>
</html>
