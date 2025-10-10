<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        .container { max-width: 800px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ccc; padding-bottom: 15px; margin-bottom: 20px; }
        .btn-secondary { background-color: #6c757d; color: white; border: none; padding: 8px 15px; cursor: pointer; border-radius: 4px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
        
            <h1>掲示板管理画面メインメニュー</h1>
                <div style="display: flex; align-items: center;">
                    <span style="margin-right: 20px;">ようこそ、<?= htmlspecialchars($admin_name) ?>さん</span>
                    <a href="logout.php" class="btn-secondary">ログアウト</a>
                </div>
        </div>
    </div>
</body>
</html>