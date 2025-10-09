<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($thread['title']) ?> - スレッド詳細</title>
    <link rel="stylesheet" href="member_regist.css">
    <style>
        .container { max-width: 800px; margin: 30px auto; padding: 20px; border: 1px solid #ccc; }
        .header { border-bottom: 2px solid #ccc; }
        /* 💡 修正: タイトルとコメント数を表示するためにマージンを調整 */
        .header-title { padding-bottom: 10px; margin-bottom: 5px; }
        .comment-count { font-size: 0.6em; color: #666; margin-left: 10px; display: block; }
        /* 💡 修正: 登録日時を投稿者名と統合したため、このクラスは不要になる可能性あり */
        .header-created_at { text-align: right; font-size: 1em; } 
        .thread-content { border: 1px solid #eee; padding: 20px; margin-bottom: 30px; }
        .thread-meta { font-size: 0.8em; color: #666; margin-top: 10px; }
        .comment-section h2 { border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 15px; }
        .comment-form textarea { width: 100%; min-height: 100px; padding: 10px; box-sizing: border-box; }
        .btn { padding: 8px 15px; cursor: pointer; border-radius: 4px; text-decoration: none; }
        .btn-primary { background-color: #007bff; color: white; border: none; }
        .btn-secondary { background-color: #6c757d; color: white; border: none; }
        /* 💡 追加: コメントアイテムとページネーションのスタイル */
        .comment-item { border-bottom: 1px dotted #eee; padding: 10px 0; }
        .pagination { display: flex; justify-content: center; margin-top: 20px; }
        .pagination a, .pagination span { padding: 8px 12px; margin: 0 5px; border: 1px solid #ddd; text-decoration: none; color: #333; }
        .pagination span.disabled { background-color: #f8f8f8; color: #ccc; cursor: default; }
    </style>
</head>
<body>
    <div class="container">

        <div style="text-align: right; margin-bottom: 30px;">
            <a href="thread.php" class="btn btn-secondary">スレッド一覧に戻る</a>
        </div>

        <div class="header">
            <h1 class="header-title">
                <?= htmlspecialchars($thread['title']) ?>
                
                <span class="comment-count"><?= $total_comments ?? 0 ?>コメント</span>
            </h1>
        
            <p class="header-created_at" style="font-size: 0.9em;">
                <?= htmlspecialchars($thread['formatted_created_at'] ?? $thread['created_at']) ?>
            </p>
        </div>
        <div class="pagination">
                    <?php 
                        $prev_link = 'thread_detail.php?id=' . $thread['id'] . '&page=' . ($current_page - 1);
                        $next_link = 'thread_detail.php?id=' . $thread['id'] . '&page=' . ($current_page + 1);
                    ?>

                    <?php if ($current_page > 1): ?>
                        <a href="<?= htmlspecialchars($prev_link) ?>">前へ</a>
                    <?php else: ?>
                        <span class="disabled">前へ</span>
                    <?php endif; ?>

                    <span style="border: none;">
                        <?= $current_page ?> / <?= $total_pages ?>
                    </span>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="<?= htmlspecialchars($next_link) ?>">次へ</a>
                    <?php else: ?>
                        <span class="disabled">次へ</span>
                    <?php endif; ?>
        </div>
        <br>
        
        <div class="thread-content">
            <div class="thread-meta">
                投稿者: <?= htmlspecialchars($thread['member_name'] ?? '退会ユーザー') ?> <?= htmlspecialchars($thread['formatted_created_at']) ?>
            </div>
            <p><?= nl2br(htmlspecialchars($thread['content'])) ?></p>
        </div>


        
        <div class="comment-section">
            <h2>コメント</h2>
            
            <?php if (empty($comments)): ?>
                <p>まだコメントはありません。</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="thread-meta">
                            <?= htmlspecialchars($comment['id']) ?>. <?= htmlspecialchars($comment['member_name'] ?? '退会ユーザー') ?> <?= htmlspecialchars($comment['formatted_created_at']) ?>
                        </div>
                        <p style="margin-bottom: 5px;"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                    </div>
                <?php endforeach; ?>

                
            <?php endif; ?>
            <div class="pagination">
                    <?php 
                        $prev_link = 'thread_detail.php?id=' . $thread['id'] . '&page=' . ($current_page - 1);
                        $next_link = 'thread_detail.php?id=' . $thread['id'] . '&page=' . ($current_page + 1);
                    ?>

                    <?php if ($current_page > 1): ?>
                        <a href="<?= htmlspecialchars($prev_link) ?>">前へ</a>
                    <?php else: ?>
                        <span class="disabled">前へ</span>
                    <?php endif; ?>

                    <span style="border: none;">
                        <?= $current_page ?> / <?= $total_pages ?>
                    </span>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="<?= htmlspecialchars($next_link) ?>">次へ</a>
                    <?php else: ?>
                        <span class="disabled">次へ</span>
                    <?php endif; ?>
            </div>
        </div>

        <div class="comment-section">
            <?php if ($is_logged_in): ?>
                <h2>コメント投稿</h2>
                <form action="thread_detail.php?id=<?= htmlspecialchars($thread['id']) ?>" method="POST" class="comment-form">
                    <textarea name="comment"><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                    <?php if (isset($error_message)): ?>
            <div style="color: red; margin-bottom: 20px;"><?= $error_message ?></div>
        <?php endif; ?>
                    <div style="text-align: right; margin-top: 10px;">
                        <button type="submit" class="btn btn-primary">コメントする</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>