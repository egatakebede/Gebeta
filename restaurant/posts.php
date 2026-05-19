<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['restaurant']);
require_once __DIR__ . '/../includes/db.php';

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE user_id = ? LIMIT 1');
$stmt->execute([$_SESSION['user']['id']]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$restaurant) {
    redirect('/restaurant/setup.php');
}

if ($restaurant['status'] !== 'active') {
    flash_set('error', 'Your restaurant is pending approval. You can post once approved.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $restaurant['status'] === 'active') {
    $type = $_POST['type'] ?? 'text';
    $content = sanitize($_POST['content'] ?? '');
    
    $stmt = $pdo->prepare('INSERT INTO restaurant_posts (restaurant_id, type, content) VALUES (?, ?, ?)');
    $stmt->execute([$restaurant['id'], $type, $content]);
    
    flash_set('success', 'Post created successfully!');
    redirect('/restaurant/posts.php');
}

$stmt = $pdo->prepare('SELECT * FROM restaurant_posts WHERE restaurant_id = ? ORDER BY created_at DESC');
$stmt->execute([$restaurant['id']]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>Online Posts</h1>
        <a class="pill-button" href="/restaurant/dashboard.php">Dashboard</a>
    </header>
    
    <?php if ($success = flash_get('success')): ?>
        <div style="background:#E8F5E9;border:2px solid #66BB6A;border-radius:16px;padding:16px;margin:20px;color:#2E7D32;font-weight:600;">
            Yes <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error = flash_get('error')): ?>
        <div style="background:#FFEBEE;border:2px solid #EF5350;border-radius:16px;padding:16px;margin:20px;color:#C62828;font-weight:600;">
            Attention <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <main class="page-content">
        <?php if ($restaurant['status'] === 'active'): ?>
        <div style="background:#fff;border-radius:20px;padding:20px;margin-bottom:24px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <h2 style="font-size:18px;margin-bottom:16px;">Create New Post</h2>
            <form method="post">
                <select name="type" style="width:100%;padding:12px;border:2px solid var(--border-gray);border-radius:12px;margin-bottom:12px;">
                    <option value="text">📝 Text</option>
                    <option value="photo">📷 Photo</option>
                    <option value="video">🎥 Video</option>
                    <option value="voice">🎤 Voice</option>
                </select>
                <textarea name="content" placeholder="What's on your mind?" style="width:100%;min-height:100px;padding:12px;border:2px solid var(--border-gray);border-radius:12px;margin-bottom:12px;" required></textarea>
                <button type="submit" class="primary-btn">Post</button>
            </form>
        </div>
        <?php endif; ?>
        
        <h2 style="font-size:20px;margin-bottom:16px;">Your Posts</h2>
        <?php if (empty($posts)): ?>
            <div class="empty-state">No posts yet. Create your first post!</div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div style="background:#fff;border-radius:20px;padding:20px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px;">
                        <span style="background:rgba(252,128,25,0.1);color:var(--primary-orange);padding:6px 12px;border-radius:999px;font-size:12px;font-weight:700;">
                            <?= $post['type'] === 'photo' ? '📷 Photo' : ($post['type'] === 'video' ? '🎥 Video' : ($post['type'] === 'voice' ? '🎤 Voice' : '📝 Text')) ?>
                        </span>
                        <span style="font-size:13px;color:var(--gray-text);"><?= date('M d, Y g:i A', strtotime($post['created_at'])) ?></span>
                    </div>
                    <p style="color:var(--dark-text);line-height:1.6;"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    
    <footer class="bottom-bar">
        <a href="/restaurant/dashboard.php">
            <span>Home</span>
            <span>Dashboard</span>
        </a>
        <a href="/restaurant/menu.php">
            <span>Menu</span>
            <span>Menu</span>
        </a>
        <a href="/restaurant/posts.php" class="active">
            <span>Online</span>
            <span>Posts</span>
        </a>
        <a href="/restaurant/profile.php">
            <span>Profile</span>
            <span>Profile</span>
        </a>
    </footer>
</body>
</html>
