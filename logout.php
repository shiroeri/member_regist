<?php
// index.php (トップ画面)

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
    </style>
</head>

<body>
    <header>
        <div class="header-nav">
            <?php if ($is_logged_in): ?>
                <div class="welcome-msg">
                    <span>ようこそ <?= $user_name ?></span>
                </div>
                
                <a href="for.logout.php">ログアウト</a>
                
                <?php else: ?>
                <a href="member_regist.php">新規会員登録</a>
                <a href="login.php">ログイン</a>
            <?php endif; ?>
        </div>
    </header>

    <main style="text-align: center; margin-top: 50px;">
        <h1>〇〇掲示板</h1>
        
    </main>
</body>

</html>