<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Dashboard · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .pending-container {
            min-height: calc(100vh - 140px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .pending-card {
            background: #fff;
            border: 2px solid var(--border-gray);
            border-radius: 24px;
            padding: 48px 32px;
            max-width: 500px;
            text-align: center;
        }
        .pending-icon {
            font-size: 72px;
            margin-bottom: 24px;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .pending-card h1 {
            font-size: 24px;
            margin-bottom: 12px;
            color: var(--text-dark);
        }
        .pending-card p {
            color: var(--gray-text);
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .status-box {
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            font-weight: 600;
        }
        .status-box.pending {
            background: #FFF3E0;
            border: 2px solid #FF9800;
            color: #E65100;
        }
        .status-box.suspended {
            background: #FFEBEE;
            border: 2px solid #F44336;
            color: #C62828;
        }
        .info-list {
            background: var(--bg-gray);
            border-radius: 12px;
            padding: 20px;
            text-align: left;
            margin-bottom: 24px;
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            color: var(--text-dark);
            font-size: 14px;
        }
        .info-item span:first-child {
            font-size: 20px;
        }
        .contact-support {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary-orange);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .contact-support:hover {
            background: #e67316;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <header class="page-header">
        <h1>🏪 <?= htmlspecialchars($restaurant['name']) ?></h1>
        <span class="status-badge" style="background:<?= $restaurant['status'] === 'pending' ? '#FFF3E0' : '#FFEBEE' ?>;color:<?= $restaurant['status'] === 'pending' ? '#E65100' : '#C62828' ?>">
            <?= ucfirst($restaurant['status']) ?>
        </span>
    </header>
    
    <main class="pending-container">
        <div class="pending-card">
            <div class="pending-icon"><?= $restaurant['status'] === 'pending' ? '⏳' : '🚫' ?></div>
            
            <h1><?= $restaurant['status'] === 'pending' ? 'Application Under Review' : 'Account Suspended' ?></h1>
            
            <p>
                <?php if ($restaurant['status'] === 'pending'): ?>
                    Thank you for submitting your restaurant details! Our team is currently reviewing your application. This usually takes 24-48 hours.
                <?php else: ?>
                    Your restaurant account has been suspended. Please contact support for more information.
                <?php endif; ?>
            </p>
            
            <div class="status-box <?= $restaurant['status'] === 'pending' ? 'pending' : 'suspended' ?>">
                <?= $restaurant['status'] === 'pending' ? '⏰ Pending Admin Approval' : '⚠️ Account Suspended' ?>
            </div>
            
            <div class="info-list">
                <div class="info-item">
                    <span>📋</span>
                    <span><strong>Restaurant:</strong> <?= htmlspecialchars($restaurant['name']) ?></span>
                </div>
                <div class="info-item">
                    <span>🍽️</span>
                    <span><strong>Cuisine:</strong> <?= htmlspecialchars($restaurant['cuisine_type']) ?></span>
                </div>
                <div class="info-item">
                    <span>📍</span>
                    <span><strong>Location:</strong> <?= htmlspecialchars($restaurant['address']) ?></span>
                </div>
                <div class="info-item">
                    <span>📞</span>
                    <span><strong>Phone:</strong> <?= htmlspecialchars($restaurant['phone']) ?></span>
                </div>
            </div>
            
            <?php if ($restaurant['status'] === 'pending'): ?>
                <p style="font-size: 13px; color: var(--gray-text); margin-bottom: 16px;">
                    You'll receive an email notification once your application is approved. In the meantime, you can prepare your menu items.
                </p>
            <?php endif; ?>
            
            <a href="mailto:support@gebeta.com" class="contact-support">Contact Support</a>
        </div>
    </main>
    
    <footer class="bottom-bar">
        <a href="/restaurant/dashboard.php" class="active">
            <span>🏠</span>
            <span>Dashboard</span>
        </a>
        <a href="/restaurant/profile.php">
            <span>👤</span>
            <span>Profile</span>
        </a>
    </footer>
</body>
</html>
