<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$purpose = in_array($_GET['purpose'] ?? '', ['register', 'reset'], true) ? $_GET['purpose'] : null;

// Guard: must have a pending session for this purpose
if ($purpose === 'register' && empty($_SESSION['pending_register'])) {
    flash_set('register_error', 'Your verification session has expired. Please try registering again.');
    redirect('/index.php');
}
if ($purpose === 'reset' && empty($_SESSION['pending_reset'])) {
    flash_set('error', 'Your reset session has expired. Please request a new code.');
    redirect('/forgot-password.php');
}
if (!$purpose) {
    flash_set('login_error', 'Invalid access attempt.');
    redirect('/index.php');
}

$email = $purpose === 'register'
    ? $_SESSION['pending_register']['email']
    : $_SESSION['pending_reset']['email'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $code = trim(implode('', array_map(fn($i) => $_POST["d$i"] ?? '', range(1, 6))));
    $code = preg_replace('/\D/', '', $code);

    error_log('OTP DEBUG - email: ' . $email . ' | code: ' . $code . ' | purpose: ' . $purpose);

    if (strlen($code) !== 6) {
        $error = 'Please enter the full 6-digit code.';
    } elseif (!verify_otp($email, $code, $purpose)) {
        $error = 'Invalid or expired code. Please try again.';
    } else {
        if ($purpose === 'register') {
            $p = $_SESSION['pending_register'];
            try {
                // Insert new user as active since email is now verified
                $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password, role, status, latitude, longitude, location_name, created_at) VALUES (?, ?, ?, ?, ?, "active", ?, ?, ?, NOW())');
                $stmt->execute([$p['name'], $p['email'], $p['phone'], $p['password'], $p['role'], $p['latitude'], $p['longitude'], $p['location_name']]);
                $userId = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
                $stmt->execute([$userId]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                login_user($userData);
                unset($_SESSION['pending_register']);

                redirect(get_dashboard_url($userData['role']));
            } catch (PDOException $e) {
                $error = "Registration failed. Please try again.";
            }
        } else {
            $_SESSION['reset_verified'] = [
                'user_id' => $_SESSION['pending_reset']['user_id']
            ];
            unset($_SESSION['pending_reset']);
            redirect('/reset-password.php');
        }
    }
}

$maskedEmail = preg_replace('/(?<=.{2}).(?=.*@)/u', '*', $email);
$title = $purpose === 'register' ? 'Verify your email' : 'Reset your password';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> · Gebeta</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#FC8019">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .verify-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, var(--bg-orange-tint) 0%, var(--bg-white) 100%);
            padding: 24px 20px;
        }
        .verify-card {
            background: #fff;
            border: 1px solid var(--border-gray);
            border-radius: 24px;
            padding: 36px 28px;
            width: 100%;
            max-width: 420px;
            text-align: center;
        }
        .verify-icon { font-size: 52px; margin-bottom: 16px; }
        .verify-card h1 { font-size: 22px; margin-bottom: 8px; }
        .verify-card p  { color: var(--gray-text); font-size: 14px; margin-bottom: 28px; }
        .otp-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 24px;
        }
        .otp-inputs input {
            width: 48px;
            height: 56px;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            border: 2px solid var(--border-gray);
            border-radius: 14px;
            padding: 0;
            margin: 0;
            transition: border-color 0.2s;
        }
        .otp-inputs input:focus {
            outline: none;
            border-color: var(--primary-orange);
        }
        .otp-inputs input.filled {
            border-color: var(--primary-orange);
            background: var(--bg-orange-tint);
        }
        .verify-card .primary-btn { width: 100%; justify-content: center; }
        .resend-row {
            margin-top: 20px;
            font-size: 13px;
            color: var(--gray-text);
        }
        .resend-row button {
            background: none;
            border: none;
            color: var(--primary-orange);
            font-weight: 700;
            font-size: 13px;
            padding: 0;
        }
        .resend-row button:disabled {
            color: var(--light-gray);
        }
        .error-msg {
            background: #FFF0F0;
            border: 1px solid #FFCDD2;
            color: var(--error-red);
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 16px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            font-size: 13px;
            color: var(--gray-text);
        }
    </style>
</head>
<body>
    <div class="verify-wrap">
        <div class="verify-card">
            <div class="verify-icon">📧</div>
            <h1><?= $title ?></h1>
            <p>We sent a 6-digit code to<br><strong><?= htmlspecialchars($maskedEmail) ?></strong></p>

            <?php if ($error): ?>
                <div class="error-msg" style="margin-bottom: 20px;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" id="otp-form">
                <?= csrf_field() ?>
                <div class="otp-inputs">
                    <input type="tel" name="d1" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" required>
                    <input type="tel" name="d2" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                    <input type="tel" name="d3" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                    <input type="tel" name="d4" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                    <input type="tel" name="d5" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                    <input type="tel" name="d6" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                </div>
                <button class="primary-btn" type="submit" id="verify-btn">Verify</button>
            </form>

            <div class="resend-row">
                Didn't receive it?
                <button id="resend-btn" onclick="resendOtp(this)">Resend code</button>
                <span id="resend-timer"></span>
            </div>

            <a class="back-link" href="/index.php">← Back to home</a>
        </div>
    </div>

    <script>
    const inputs = document.querySelectorAll('.otp-inputs input');

    // Auto-advance and auto-submit
    inputs.forEach((input, i) => {
        input.addEventListener('input', e => {
            const val = e.target.value.replace(/\D/g, '');
            e.target.value = val.slice(-1);
            e.target.classList.toggle('filled', !!e.target.value);
            if (val && i < inputs.length - 1) inputs[i + 1].focus();
            if (i === inputs.length - 1 && val) {
                // All filled — auto submit
                const allFilled = [...inputs].every(inp => inp.value);
                if (allFilled) document.getElementById('otp-form').submit();
            }
        });

        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !input.value && i > 0) {
                inputs[i - 1].focus();
                inputs[i - 1].value = '';
                inputs[i - 1].classList.remove('filled');
            }
        });

        // Handle paste on any input
        input.addEventListener('paste', e => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            [...pasted.slice(0, 6)].forEach((char, j) => {
                if (inputs[j]) {
                    inputs[j].value = char;
                    inputs[j].classList.add('filled');
                }
            });
            const next = Math.min(pasted.length, inputs.length - 1);
            inputs[next].focus();
            if (pasted.length >= 6) document.getElementById('otp-form').submit();
        });
    });

    inputs[0].focus();

    // Resend with 60s cooldown
    let cooldown = 0;
    const timerEl = document.getElementById('resend-timer');
    const resendBtn = document.getElementById('resend-btn');

    function startCooldown(seconds) {
        cooldown = seconds;
        resendBtn.disabled = true;
        const tick = setInterval(() => {
            cooldown--;
            timerEl.textContent = cooldown > 0 ? `(${cooldown}s)` : '';
            if (cooldown <= 0) {
                clearInterval(tick);
                resendBtn.disabled = false;
            }
        }, 1000);
    }

    async function resendOtp(btn) {
        btn.disabled = true;
        try {
            const res  = await fetch('/api/resend-otp.php', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                timerEl.textContent = '';
                startCooldown(60);
                // Show inline success
                const msg = document.createElement('div');
                msg.style.cssText = 'color:#2e7d32;font-size:13px;margin-top:8px;';
                msg.textContent = 'Code resent! Check your inbox.';
                btn.parentElement.appendChild(msg);
                setTimeout(() => msg.remove(), 4000);
            } else {
                btn.disabled = false;
                alert(data.message || 'Could not resend. Try again.');
            }
        } catch {
            btn.disabled = false;
        }
    }

    // Start initial cooldown so they can't spam immediately
    startCooldown(60);
    </script>
</body>
</html>
