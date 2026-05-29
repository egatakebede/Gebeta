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
    
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        flash_set('login_error', 'Email and password are required');
        redirect('/index.php');
    } else {
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                flash_set('login_error', 'Invalid email or password');
                redirect('/index.php');
            } elseif ($user['status'] !== 'active') {
                $msg = $user['status'] === 'suspended' ? 'Your account has been suspended' : 'Your account is pending approval';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login · Gebeta</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #FC8019 0%, #E56B0F 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Ensure content doesn't get clipped on small screens */
        @media (max-width: 480px), (max-height: 700px) {
            body { display: block; height: auto; min-height: 100vh; padding: 20px; }
            .login-container { margin: 0 auto; }

            body.keyboard-open .login-container {
                margin-top: 0;
            }
        }
        
        .login-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 1.25rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #FC8019, #E56B0F);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-weight: bold;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }
        
        .login-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            font-size: 0.875rem;
            color: #6B7280;
        }
        
        .form-group {
            margin-bottom: 0.5rem;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.3rem;
            font-size: 0.875rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #FC8019;
            box-shadow: 0 0 0 3px rgba(252, 128, 25, 0.1);
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        
        .login-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #FC8019, #E56B0F);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(252, 128, 25, 0.3);
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #6B7280;
        }

        .login-footer a {
            color: #FC8019;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">G</div>
            <h1 class="login-title">Gebeta</h1>
            <p class="login-subtitle">Food Delivery Platform</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            ❌ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input 
                    type="email" 
                    name="email" 
                    class="form-input" 
                    placeholder="your@email.com"
                    value="<?php echo htmlspecialchars($email); ?>"
                    required
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    class="form-input" 
                    placeholder="••••••••"
                    required
                >
            </div>
            
            <button type="submit" class="login-btn">Sign In</button>
        </form>

        <div class="links" style="display: flex; justify-content: space-between; margin-top: 1rem; font-size: 0.8rem;">
            <a href="/register.php">Create Account</a>
            <a href="/forgot-password.php">Forgot Password?</a>
        </div>
        
        <div class="test-accounts" style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #eee; font-size: 11px; color: #999; text-align: center;">
            <div>Admin: admin@gebeta.com (password123)</div>
            <div>User: customer@test.com (password123)</div>
        </div>
    </div>
</body>
</html>