<?php
// logout.php (トップ画面)

// 1. セッションを開始
session_start();
// ----------------------------------------------------
// 2. ログイン状態の確認
// ----------------------------------------------------

$is_logged_in = $_SESSION['is_logged_in'] ?? false;
$user_name = '';

if ($is_logged_in) {
    // ログイン状態の場合、「ようこそ〇〇様」用の氏名を取得
    $user_name = htmlspecialchars($_SESSION['name_sei']) . htmlspecialchars($_SESSION['name_mei']) . '様';
}

// ----------------------------------------------------
// 3. HTMLの出力と表示制御
// ----------------------------------------------------
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トップ画面</title>
    <style>
        .header-nav { text-align: right; padding: 10px; border-bottom: 1px solid #ccc; }
        .header-nav a { margin-left: 15px; text-decoration: none; color: blue; }
        .welcome-msg { text-align: left; margin-right: 15px; font-weight: bold; }
        .nav-content { display: flex; justify-content: flex-end; align-items: center; }
    </style>
</head>

<body>
    <header>
        <div class="header-nav">
            <div>
            <?php if ($is_logged_in): ?>
                <div class="welcome-msg">
                    <span>ようこそ <?= $user_name ?></span>
                </div>
                
                <div class="nav-content">
                    <a href="thread.php" style="margin-left: 15px;">スレッド一覧</a>
                    <a href="thread_regist.php">新規スレッド作成</a>
                    <a href="for.logout.php">ログアウト</a>
                </div>
             
            <?php else: ?>
                <div class="nav-content">
                    <a href="thread.php" style="margin-left: 15px;">スレッド一覧</a>
                    <a href="member_regist.php">新規会員登録</a>
                    <a href="login.php">ログイン</a>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </header>

    <main style="text-align: center; padding: 100px 0; background-color: #DDFFFF;">
        <h1>〇〇掲示板</h1>
    </main>
    <footer style="height: 50px; background-color: #FFFFDD;">
        <?php if ($is_logged_in): ?>
            <div style="text-align: right; margin-bottom: 30px; padding: 10px;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="member_withdrawal.php" class="btn btn-danger">退会</a> 
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </footer>
</body>
</html>