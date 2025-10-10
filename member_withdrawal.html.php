<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="member_regist.css">
    <style>
        .container { max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; }
        .alert { color: red; margin-bottom: 20px; }
        .btn { padding: 8px 15px; cursor: pointer; border-radius: 4px; text-decoration: none; }
        .btn-danger { background-color: #dc3545; color: white; border: none; }
        .btn-secondary { background-color: #6c757d; color: white; border: none; }
        .withdrawal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .withdrawal-header h1 { flex-grow: 1; text-align: center; margin: 0; }
    </style>
</head>
<body>
    <div class="container">
    <div style="text-align: right;">
        <a href="logout.php" class="btn btn-secondary" style="margin-right: 10px;">トップに戻る</a>
    </div>
        <div style="text-align: center">
            <h1>退会</h1>
        
            <?php if (isset($error_message) && $error_message): ?>
                <p class="alert"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>

            <p style="margin-top: 30px;">退会しますか？</p>
        
            <div>
                <form action="member_withdrawal.php" method="POST" style="margin-top: 20px;">
                    <button type="submit" name="withdraw_confirm" class="btn btn-danger">
                        退会する
                    </button>   
                </form>
            </div>
            
        </div>
        
    </div>
</body>
</html>