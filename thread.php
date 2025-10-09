<?php
// thread.php

// セッション開始
session_start();

// データベース接続設定ファイルを読み込む
require_once 'db_config.php';

// タイトル設定
$title = 'スレッド一覧';
$threads = [];
$search_query = '';

// ログイン状態を確認
$is_logged_in = isset($_SESSION['user_id']) || ($_SESSION['is_logged_in'] ?? false);

try {
        // データベース接続
        $pdo = getPdoConnection(); 

        // ----------------------------------------
        // 検索処理
        // ----------------------------------------
        $search_conditions = '';
        $params = [];
    
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
            $search_query = trim($_GET['search']);
        
            // スレッドのタイトル・コメントに一致するものを検索（title,content）		
            if ($search_query !== '') {
                $search_conditions = ' AND (title LIKE :search_title OR content LIKE :search_content)';
                
                $params[':search_title'] = '%' . $search_query . '%';
                $params[':search_content'] = '%' . $search_query . '%'; // 値は同じでOK
            }
        }
    
        // ----------------------------------------
        // スレッド一覧取得
        // ----------------------------------------
        
        // SQLの構造を再確認
        $sql = "SELECT id, title, member_id, created_at FROM threads" . 
               " WHERE deleted_at IS NULL" .  // (1) ベース条件
               $search_conditions .          // (2) 検索ワードがあれば ' AND (...)' が追加される
               " ORDER BY created_at DESC";  // スレッドID、スレッドタイトル、登録日時を登録日時の降順に一覧表示
    
        $stmt = $pdo->prepare($sql);

        $stmt->execute($params);
        $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    // DB接続またはクエリ実行エラー
    error_log("DB Error in thread.php: " . $e->getMessage());
    // ユーザーには一般的なエラーを表示
    $error_message = 'データベースエラーが発生しました。詳細: ' . $e->getMessage() . 
                     ' (Code: ' . $e->getCode() . ')';
}

// ビューファイルの読み込み
require_once 'thread.html.php';
?>