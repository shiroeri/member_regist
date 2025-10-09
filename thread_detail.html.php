<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($thread['title']) ?> - スレッド詳細</title>
    <link rel="stylesheet" href="member_regist.css">
    <style>
        .container { max-width: 800px; margin: 30px auto; padding: 20px; border: 1px solid #ccc; }
        .header { border-bottom: 2px solid #ccc; }
        .header-title { padding-bottom: 10px; margin-bottom: 20px; }
        .header-created_at { text-align: right; font-size: 1em; }
        .thread-content { border: 1px solid #eee; padding: 20px; margin-bottom: 30px; }
        .thread-meta { font-size: 0.8em; color: #666; margin-top: 10px; }
        .comment-section h2 { border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 15px; }
        .comment-form textarea { width: 100%; min-height: 100px; padding: 10px; box-sizing: border-box; }
        .btn { padding: 8px 15px; cursor: pointer; border-radius: 4px; text-decoration: none; }
        .btn-primary { background-color: #007bff; color: white; border: none; }
        .btn-secondary { background-color: #6c757d; color: white; border: none; }
    </style>
</head>
<body>
    <div class="container">

        <div style="text-align: right; margin-bottom: 30px;">
            <a href="thread.php" class="btn btn-secondary">スレッド一覧に戻る</a>
        </div>

        <?php if (isset($error_message)): ?>
            <div style="color: red; margin-bottom: 20px;"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="header">
            <h1 class="header-title"><?= htmlspecialchars($thread['title']) ?></h1>
        
            <h2 class="header-created_at">登録日時: <?= htmlspecialchars($thread['created_at']) ?></h2>    
        </div>
        
        
        
        <div class="thread-content">
    
            <div class="thread-meta">
                投稿者: <?= htmlspecialchars($thread['member_name']) ?> <?= htmlspecialchars($thread['formatted_created_at']) ?>
            </div>
            <p><?= nl2br(htmlspecialchars($thread['content'])) ?></p>
        </div>

        <div class="comment-section">
            <?php if ($is_logged_in): ?>
                <h2>コメント投稿</h2>
                <form action="thread_detail.php?id=<?= htmlspecialchars($thread['id']) ?>" method="POST" class="comment-form">
                    <textarea name="comment"></textarea>
                    <div style="text-align: right; margin-top: 10px;">
                        <button type="submit" class="btn btn-primary">コメントする</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>