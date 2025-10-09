<?php
// thread_detail.php (スレッド詳細機能のコントローラー)

// ----------------------------------------------------
// 1. 初期設定とPDO接続ファイルの読み込み
// ----------------------------------------------------

session_start();
require_once 'db_config.php'; // データベース接続関数 getPdoConnection() を使用

$thread = null;
$error_message = null;

// ログイン状態を確認（コメントフォーム表示に使用）
$is_logged_in = isset($_SESSION['user_id']) || ($_SESSION['is_logged_in'] ?? false);


// ----------------------------------------------------
// 2. スレッドIDの検証
// ----------------------------------------------------

// URLからスレッドIDを取得
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    // IDがない、または不正な場合はスレッド一覧にリダイレクト
    header('Location: thread.php');
    exit;
}

$thread_id = (int)$_GET['id'];


// ----------------------------------------------------
// 3. データベース操作: スレッド詳細の取得
// ----------------------------------------------------

try {
    $pdo = getPdoConnection(); 

    // スレッド詳細を取得 (論理削除されていないもののみ)
    $sql = "SELECT t.id, t.title, t.content, t.member_id, t.created_at, CONCAT(m.name_sei, ' ', m.name_mei) AS member_name
            FROM threads AS t
            LEFT JOIN members AS m
            ON t.member_id = m.id
            WHERE t.id = :id
            AND t.deleted_at IS NULL;";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $thread_id, PDO::PARAM_INT);
    $stmt->execute();
    $thread = $stmt->fetch(PDO::FETCH_ASSOC);

    // スレッドが存在しない場合の処理
    if (!$thread) {
        $error_message = '指定されたスレッドは見つかりませんでした。';
        // 開発フェーズではリダイレクトせず、エラーメッセージを表示
    } else {
        if (isset($thread['created_at'])) {
            $date_obj = new DateTime($thread['created_at']);
            $thread['formatted_created_at'] = $date_obj->format('Y-m-d H:i');
        }
    }


    // ----------------------------------------------------
    // 4. コメント投稿処理 (このステップでは未実装、次のステップで追加)
    // ----------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // コメント投稿ロジックは次のステップで実装します
    }


} catch (\PDOException $e) {
    error_log("DB Error in thread_detail.php: " . $e->getMessage());
    $error_message = 'システムエラーが発生しました。データベースの接続設定を確認してください。';
}


// ----------------------------------------------------
// 5. ビューファイルの読み込み
// ----------------------------------------------------

// スレッドが見つかった場合のみビューを読み込む
if ($thread) {
    require_once 'thread_detail.html.php';
} else {
    // スレッドが見つからない場合もエラーメッセージを表示するためにビューを読み込む
    $title = 'エラー'; // 画面表示用のタイトルを仮設定
    require_once 'thread_detail.html.php';
}

?>