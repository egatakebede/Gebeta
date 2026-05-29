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
    csrf_verify();

    $type = $_POST['type'] ?? 'text';
    $content = sanitize($_POST['content'] ?? '');

    $type = in_array($type, ['text','photo','video','voice'], true) ? $type : 'text';

    $mediaUrl = null;
    if (!empty($_FILES['media']) && isset($_FILES['media']['tmp_name']) && is_uploaded_file($_FILES['media']['tmp_name'])) {
        $file = $_FILES['media'];
        $maxBytes = 25 * 1024 * 1024; // ~25MB short video cap
        if (!empty($file['size']) && (int)$file['size'] > $maxBytes) {
            flash_set('error', 'File is too large (max 25MB).');
            redirect('/restaurant/posts.php');
        }

        // Validate by selected post type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';

        $allowed = [];
        if ($type === 'photo') {
            $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
            if (!in_array($mime, $allowed, true)) {
                flash_set('error', 'Invalid photo format.');
                redirect('/restaurant/posts.php');
            }
        } elseif ($type === 'video') {
            $allowed = ['video/mp4','video/webm','video/ogg'];
            if (!in_array($mime, $allowed, true)) {
                flash_set('error', 'Invalid video format. Use mp4/webm/ogg.');
                redirect('/restaurant/posts.php');
            }
        } else {
            // For text/voice, ignore media upload
        }

        if (!empty($allowed) && in_array($type, ['photo','video'], true)) {
            $restDir = UPLOAD_DIR_POSTS . (int)$restaurant['id'] . '/';
            if (!is_dir($restDir)) {
                @mkdir($restDir, 0755, true);
            }

            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
                'video/mp4'  => 'mp4',
                'video/webm' => 'webm',
                'video/ogg'  => 'ogv',
                default       => 'bin'
            };

            $randName = bin2hex(random_bytes(16));
            $filename = $randName . '.' . $ext;
            $destPath = $restDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                flash_set('error', 'Failed to upload media.');
                redirect('/restaurant/posts.php');
            }

            $mediaUrl = '/' . trim($destPath, '/');
        }
    }

    // Prefer inserting media_url if column exists; otherwise fallback to storing in content.
    $mediaInserted = false;
    if ($mediaUrl !== null) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM restaurant_posts LIKE 'media_url'");
            $has = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
            if ($has) {
                $stmt = $pdo->prepare('INSERT INTO restaurant_posts (restaurant_id, type, content, media_url) VALUES (?, ?, ?, ?)');
                $stmt->execute([$restaurant['id'], $type, $content, $mediaUrl]);
                $mediaInserted = true;
            }
        } catch (Throwable $e) {
            // ignore and fallback
        }
    }

    if (!$mediaInserted && $mediaUrl !== null) {
        // Fallback: store media URL in content so old DB schemas still work
        $content = $content . "\n" . '[media]:' . $mediaUrl;
    }

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
            <form method="post" enctype="multipart/form-data">
                <select name="type" style="width:100%;padding:12px;border:2px solid var(--border-gray);border-radius:12px;margin-bottom:12px;">
                    <option value="text">📝 Text</option>
                    <option value="photo">📷 Photo</option>
                    <option value="video">🎥 Video</option>
                    <option value="voice">🎤 Voice</option>
                </select>
                <textarea name="content" placeholder="What's on your mind?" style="width:100%;min-height:100px;padding:12px;border:2px solid var(--border-gray);border-radius:12px;margin-bottom:12px;" required></textarea>
                <?= csrf_field() ?>

                <div style="margin-bottom:12px;">
                    <label style="display:block;font-weight:700;margin-bottom:8px;">Add photo or short video (optional)</label>
                    <input type="file" name="media" accept="image/*,video/*" style="width:100%;padding:10px;border:2px solid var(--border-gray);border-radius:12px;" />
                    <p style="margin:8px 0 0 0;font-size:12px;color:var(--gray-text);">
                        Choose <b>Photo</b> or <b>Video</b> from the post type.
                    </p>
                </div>

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
