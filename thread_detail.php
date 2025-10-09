<?php
// thread_detail.php (スレッド詳細機能のコントローラー)

// ----------------------------------------------------
// 1. 初期設定とPDO接続ファイルの読み込み
// ----------------------------------------------------

session_start();
require_once 'db_config.php'; 

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

// ページネーションの初期化（DB接続の前に実行しても問題なし）
$current_page = (int)($_GET['page'] ?? 1);
if ($current_page < 1) {
    $current_page = 1;
}

$comments = [];
$total_comments = 0;
$total_pages = 0;

// 💡 定数をグローバルスコープに移動済み (OK)
const COMMENTS_PER_PAGE = 5; 


// ----------------------------------------------------
// 3 & 4. データベース操作と投稿処理を一つのtry/catchで囲む
// ----------------------------------------------------

try {
    $pdo = getPdoConnection(); 

    // --- 3. スレッド詳細を取得 ---
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
    } else {
        // 日時のフォーマット
        if (isset($thread['created_at'])) {
            $date_obj = new DateTime($thread['created_at']);
            $thread['formatted_created_at'] = $date_obj->format('Y-m-d H:i');
        }

        // --- 4. コメント投稿処理 (POST) ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // ログイン状態の確認
            if (!$is_logged_in) {
                $error_message = 'コメントを投稿するにはログインが必要です。';
            } else {
                // バリデーション
                $comment = $_POST['comment'] ?? '';
                $comment_errors = [];

                if (trim($comment) === '') { $comment_errors[] = '※コメントを入力してください。'; }
                if (mb_strlen($comment) > 500) { $comment_errors[] = 'コメントは500文字以内で入力してください。'; }

                if (empty($comment_errors)) {
                    // DBに登録
                    $now = date('Y-m-d H:i:s');
                    $member_id = $_SESSION['user_id']; 

                    // 💡 修正: created_at用に :created_at, updated_at用に :updated_at を使用
                    $sql_insert = "INSERT INTO comments (thread_id, member_id, comment, created_at, updated_at) 
                                   VALUES (:thread_id, :member_id, :comment, :created_at, :updated_at)"; // <-- SQLを修正
    
                    $stmt_insert = $pdo->prepare($sql_insert);
    
                    // 💡 すべて bindValue で型を明記
                    $stmt_insert->bindValue(':thread_id', $thread_id, PDO::PARAM_INT);
                    $stmt_insert->bindValue(':member_id', $member_id, PDO::PARAM_INT);
                    $stmt_insert->bindValue(':comment', $comment, PDO::PARAM_STR);
    
                    // 💡 修正: 2つの異なるプレースホルダに、同じ変数 $now の値をバインド
                    $stmt_insert->bindValue(':created_at', $now, PDO::PARAM_STR);
                    $stmt_insert->bindValue(':updated_at', $now, PDO::PARAM_STR);
    
                    $stmt_insert->execute();

                    // 登録成功後、ページをリロード
                    header('Location: thread_detail.php?id=' . $thread_id);
                    exit;

                } else {
                    $error_message = implode('<br>', $comment_errors);
                }
            } // end else for login check
        } // end if POST request

        
        // --- 3.1 & 3.2. コメント一覧の取得 ---
        // $threadが存在する場合のみ実行される
        
        // 3.1. 総コメント数の取得
        $sql_count = "SELECT COUNT(*) AS total FROM comments WHERE thread_id = :thread_id AND deleted_at IS NULL";
        $stmt_count = $pdo->prepare($sql_count);
        $stmt_count->bindParam(':thread_id', $thread_id, PDO::PARAM_INT);
        $stmt_count->execute();
        $total_comments = (int)$stmt_count->fetchColumn();
        
        $total_pages = ceil($total_comments / COMMENTS_PER_PAGE);

        // 不正なページ番号チェック
        if ($current_page > $total_pages && $total_comments > 0) {
            header('Location: thread_detail.php?id=' . $thread_id . '&page=' . $total_pages);
            exit;
        }
        $offset = ($current_page - 1) * COMMENTS_PER_PAGE; // 💡 offsetはここで再計算
        
        // 3.2. コメント一覧の取得
        $sql_comments = "SELECT c.id, c.comment, c.created_at, m.name_sei, m.name_mei
                         FROM comments AS c
                         LEFT JOIN members AS m ON c.member_id = m.id
                         WHERE c.thread_id = :thread_id AND c.deleted_at IS NULL
                         ORDER BY c.created_at ASC
                         LIMIT :limit OFFSET :offset";
        
        $stmt_comments = $pdo->prepare($sql_comments);
        $stmt_comments->bindParam(':thread_id', $thread_id, PDO::PARAM_INT);
        // LIMIT/OFFSETに直接bindParamする場合、PDO::PARAM_INTを明示的に使用
        $stmt_comments->bindValue(':limit', COMMENTS_PER_PAGE, PDO::PARAM_INT);
        $stmt_comments->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt_comments->execute();
        $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

        // コメントのフォーマット処理
        foreach ($comments as $key => $comment) {
            $comments[$key]['member_name'] = trim($comment['name_sei'] . ' ' . $comment['name_mei']);
            $date_obj = new DateTime($comment['created_at']);
            $comments[$key]['formatted_created_at'] = $date_obj->format('Y-m-d H:i');
        }

    } // end else (if (!$thread))

} catch (\PDOException $e) {
    error_log("DB Error in thread_detail.php: " . $e->getMessage());
    $error_message = 'システムエラーが発生しました。データベースの接続設定を確認してください。';
    echo "<h1>DB Error!</h1>";
    echo "<pre>";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString();
    echo "</pre>";
    exit;
}


// ----------------------------------------------------
// 5. ビューファイルの読み込み
// ----------------------------------------------------

if ($thread) {
    require_once 'thread_detail.html.php';
} else {
    // スレッドが見つからない場合もエラーメッセージを表示するためにビューを読み込む
    $title = 'エラー';
    require_once 'thread_detail.html.php';
}

?>