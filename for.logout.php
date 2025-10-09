<?php
// for.logout.php

// 1. セッションを開始
session_start();

// ----------------------------------------------------
// 2. セッションの破棄処理
// ----------------------------------------------------

// セッション変数を全てクリア
$_SESSION = array();

// クライアント側のセッションIDを保持しているクッキーを削除
// (これによりセッションが完全に無効化される)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 最終的にセッションを破壊 (サーバー側でのセッションデータ削除)
session_destroy();

// ----------------------------------------------------
// 3. ログアウト成功後の遷移 (仕様対応)
// ----------------------------------------------------

// ログアウト成功後はトップ画面へ遷移
header('Location: logout.php');
exit;