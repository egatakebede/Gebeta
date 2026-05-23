<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['delivery']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->prepare("SELECT * FROM delivery_partners WHERE user_id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

if ($partner && $partner['verified']) {
    redirect('/delivery/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approval - Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="page-content">
        <div class="container" style="max-width: 600px; margin: 100px auto; text-align: center;">
            <div style="font-size: 80px; margin-bottom: 20px;">⏳</div>
            <h1>Verification in Progress</h1>
            <p style="font-size: 18px; color: #666; margin: 20px 0;">
                Thank you for registering as a delivery partner!
            </p>
            <p>
                Our team is currently reviewing your documents. This usually takes 24-48 hours.
            </p>
            <p>
                You will receive an email notification once your account is approved.
            </p>
            
            <div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 30px 0;">
                <h3>What happens next?</h3>
                <ul style="text-align: left; max-width: 400px; margin: 20px auto;">
                    <li>✅ Document verification (24-48 hours)</li>
                    <li>📧 Email notification on approval</li>
                    <li>🚚 Start accepting deliveries</li>
                    <li>💰 Earn money on your schedule</li>
                </ul>
            </div>
            
            <a href="/logout.php" class="primary-btn">Logout</a>
        </div>
    </div>
</body>
</html>
