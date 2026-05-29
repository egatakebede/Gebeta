<?php
session_start();

// Already logged in?
if (isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/includes/functions.php';
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
    csrf_verify();

    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'role' => $_POST['role'] ?? 'customer'
    ];
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $location_name = !empty($_POST['location_name']) ? trim($_POST['location_name']) : null;
    
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
                
                // Store pending registration data in session for OTP verification
                $_SESSION['pending_register'] = [
                    'name' => $formData['name'],
                    'email' => $formData['email'],
                    'phone' => $formData['phone'],
                    'password' => $hashedPassword, // Store hashed password
                    'role' => $formData['role'],
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'location_name' => $location_name
                ];

                // Send OTP for email verification
                $sent = send_otp_email($formData['email'], $formData['name'], 'register');
                if (!$sent) {
                    flash_set('register_error', 'Failed to send verification email. Please try again.');
                    redirect('/index.php');
                }

                // Redirect to OTP verification page
                redirect('/verify.php?purpose=register');
            } catch (PDOException $e) {
                $error = 'Registration failed. Please try again.';
                error_log('Registration error: ' . $e->getMessage());
            }
        }
    }
}
?><!DOCTYPE html>
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
            margin: 0;
        }

        /* Ensure content doesn't get clipped on small screens */
        @media (max-width: 480px), (max-height: 700px) {
            body { display: block; height: auto; min-height: 100vh; }
            .container { margin: 10px auto; padding: 1.25rem; }
            .header { margin-bottom: 1rem; }
            
            body.keyboard-open .container {
                margin-top: 10px;
            }
        }
        
        .container {
            background: white;
            padding: 1rem 1.25rem;
            border-radius: 1rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .header {
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .logo-icon {
            font-size: 2rem;
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
            margin-bottom: 0.4rem;
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
            padding: 0.6rem;
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
            padding: 0.5rem 0.75rem;
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
            <?= csrf_field() ?>
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
                    id="email"
                    name="email" 
                    class="form-input" 
                    placeholder="your@email.com"
                    value="<?php echo htmlspecialchars($formData['email']); ?>"
                    required
                >
                <div id="email-feedback" style="font-size: 0.75rem; margin-top: 0.25rem; font-weight: 500;"></div>
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

    <script>
        const emailInput = document.getElementById('email');
        const emailFeedback = document.getElementById('email-feedback');
        const submitBtn = document.querySelector('button[type="submit"]');
        let checkTimeout = null;

        emailInput.addEventListener('input', function() {
            const email = this.value.trim();
            
            // Reset state
            emailFeedback.textContent = '';
            submitBtn.disabled = false;
            clearTimeout(checkTimeout);

            // Basic validation before calling API
            if (!email || !email.includes('@') || email.length < 5) {
                return;
            }

            // Debounce the API call (500ms)
            checkTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`/api/check-email.php?email=${encodeURIComponent(email)}`);
                    const data = await response.json();

                    if (data.available) {
                        emailFeedback.textContent = '✓ Email is available';
                        emailFeedback.style.color = '#059669'; // Green
                    } else {
                        emailFeedback.textContent = '✗ Email is already registered';
                        emailFeedback.style.color = '#DC2626'; // Red
                        submitBtn.disabled = true;
                    }
                } catch (error) {
                    console.error('Error checking email:', error);
                }
            }, 500);
        });
    </script>
</body>
</html>
