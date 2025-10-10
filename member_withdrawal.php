<?php
// member_withdrawal.php (会員退会機能のコントローラー)

session_start();
require_once 'db_config.php'; 

$error_message = null;
$pdo = null;

// ----------------------------------------------------
// 1. ログイン状態の確認（仕様: 退会ボタンはログイン時のみ表示/アクセス）
// ----------------------------------------------------
if (!isset($_SESSION['user_id']) || !($_SESSION['is_logged_in'] ?? false)) {
    // ログインしていない場合はトップ画面へリダイレクト
    header('Location: logout.php'); 
    exit;
}

$member_id = $_SESSION['user_id'];

// ----------------------------------------------------
// 2. データベース操作と退会処理
// ----------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 退会確認ボタンが押された場合の処理
    if (isset($_POST['withdraw_confirm'])) {
        
        try {
            $pdo = getPdoConnection(); 
            
            // トランザクション開始
            $pdo->beginTransaction();

            // 💡 データベースから削除する場合はソフトデリート
            $sql = "UPDATE members 
                    SET deleted_at = :deleted_at, updated_at = :updated_at 
                    WHERE id = :member_id 
                    AND deleted_at IS NULL"; // 既に退会済みでないか確認

            $stmt = $pdo->prepare($sql);
            $now = date('Y-m-d H:i:s');
            
            $stmt->bindValue(':deleted_at', $now);
            $stmt->bindValue(':updated_at', $now);
            $stmt->bindValue(':member_id', $member_id, PDO::PARAM_INT);
            
            $stmt->execute();

            $pdo->commit();

            // 退会成功後、ログアウト処理を実行
            $_SESSION = array(); 
            session_destroy();

            // ログアウト状態でトップ画面へリダイレクト
            header('Location: logout.php');
            exit;

        } catch (\PDOException $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB Error in member_withdrawal.php: " . $e->getMessage());
            $error_message = 'システムエラーが発生しました。時間をおいて再度お試しください。';
        }
    }
}

// ----------------------------------------------------
// 3. ビューファイルの読み込み
// ----------------------------------------------------
$title = '会員退会';
require_once 'member_withdrawal.html.php';
?>