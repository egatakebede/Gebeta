<?php
session_start();

// Already logged in?
if (isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';
$formData = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'role' => 'customer'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'role' => $_POST['role'] ?? 'customer'
    ];
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($formData['name'])) {
        $error = 'Full name is required';
    } elseif (strlen($formData['name']) < 2) {
        $error = 'Name must be at least 2 characters';
    } elseif (empty($formData['email'])) {
        $error = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (empty($formData['phone'])) {
        $error = 'Phone number is required';
    } elseif (empty($password)) {
        $error = 'Password is required';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number';
    } elseif (!in_array($formData['role'], ['customer', 'restaurant', 'delivery'])) {
        $error = 'Invalid role selected';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$formData['email']]);
        
        if ($stmt->fetchColumn()) {
            $error = 'Email already registered';
        } else {
            try {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                
                // Insert user
                $stmt = $pdo->prepare('
                    INSERT INTO users (name, email, phone, password, role, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $formData['name'],
                    $formData['email'],
                    $formData['phone'],
                    $hashedPassword,
                    $formData['role'],
                    'active'
                ]);
                
                $success = '✅ Account created successfully! Redirecting to login...';
                
                // Redirect after 2 seconds
                header('Refresh: 2; url=/login.php');
            } catch (PDOException $e) {
                $error = 'Registration failed. Please try again.';
                error_log('Registration error: ' . $e->getMessage());
            }
        }
    }
}
?>!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register · Gebeta</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #FC8019, #E56B0F);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1F2937;
        }
        
        .subtitle {
            color: #6B7280;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-input,
        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-family: inherit;
        }
        
        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #FC8019;
            box-shadow: 0 0 0 3px rgba(252, 128, 25, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #FC8019, #E56B0F);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 0.5rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(252, 128, 25, 0.3);
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #6B7280;
        }
        
        .login-link a {
            color: #FC8019;
            text-decoration: none;
            font-weight: 600;
        }
        
        .password-requirements {
            background: #F9FAFB;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
            font-size: 0.75rem;
            color: #6B7280;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .requirement-icon {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #E5E7EB;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
            font-size: 0.65rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-icon">🍽️</div>
            <h1 class="title">Create Account</h1>
            <p class="subtitle">Join Gebeta Today</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input 
                    type="text" 
                    name="name" 
                    class="form-input" 
                    placeholder="John Doe"
                    value="<?php echo htmlspecialchars($formData['name']); ?>"
                    required
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input 
                    type="email" 
                    name="email" 
                    class="form-input" 
                    placeholder="your@email.com"
                    value="<?php echo htmlspecialchars($formData['email']); ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input 
                    type="tel" 
                    name="phone" 
                    class="form-input" 
                    placeholder="+251911111111"
                    value="<?php echo htmlspecialchars($formData['phone']); ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label class="form-label">Register As</label>
                <select name="role" class="form-select" required>
                    <option value="customer" <?php echo $formData['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                    <option value="restaurant" <?php echo $formData['role'] === 'restaurant' ? 'selected' : ''; ?>>Restaurant Owner</option>
                    <option value="delivery" <?php echo $formData['role'] === 'delivery' ? 'selected' : ''; ?>>Delivery Partner</option>
                </select>
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
            
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input 
                    type="password" 
                    name="confirm_password" 
                    class="form-input" 
                    placeholder="••••••••"
                    required
                >
            </div>
            
            <button type="submit" class="btn">Create Account</button>
        </form>
        
        <div class="password-requirements">
            <strong>Password Requirements:</strong>
            <div class="requirement">
                <span class="requirement-icon">✓</span>
                At least 8 characters
            </div>
            <div class="requirement">
                <span class="requirement-icon">✓</span>
                One uppercase letter (A-Z)
            </div>
            <div class="requirement">
                <span class="requirement-icon">✓</span>
                One number (0-9)
            </div>
        </div>
        
        <div class="login-link">
            Already have an account? <a href="/login.php">Sign in</a>
        </div>
    </div>
</body>
</html>

            if ($existing) {
                flash_set('login_error', 'This email is already registered. Please sign in below.');
                redirect('/index.php');
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $_SESSION['pending_register'] = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => $hashedPassword,
                    'role' => $role,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'location_name' => $location_name
                ];
                
                send_otp_email($email, $name, 'register');
                redirect('/verify.php?purpose=register');
            }
        } catch (PDOException $e) {
            flash_set('register_error', 'Database error. Please try again later.');
            redirect('/index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Gebeta</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:Arial,sans-serif;background:linear-gradient(135deg,#FC8019,#F97316);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .register-box{background:#fff;border-radius:20px;padding:40px;width:100%;max-width:450px;box-shadow:0 20px 40px rgba(0,0,0,0.1)}
        h1{color:#FC8019;margin-bottom:10px;text-align:center}
        .subtitle{text-align:center;color:#666;margin-bottom:30px}
        input,select{width:100%;padding:12px;margin:10px 0;border:1px solid #ddd;border-radius:8px;font-size:16px}
        button{width:100%;padding:12px;background:#FC8019;color:#fff;border:none;border-radius:8px;font-size:16px;cursor:pointer;margin-top:10px}
        button:hover{background:#E56B0F}
        .error{background:#fee;color:#c00;padding:10px;border-radius:8px;margin-bottom:15px}
        .success{background:#d4edda;color:#155724;padding:10px;border-radius:8px;margin-bottom:15px}
        .login-link{text-align:center;margin-top:20px;color:#666}
        .login-link a{color:#FC8019;text-decoration:none}
    </style>
</head>
<body>
    <div class="register-box">
        <h1>🍽️ Create Account</h1>
        <div class="subtitle">Join Gebeta today</div>
        
        <?php if($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?= csrf_field() ?>
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" id="regEmail" placeholder="Email Address" required>
            <div id="emailFeedback" style="font-size:12px;margin-top:-8px;margin-bottom:10px;"></div>
            <input type="tel" name="phone" placeholder="Phone Number" required>
            <select name="role">
                <option value="customer">Customer</option>
                <option value="restaurant">Restaurant Owner</option>
                <option value="delivery">Delivery Partner</option>
            </select>
            <input type="password" name="password" placeholder="Password (min 6 characters)" required>
            <button type="submit">Register</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="/login.php">Login here</a>
        </div>
    </div>

    <script>
    document.getElementById('regEmail').addEventListener('blur', async (e) => {
        const email = e.target.value;
        if (!email.includes('@')) return;
        
        const res = await fetch(`/api/check-email.php?email=${encodeURIComponent(email)}`);
        const data = await res.json();
        const feedback = document.getElementById('emailFeedback');
        
        if (!data.available) {
            feedback.textContent = '❌ Email already registered';
            feedback.style.color = 'red';
        } else {
            feedback.textContent = '✅ Email is available';
            feedback.style.color = 'green';
        }
    });
    </script>
</body>
</html>
