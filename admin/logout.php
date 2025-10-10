<?php
// admin/logout.php (管理者ログアウト処理)

session_start();

// 1. セッション変数を空にする
$_SESSION = array(); 

// 2. セッションを破棄
session_destroy();

// 3. ログインフォームへ遷移
header('Location: login.php');
exit;
?>