<?php
// member_delete.php - 会員削除処理（ソフトデリート）

// PHPセッションを開始
session_start();

// ----------------------------------------------------
// 認証チェック
// ----------------------------------------------------
// セッションに管理者ID（admin_id）が存在するかを確認します。
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php'); 
    exit;
}

// データベース設定と接続関数を読み込み
require_once '../db_config.php';

// ----------------------------------------------------
// 1. データ取得とチェック
// ----------------------------------------------------

// URLパラメータから会員IDを取得
$member_id = $_GET['id'] ?? null;

if (!is_numeric($member_id) || $member_id <= 0) {
    // IDが不正な場合は一覧へリダイレクト
    header('Location: member.php');
    exit;
}

// ----------------------------------------------------
// 2. 削除処理（ソフトデリート）
// ----------------------------------------------------

$pdo = getPdoConnection();

try {
    // deleted_at カラムに現在日時をセットする（ソフトデリート）
    $sql = "UPDATE members SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', (int)$member_id, PDO::PARAM_INT);
    
    $stmt->execute();
    
    // 処理成功後のメッセージ（必要であればセッションに設定）
    // $_SESSION['success_message'] = '会員ID: ' . $member_id . ' を削除しました。';

} catch (PDOException $e) {
    error_log("DB Delete (Soft) failed in member_delete: " . $e->getMessage());
    // エラー時のメッセージ（必要であればセッションに設定）
    // $_SESSION['error_message'] = '削除処理中にエラーが発生しました。';
}

// 処理完了後、会員一覧画面へリダイレクト
header('Location: member.php');
exit;
