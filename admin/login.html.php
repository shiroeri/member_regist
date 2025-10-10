<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="../member_regist.css"> 
    <style>
        .container { max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn-primary { background-color: #007bff; color: white; border: none; padding: 10px 15px; cursor: pointer; border-radius: 4px; }
        .alert { color: red; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>管理画面</h1>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="login_id">ログインID</label>
                <input type="text" id="login_id" name="login_id" value="<?= htmlspecialchars($login_id) ?>">
            </div>
            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password">
            </div>
            
            <div style="text-align: right; margin-top: 20px;">
                <button type="submit" class="btn-primary">ログイン</button>
            </div>
        </form>
        <?php if ($error_message): ?>
            <div class="alert"><?= $error_message ?></div>
        <?php endif; ?>
    </div>
</body>
</html>