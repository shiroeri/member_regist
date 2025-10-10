<?php
// login.php

// 1. セッションを開始
session_start();

// データベース設定と接続関数を読み込み
// ⭐ 注意: このファイルの先頭に余分な文字がないか確認してください
require_once 'db_config.php'; 

// ----------------------------------------------------
// 0. 初期設定と変数定義
// ----------------------------------------------------

$title = 'ログイン画面';
$errors = [];
$formData = [
    'email' => trim($_POST['email'] ?? $_SESSION['login_form_data']['email'] ?? ''),
];

// 既にログイン済みであればトップページへ強制遷移 (仕様対応)
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    header('Location: logout.php');
    exit;
}

// エラー情報をセッションから取得し、クリア
if (isset($_SESSION['login_errors'])) {
    $errors = $_SESSION['login_errors'];
    unset($_SESSION['login_errors']);
}
if (isset($_SESSION['login_form_data'])) {
    // メールアドレスのみセッションから復元し、パスワードはクリア
    $formData['email'] = $_SESSION['login_form_data']['email'];
    unset($_SESSION['login_form_data']);
}


// --- 二重送信防止トークンの管理 ---
if (!isset($_SESSION['login_token'])) {
    $_SESSION['login_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['login_token'];


// ----------------------------------------------------
// 1. データ処理（POSTリクエストの処理）
// ----------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // POSTデータを取得し、メールアドレスをフォームデータに保存（再表示用）
    $input_email = trim($_POST['email'] ?? '');
    $input_password = $_POST['password'] ?? '';
    $formData['email'] = $input_email; 

    // 1. バリデーションの実行
    if (empty($input_email)) {
        $errors['email'] = 'メールアドレスは必須入力です。';
    } elseif (!filter_var($input_email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'メールアドレスの形式が正しくありません。';
    }
    
    if (empty($input_password)) {
        $errors['password'] = 'パスワードは必須入力です。';
    }

    // 2. 認証処理
    if (empty($errors)) {
        try {
            $pdo = getPdoConnection(); // DB接続
            
            // 該当メールアドレスのユーザー情報を取得
            $sql = "SELECT id, password, name_sei, name_mei 
                    FROM members 
                    WHERE email = :email 
                    AND deleted_at IS NULL";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':email', $input_email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // ユーザーが存在し、かつパスワードが一致するか検証
            if ($user && password_verify($input_password, $user['password'])) {
                
                // 認証成功
                // 3. セッションにログイン情報を保存
                $_SESSION['is_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name_sei'] = $user['name_sei'];
                $_SESSION['name_mei'] = $user['name_mei'];
                
                // 4. トップ画面へ遷移 (仕様対応)
                unset($_SESSION['login_token']);
                
                header('Location: logout.php'); // index.php (トップ画面)へ遷移
                exit;

            } else {
                // ユーザーが存在しない、またはパスワード不一致
                $errors['auth'] = '認証失敗';
            }
            
        } catch (PDOException $e) {
            error_log("Login DB error: " . $e->getMessage());
            $errors['global'] = 'データベース処理中にエラーが発生しました。';
        }
    }
    
    // 3. エラーがあった場合
    if (!empty($errors)) {
        // エラーとフォームデータをセッションに保存し、リダイレクト
        $_SESSION['login_errors'] = $errors;
        $_SESSION['login_form_data']['email'] = $formData['email'];
        $_SESSION['login_token'] = bin2hex(random_bytes(32)); 
        
        // リダイレクトしてGETリクエストとして再表示
        header('Location: login.php');
        exit;
    }
}

// ----------------------------------------------------
// 2. HTMLの出力
// ----------------------------------------------------

// ログインフォームのHTMLテンプレートを読み込み
require_once 'login.html.php';