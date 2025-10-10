<?php
// admin/login.php (管理画面ログイン機能のコントローラー)

session_start();
require_once '../db_config.php'; // 階層が深くなるためパスを修正

$error_message = null;
$login_id = '';
$password = '';

// ----------------------------------------------------
// 1. ログイン状態の確認（ログイン済みならトップへリダイレクト）
// ----------------------------------------------------
if (isset($_SESSION['admin_id'])) {
    header('Location: top.php');
    exit;
}

// ----------------------------------------------------
// 2. ログイン認証処理 (POST)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = $_POST['login_id'] ?? '';
    $password = $_POST['password'] ?? '';
    $login_errors = [];

    // バリデーション
    if (trim($login_id) === '') {
        $login_errors[] = 'ログインIDは必須です。'; 
    } else if (!preg_match('/^[a-zA-Z0-9]{7,10}$/', $login_id)) {
        $login_errors[] = 'ログインIDは半角英数字7〜10文字で入力してください。'; 
    }
    if (trim($password) === '') {
        $login_errors[] = 'パスワードは必須です。'; 
    } else if (!preg_match('/^[a-zA-Z0-9]{8,20}$/', $password)) { 
        $login_errors[] = 'パスワードは半角英数字8〜20文字で入力してください。'; 
    }

    if (empty($login_errors)) {
        try {
            $pdo = getPdoConnection();
            
            // 認証SQLの実行
            $sql = "SELECT id, name, password
                    FROM administers
                    WHERE login_id = :login_id
                    AND deleted_at IS NULL";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':login_id', $login_id, PDO::PARAM_STR);
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                // 認証成功
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                
                // 💡 管理画面トップへ遷移
                header('Location: top.php');
                exit;
            } else {
                // 認証失敗
                $error_message = 'ログインIDまたはパスワードが不正です。';
            }

        } catch (\PDOException $e) {
            error_log("DB Error in login.php: " . $e->getMessage());
            $error_message = 'システムエラーが発生しました。';
        }
    } else {
        $error_message = implode('<br>', $login_errors);
    }
}

// ----------------------------------------------------
// 3. ビューファイルの読み込み
// ----------------------------------------------------
$title = '管理者ログイン';
require_once 'login.html.php';
?>