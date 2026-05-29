<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['restaurant']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->prepare('SELECT id FROM restaurants WHERE user_id = ? LIMIT 1');
$stmt->execute([$_SESSION['user']['id']]);
if ($stmt->fetch()) {
    redirect('/restaurant/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfcre_verify();
    
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $cuisine_type = sanitize($_POST['cuisine_type'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    
    if (!$name || !$cuisine_type || !$location || !$phone) {
        flash_set('error', 'Please fill all required fields.');
        redirect('/restaurant/setup.php');
    }
    
    $stmt = $pdo->prepare('INSERT INTO restaurants (user_id, name, description, cuisine_type, location, phone, status, rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$_SESSION['user']['id'], $name, $description, $cuisine_type, $location, $phone, 'pending', 0]);
    
    flash_set('success', 'Restaurant created! Waiting for admin approval.');
    redirect('/restaurant/dashboard.php');
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Restaurant · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .setup-wrap {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--bg-orange-tint) 0%, var(--bg-white) 100%);
            padding: 24px 20px;
        }
        .setup-card {
            background: #fff;
            border: 1px solid var(--border-gray);
            border-radius: 24px;
            padding: 32px 28px;
            max-width: 600px;
            margin: 0 auto;
        }
        .setup-card h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }
        .setup-card p {
            color: var(--gray-text);
            font-size: 14px;
            margin-bottom: 24px;
        }
        .setup-card label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        .setup-card input, .setup-card textarea, .setup-card select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-gray);
            border-radius: 12px;
            font-size: 15px;
            margin-bottom: 16px;
        }
        .setup-card textarea {
            min-height: 100px;
            resize: vertical;
        }
        .setup-card .primary-btn {
            width: 100%;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="setup-wrap">
        <div class="setup-card">
            <h1>Hawassa Setup Your Restaurant</h1>
            <p>Tell us about your restaurant to get started on Gebeta</p>
            
            <?php if ($error = flash_get('error')): ?>
                <div style="background:#FFF0F0;border:1px solid #FFCDD2;color:var(--error-red);border-radius:12px;padding:10px 14px;font-size:13px;margin-bottom:16px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <?= csrf_field() ?>
                <label>Restaurant Name *</label>
                <input type="text" name="name" placeholder="e.g. Habesha Restaurant" required>
                
                <label>Description</label>
                <textarea name="description" placeholder="Tell customers about your restaurant..."></textarea>
                
                <label>Cuisine Type *</label>
                <select name="cuisine_type" required>
                    <option value="">Select cuisine type</option>
                    <option value="Ethiopian">Ethiopian</option>
                    <option value="Italian">Italian</option>
                    <option value="Chinese">Chinese</option>
                    <option value="Fast Food">Fast Food</option>
                    <option value="Cafe">Cafe</option>
                    <option value="Mixed">Mixed</option>
                    <option value="Other">Other</option>
                </select>
                
                <label>Location *</label>
                <input type="text" name="location" placeholder="e.g. Piassa, Hawassa" required>
                
                <label>Phone Number *</label>
                <input type="tel" name="phone" placeholder="+251 912 345 678" required>
                
                <button class="primary-btn" type="submit">Create Restaurant</button>
            </form>
        </div>
    </div>
</body>
</html>
