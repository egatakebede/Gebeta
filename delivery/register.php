<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $phone = sanitize($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $vehicle_type = sanitize($_POST['vehicle_type'] ?? '');
    $vehicle_number = sanitize($_POST['vehicle_number'] ?? '');
    $vehicle_color = sanitize($_POST['vehicle_color'] ?? '');
    $license_number = sanitize($_POST['license_number'] ?? '');
    $license_expiry = sanitize($_POST['license_expiry'] ?? '');
    
    $errors = [];
    
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if (!$email) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($vehicle_number) || empty($license_number)) {
        $errors[] = 'Vehicle and license details are required';
    }
    
    if (!empty($errors)) {
        flash_set('error', implode(', ', $errors));
        redirect('/delivery/register.php');
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$email, $phone]);
        if ($stmt->fetch()) {
            flash_set('error', 'Email or phone already registered');
            redirect('/delivery/register.php');
        }
        
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'delivery')");
        $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
        $user_id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO delivery_partners (user_id, phone, vehicle_type, vehicle_number, vehicle_color, license_number, license_expiry) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $phone, $vehicle_type, $vehicle_number, $vehicle_color, $license_number, $license_expiry]);
        
        $pdo->commit();
        
        flash_set('success', 'Registration successful! Your documents are under verification.');
        redirect('/delivery/pending-approval.php');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Delivery registration error: ' . $e->getMessage());
        flash_set('error', 'Registration failed. Please try again.');
        redirect('/delivery/register.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Partner Registration - Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="page-content">
        <div class="container" style="max-width: 600px; margin: 40px auto;">
            <h1>🚚 Join Gebeta as Delivery Partner</h1>
            <p>Earn money delivering food in your free time</p>
            
            <?php if ($error = flash_get('error')): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="form-card">
                <fieldset>
                    <legend>Personal Information</legend>
                    
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required minlength="3">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" placeholder="+251 9..." required>
                    </div>
                </fieldset>
                
                <fieldset>
                    <legend>Vehicle Information</legend>
                    
                    <div class="form-group">
                        <label for="vehicle_type">Vehicle Type *</label>
                        <select id="vehicle_type" name="vehicle_type" required>
                            <option value="">Select vehicle type</option>
                            <option value="bike">Bike/Motorcycle</option>
                            <option value="auto">Auto/Tuk-tuk</option>
                            <option value="car">Car</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="vehicle_number">Vehicle Registration Number *</label>
                        <input type="text" id="vehicle_number" name="vehicle_number" placeholder="e.g., AA-12345" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="vehicle_color">Vehicle Color *</label>
                        <input type="text" id="vehicle_color" name="vehicle_color" placeholder="e.g., White" required>
                    </div>
                </fieldset>
                
                <fieldset>
                    <legend>Driving License</legend>
                    
                    <div class="form-group">
                        <label for="license_number">License Number *</label>
                        <input type="text" id="license_number" name="license_number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="license_expiry">License Expiry Date *</label>
                        <input type="date" id="license_expiry" name="license_expiry" required>
                    </div>
                </fieldset>
                
                <fieldset>
                    <legend>Account Password</legend>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" minlength="6" required>
                        <small>Minimum 6 characters</small>
                    </div>
                </fieldset>
                
                <button type="submit" class="primary-btn">Register as Delivery Partner</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px;">
                Already registered? <a href="/index.php">Sign in</a>
            </p>
        </div>
    </div>
</body>
</html>
