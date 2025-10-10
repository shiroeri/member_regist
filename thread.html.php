<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="member_regist.css">
    <style>
        .container { max-width: 800px; margin: 30px auto; padding: 20px; border: 1px solid #ccc; }
        .header-title { border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
        .thread-item { border: 1px solid #eee; padding: 15px; margin-bottom: 10px; }
        .thread-title { font-size: 1.2em; font-weight: bold; margin-bottom: 5px; }
        .thread-meta { font-size: 0.8em; color: #666; }
        .btn { padding: 8px 15px; cursor: pointer; border-radius: 4px; text-decoration: none; }
        .btn-primary { background-color: #007bff; color: white; border: none; }
        .btn-secondary { background-color: #6c757d; color: white; border: none; }
        .button-group { margin-top: 20px; display: flex; justify-content: space-between; align-items: center; }
        
        .search-form { margin-bottom: 20px; display: flex; } 
        .search-form input[type="text"] { 
            flex-grow: 1;
            padding: 8px; 
            border: 2px solid black; 
            margin-right: 10px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="header-title"><?= htmlspecialchars($title) ?></h1>

        <?php if ($is_logged_in): ?>
            <div style="text-align: right; margin-bottom: 20px;">
                <a href="thread_regist.php">新規スレッド作成</a>
            </div>
        <?php endif; ?>
        
        <div></div>
        <form action="thread.php" method="GET" class="search-form">
            
                <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>">  
            
            <div>
                <button type="submit" class="btn btn-primary">スレッド検索</button>
            </div>
        </form>

        <?php if (isset($error_message)): ?>
            <div style="color: red; margin-bottom: 20px;"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if (empty($threads)): ?>
            <p>スレッドはまだありません。</p>
        <?php else: ?>
            <?php foreach ($threads as $thread): ?>
                <div class="thread-item">
                    <div class="thread-title">
                        <a href="thread_detail.php?id=<?= htmlspecialchars($thread['id']) ?>" style="text-decoration: none; color: inherit;">
                            <?= htmlspecialchars($thread['title']) ?>
                        </a>
                    </div>
                    <div class="thread-meta">
                        スレッドID: <?= htmlspecialchars($thread['id']) ?> | 登録日時: <?= htmlspecialchars($thread['created_at']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <br>
        <div style="text-align: center; margin-bottom: 20px;">
            <a href="logout.php" class="btn btn-secondary">トップに戻る</a>
        </div>
    </div>
</body>
</html>