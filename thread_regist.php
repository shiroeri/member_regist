<?php
// thread_regist.php

session_start();
// DB接続関数と設定の読み込み
require_once 'db_config.php';

// ----------------------------------------------------
// 0. 初期設定とモードの決定
// ----------------------------------------------------

$title = 'スレッド作成フォーム';
$errors = [];
$formData = [
    'title' => trim($_POST['title'] ?? ''),
    'content' => trim($_POST['content'] ?? ''),
];
// mode: input, confirm, complete のいずれか
$mode = $_POST['mode'] ?? 'input'; 

// ----------------------------------------------------
// 1. ログイン状態のチェック (仕様対応)
// ----------------------------------------------------
// スレッドを作成するボタンを押すとログイン時にエラーが表示されないようにする
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    // ログインしていない場合、ログイン認証ページへ強制遷移
    header('Location: login_auth.php'); 
    exit;
}

// ユーザーIDを取得 (DB挿入に必要)
$member_id = $_SESSION['user_id'];

// --- 二重送信防止トークンの管理 ---
if (!isset($_SESSION['thread_token'])) {
    $_SESSION['thread_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['thread_token'];


// ----------------------------------------------------
// 2. POSTデータ処理とバリデーション
// ----------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // POSTデータをformDataに格納 (再表示・確認画面用)
    $formData['title'] = trim($_POST['title'] ?? '');
    $formData['content'] = trim($_POST['content'] ?? '');
    $post_token = $_POST['token'] ?? ''; // 送信されたトークンを取得
    $action = $_POST['action'] ?? ''; // 登録完了時のアクションを想定

    // トークンチェック (セキュリティのため)
    if ($post_token !== $token) {
        // トークン不一致、または二重送信の場合は入力画面に戻す
        $errors['global'] = '不正な操作が行われました。最初からやり直してください。';
        $mode = 'input';
        // 新しいトークンを生成
        $_SESSION['thread_token'] = bin2hex(random_bytes(32)); 
    }
    
    // 戻るボタンが押された場合の処理
    elseif (isset($_POST['back_to_input'])) {
        // 確認画面から入力画面へ戻る
        $mode = 'input';
        // 戻る際も新しいトークンを生成
        $_SESSION['thread_token'] = bin2hex(random_bytes(32));
    } 
    
    // '確認画面へ' または 'スレッドを作成する' ボタン処理
    else {
        // バリデーションは 'input' ステップと 'confirm' ステップ両方で行う
        $errors = validateThreadForm($formData);

        if (empty($errors)) {
            if ($mode === 'confirm' && $action === 'register') {
                // 'スレッドを作成する' ボタン処理 (DB登録)
                try {
                    // トークンが一致し、バリデーションも問題なし
                    $pdo = getPdoConnection();
                    
                    // タイムスタンプはPHP側で設定 (DBのデフォルト値が '0000-00-00 00:00:00' のため)
                    $now = date('Y-m-d H:i:s'); 
                    
                    // DBへの操作は、PDOを利用
                    // created_at, updated_at に現在時刻を設定
                    $sql = "INSERT INTO threads (member_id, title, content, created_at, updated_at) 
                            VALUES (:member_id, :title, :content, :created_at, :updated_at)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':member_id', $member_id, PDO::PARAM_INT);
                    $stmt->bindValue(':title', $formData['title'], PDO::PARAM_STR);
                    $stmt->bindValue(':content', $formData['content'], PDO::PARAM_STR);
                    $stmt->bindValue(':created_at', $now, PDO::PARAM_STR);
                    $stmt->bindValue(':updated_at', $now, PDO::PARAM_STR);
                    
                    $stmt->execute();
                    
                    // 登録成功後、セッションのトークンをクリア
                    unset($_SESSION['thread_token']);

                    // ⭐ 登録完了後、トップ画面へリダイレクト
                    header('Location: logout.php');

                    // スレッド作成確認画面へ遷移
                    $mode = 'complete';

                    exit;
                    
                } catch (PDOException $e) {
                    error_log("Thread registration DB error: " . $e->getMessage());
                    $errors['global'] = 'データベース処理中にエラーが発生しました。スレッドは作成されませんでした。';
                    $mode = 'input'; // エラーで入力画面に戻す
                }

            } else {
                // '確認画面へ' ボタン処理
                $mode = 'confirm';
                // 確認画面へ遷移する際は新しいトークンを生成
                $_SESSION['thread_token'] = bin2hex(random_bytes(32));
            }
        } else {
            // バリデーションエラー時は入力画面に戻す
            $mode = 'input';
            // エラー時はフォームの再表示で同じトークンを使う
        }
    }
}

// 現在のトークンをHTMLに渡す
$token = $_SESSION['thread_token'];

// ----------------------------------------------------
// 3. バリデーション関数
// ----------------------------------------------------

/**
 * スレッド作成フォームのバリデーションを実行する
 * @param array $data フォームから受け取ったデータ
 * @return array エラーメッセージの配列
 */
function validateThreadForm(array $data): array {
    $errors = [];

    // スレッドタイトル: 必須、100文字以内
    if (empty($data['title'])) {
        $errors['title'] = 'タイトルは必須入力です。';
    } elseif (mb_strlen($data['title']) > 100) {
        // 101文字以上でフォームに戻りエラー表示
        $errors['title'] = 'スレッドタイトルは100文字以内で入力してください。';
    }
    
    // コメント: 必須、500文字以内
    if (empty($data['content'])) {
        $errors['content'] = 'コメントは必須入力です。';
    } elseif (mb_strlen($data['content']) > 500) {
        // 501文字以上でフォームに戻りエラー表示
        $errors['content'] = 'コメントは500文字以内で入力してください。';
    }

    return $errors;
}

// ----------------------------------------------------
// 4. 画面の表示
// ----------------------------------------------------

// 各モードに応じたHTMLテンプレートを読み込み
require_once 'thread_regist.html.php';