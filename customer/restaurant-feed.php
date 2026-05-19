<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('/customer/dashboard.php');

$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$id]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$restaurant) redirect('/customer/dashboard.php');

// Get restaurant posts with reaction counts
$stmt = $pdo->prepare('
    SELECT p.*, 
    (SELECT COUNT(*) FROM post_reactions WHERE post_id = p.id AND reaction_type = "like") as like_count,
    (SELECT COUNT(*) FROM post_reactions WHERE post_id = p.id AND reaction_type = "love") as love_count,
    (SELECT COUNT(*) FROM post_reactions WHERE post_id = p.id AND reaction_type = "wow") as wow_count,
    (SELECT reaction_type FROM post_reactions WHERE post_id = p.id AND user_id = ?) as user_reaction
    FROM restaurant_posts p 
    WHERE p.restaurant_id = ? 
    ORDER BY p.created_at DESC
');
$stmt->execute([$_SESSION['user']['id'], $id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's rating
$stmt = $pdo->prepare('SELECT rating, review FROM restaurant_ratings WHERE restaurant_id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user']['id']]);
$userRating = $stmt->fetch(PDO::FETCH_ASSOC);

$cartCount = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant['name']) ?> · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .post-card {
            background: #fff;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .post-type {
            background: rgba(252,128,25,0.1);
            color: var(--primary-orange);
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .reactions {
            display: flex;
            gap: 8px;
            margin: 16px 0;
            padding: 12px 0;
            border-top: 1px solid var(--border-gray);
            border-bottom: 1px solid var(--border-gray);
        }
        .reaction-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: #f8f9fa;
            border-radius: 12px;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        .reaction-btn:hover {
            transform: scale(1.1);
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .reaction-btn.active {
            background: var(--bg-orange-tint);
            border: 2px solid var(--primary-orange);
        }
        .reaction-count {
            font-size: 11px;
            font-weight: 700;
            color: var(--gray-text);
            display: block;
        }
        .comments-section {
            margin-top: 16px;
        }
        .comment {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 8px;
        }
        .comment-author {
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 4px;
        }
        .comment-text {
            font-size: 14px;
            color: var(--dark-text);
        }
        .comment-form {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        .comment-input {
            flex: 1;
            padding: 10px 14px;
            border: 2px solid var(--border-gray);
            border-radius: 999px;
            font-size: 14px;
        }
        .rating-section {
            background: linear-gradient(135deg, #FFF5ED 0%, #FFE8D6 100%);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .stars {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin: 16px 0;
        }
        .star {
            font-size: 32px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .star:hover, .star.active {
            transform: scale(1.2);
        }
    </style>
</head>
<body>
    <header class="page-header">
        <div style="display:flex;justify-content:space-between;align-items:center;width:100%;">
            <a class="pill-button" href="/customer/restaurant.php?id=<?= $id ?>">← Menu</a>
            <h1 style="font-size:18px;">Feed</h1>
            <a class="pill-button" href="/customer/cart.php">🛒 <?= $cartCount ?: '' ?></a>
        </div>
    </header>
    
    <main class="page-content">
        <div class="rating-section">
            <h2 style="text-align:center;font-size:20px;margin-bottom:8px;">Rate <?= htmlspecialchars($restaurant['name']) ?></h2>
            <div class="stars" id="rating-stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?= $userRating && $i <= $userRating['rating'] ? 'active' : '' ?>" data-rating="<?= $i ?>" onclick="rateRestaurant(<?= $i ?>)">⭐</span>
                <?php endfor; ?>
            </div>
            <p style="text-align:center;color:var(--gray-text);font-size:14px;">Current: <?= number_format($restaurant['rating'], 1) ?> ⭐</p>
        </div>
        
        <h2 style="font-size:20px;margin-bottom:16px;">📱 Posts</h2>
        
        <?php if (empty($posts)): ?>
            <div class="empty-state">No posts yet from this restaurant.</div>
        <?php else: ?>
            <?php foreach ($posts as $post): 
                $stmt = $pdo->prepare('SELECT c.*, u.name as user_name FROM post_comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at DESC LIMIT 5');
                $stmt->execute([$post['id']]);
                $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
                <div class="post-card" data-post-id="<?= $post['id'] ?>">
                    <div class="post-header">
                        <span class="post-type">
                            <?= $post['type'] === 'photo' ? '📷 Photo' : ($post['type'] === 'video' ? '🎥 Video' : ($post['type'] === 'voice' ? '🎤 Voice' : '📝 Text')) ?>
                        </span>
                        <span style="font-size:13px;color:var(--gray-text);"><?= date('M d, Y', strtotime($post['created_at'])) ?></span>
                    </div>
                    
                    <p style="color:var(--dark-text);line-height:1.6;margin-bottom:12px;"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                    
                    <div class="reactions">
                        <button class="reaction-btn <?= $post['user_reaction'] === 'like' ? 'active' : '' ?>" onclick="react(<?= $post['id'] ?>, 'like', this)">
                            👍
                            <span class="reaction-count"><?= $post['like_count'] ?: '' ?></span>
                        </button>
                        <button class="reaction-btn <?= $post['user_reaction'] === 'love' ? 'active' : '' ?>" onclick="react(<?= $post['id'] ?>, 'love', this)">
                            ❤️
                            <span class="reaction-count"><?= $post['love_count'] ?: '' ?></span>
                        </button>
                        <button class="reaction-btn <?= $post['user_reaction'] === 'wow' ? 'active' : '' ?>" onclick="react(<?= $post['id'] ?>, 'wow', this)">
                            😮
                            <span class="reaction-count"><?= $post['wow_count'] ?: '' ?></span>
                        </button>
                    </div>
                    
                    <div class="comments-section">
                        <strong style="font-size:14px;color:var(--dark-text);">💬 Comments (<?= count($comments) ?>)</strong>
                        <div class="comments-list" id="comments-<?= $post['id'] ?>">
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment">
                                    <div class="comment-author"><?= htmlspecialchars($comment['user_name']) ?></div>
                                    <div class="comment-text"><?= htmlspecialchars($comment['comment']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form class="comment-form" onsubmit="addComment(event, <?= $post['id'] ?>)">
                            <input type="text" class="comment-input" placeholder="Write a comment..." required>
                            <button type="submit" class="pill-button">Send</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    
    <?php $active_nav = 'home'; include __DIR__ . '/../includes/bottom-nav.php'; ?>
    
    <script src="/assets/js/script.js"></script>
    <script>
    async function react(postId, type, btn) {
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('reaction_type', type);
        
        try {
            const res = await fetch('/api/react-post.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                // Update UI
                const card = btn.closest('.post-card');
                const reactions = card.querySelectorAll('.reaction-btn');
                reactions.forEach(r => r.classList.remove('active'));
                
                if (data.action !== 'removed') {
                    btn.classList.add('active');
                }
                
                // Update counts
                const counts = data.counts;
                reactions[0].querySelector('.reaction-count').textContent = counts.like || '';
                reactions[1].querySelector('.reaction-count').textContent = counts.love || '';
                reactions[2].querySelector('.reaction-count').textContent = counts.wow || '';
            }
        } catch (e) {
            showToast('Error reacting', 'error');
        }
    }
    
    async function addComment(e, postId) {
        e.preventDefault();
        const form = e.target;
        const input = form.querySelector('.comment-input');
        const comment = input.value.trim();
        
        if (!comment) return;
        
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('comment', comment);
        
        try {
            const res = await fetch('/api/comment-post.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                const commentsList = document.getElementById('comments-' + postId);
                const commentHtml = `
                    <div class="comment">
                        <div class="comment-author">${data.comment.user_name}</div>
                        <div class="comment-text">${data.comment.comment}</div>
                    </div>
                `;
                commentsList.insertAdjacentHTML('afterbegin', commentHtml);
                input.value = '';
                showToast('Comment added!', 'success');
            }
        } catch (e) {
            showToast('Error adding comment', 'error');
        }
    }
    
    async function rateRestaurant(rating) {
        const formData = new FormData();
        formData.append('restaurant_id', <?= $id ?>);
        formData.append('rating', rating);
        
        try {
            const res = await fetch('/api/rate-restaurant.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                document.querySelectorAll('.star').forEach((star, i) => {
                    star.classList.toggle('active', i < rating);
                });
                showToast('Rating submitted!', 'success');
            }
        } catch (e) {
            showToast('Error rating', 'error');
        }
    }
    </script>
</body>
</html>
