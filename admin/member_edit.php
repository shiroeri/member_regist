<?php
// member_edit.php
// PHPセッションを開始
session_start();

// データベース設定と接続関数を読み込み
require_once '../db_config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php'); 
    exit;
}

// ----------------------------------------------------
// 0. 定数と初期設定
// ----------------------------------------------------

const PREFECTURES = [
    '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
    '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
    '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
    '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
    '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
    '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
    '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
];

const GENDERS = [
    1 => '男性',
    2 => '女性',
];

// ステージ管理
$stage = $_SESSION['stage'] ?? 1;
$errors = $_SESSION['edit_errors'] ?? [];

// エラー情報をクリア（フォーム画面表示前にクリアしておく）
unset($_SESSION['edit_errors']);

// --- IDと既存データの取得 ---

// 編集対象のIDをURLパラメータまたはセッションから取得
$member_id = $_GET['id'] ?? $_SESSION['edit_member_id'] ?? null;

// 💡 編集IDがURLで渡され、セッションに保持されていたIDと異なる場合、セッションのフォームデータをクリアする
if (isset($_GET['id']) && (string)$_GET['id'] !== (string)($_SESSION['edit_member_id'] ?? '')) {
    unset($_SESSION['edit_data']); 
    unset($_SESSION['edit_errors']);
    unset($_SESSION['stage']); // ステージもリセット
}

// 💡 【ステージリセットの追加】GETリクエストの場合、ステージを1に強制リセット
// これにより、一覧へ戻った後やURL直打ちでアクセスした場合に必ずフォーム画面から開始される
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stage = 1;
    unset($_SESSION['stage']); 
}
// ----------------------------------

if (!is_numeric($member_id) || $member_id <= 0) {
    // IDが不正または未指定の場合は一覧画面へリダイレクト
    header('Location: member.php');
    exit;
}

// 💡 IDチェック後にセッションに新しいIDを保持
$_SESSION['edit_member_id'] = $member_id;

// データベースから既存データを取得
$pdo = getPdoConnection();
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = :id");
$stmt->bindValue(':id', (int)$member_id, PDO::PARAM_INT);
$stmt->execute();
$existing_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$existing_data) {
    // 該当IDの会員が存在しない場合も一覧画面へリダイレクト
    unset($_SESSION['edit_member_id']);
    header('Location: member.php');
    exit;
}

// --- 二重送信防止トークンの管理 ---
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['token'];


// ----------------------------------------------------
// 1. データ処理（POSTリクエストの処理）
// ----------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $post_token = $_POST['token'] ?? ''; // 送信されたトークンを取得

    $current_post_data = [
        'name_sei'          => trim($_POST['name_sei'] ?? ''),
        'name_mei'          => trim($_POST['name_mei'] ?? ''),
        'gender'            => $_POST['gender'] ?? '',
        'pref_name'         => $_POST['pref_name'] ?? '',
        'address'           => trim($_POST['address'] ?? ''),
        'password'          => $_POST['password'] ?? '',
        'password_confirm'  => $_POST['password_confirm'] ?? '',
        'email'             => trim($_POST['email'] ?? ''),
        // 既存のハッシュ値も処理のために保持
        'existing_password_hash' => $existing_data['password'],
    ];
    
    // フォームから確認画面へ (stage 1 -> 2)
    if ($action === 'confirm') {

        // 1. バリデーションの実行
        $errors = validateEditForm($current_post_data, $pdo, $member_id);

        // 2. 画面遷移の決定
        if (empty($errors)) {
            $stage = 2;
            $_SESSION['token'] = bin2hex(random_bytes(32));
        } else {
            $stage = 1;
            // 💡 修正後のセッションキーを使用
            $_SESSION['edit_errors'] = $errors;
        }

        // フォームデータは、エラーがあってもセッションに保存する
        // 💡 修正後のセッションキーを使用
        $_SESSION['edit_data'] = array_diff_key(
            $current_post_data, 
            array_flip(['password', 'password_confirm'])
        );
        
        // エラーがない場合のみ、パスワードハッシュをセッションに保存
        if ($stage === 2) {
             // パスワードが入力されているかチェック
             if (!empty($current_post_data['password'])) {
                 // 新しいパスワードが入力された場合、ハッシュ化して保存
                 $_SESSION['edit_data']['password_hash'] = password_hash($current_post_data['password'], PASSWORD_DEFAULT);
             } else {
                 // パスワードが空の場合、既存のハッシュをそのまま使用
                 $_SESSION['edit_data']['password_hash'] = $existing_data['password'];
             }
        }


    // 確認画面から更新完了へ (stage 2 -> member.phpへリダイレクト)
    } elseif ($action === 'update') {
        
        // トークンチェック (セキュリティ強化のため追加)
        if (!isset($_SESSION['token']) || $post_token !== $_SESSION['token']) {
            $_SESSION['edit_errors']['global'] = '不正な操作または二重送信の可能性があります。最初からやり直してください。';
            $_SESSION['stage'] = 1; // フォーム入力画面に戻す
            header('Location: member_edit.php?id=' . $member_id);
            exit;
        }

        // 2. データベース更新処理
        try {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
            $pdo->beginTransaction();

            $formData = $_SESSION['edit_data']; 
            $password_hash = $formData['password_hash']; 

            // SQL文をUPDATEに変更
            $sql = "UPDATE members SET 
                        name_sei = :name_sei, 
                        name_mei = :name_mei, 
                        gender = :gender, 
                        pref_name = :pref_name, 
                        address = :address, 
                        password = :password, 
                        email = :email, 
                        updated_at = NOW() 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            
            $stmt->bindValue(':name_sei', $formData['name_sei']);
            $stmt->bindValue(':name_mei', $formData['name_mei']);
            $stmt->bindValue(':gender', (int)$formData['gender'], PDO::PARAM_INT);
            $stmt->bindValue(':pref_name', $formData['pref_name']);
            $stmt->bindValue(':address', $formData['address']);
            $stmt->bindValue(':password', $password_hash);
            $stmt->bindValue(':email', $formData['email']);
            // IDのバインドが必須
            $stmt->bindValue(':id', (int)$member_id, PDO::PARAM_INT);

            // 実行
            $stmt->execute(); 

            // トランザクションコミット
            $pdo->commit();
            
            // 3. 登録完了後、セッションデータをクリアし、一覧へリダイレクト
            unset($_SESSION['edit_data']);
            unset($_SESSION['edit_errors']);
            unset($_SESSION['token']);
            unset($_SESSION['stage']);
            unset($_SESSION['edit_member_id']); // 編集IDもクリア

            // 更新完了後はmember.phpへリダイレクト
            header('Location: member.php');
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB Update failed: " . $e->getMessage()); 
            
            $stage = 2; 
            $_SESSION['edit_errors']['global'] = '更新処理中にデータベースエラーが発生しました。時間を置いて再度お試しください。';
            $_SESSION['stage'] = $stage;
            header('Location: member_edit.php?id=' . $member_id);
            exit;
        }

    // 確認画面からフォームへ戻る (stage 2 -> 1)
    } elseif ($action === 'back') {
        $stage = 1;
        $_SESSION['token'] = bin2hex(random_bytes(32));
    }
}

// フォームのリロード時や初めてのアクセス時の初期データ設定
// 💡 修正後のセッションキーを使用
if (!isset($_SESSION['edit_data'])) {
    // 初めてアクセスされた時、DBの既存データをセッションにセット
    $_SESSION['edit_data'] = [
        'name_sei' => $existing_data['name_sei'], 
        'name_mei' => $existing_data['name_mei'], 
        'gender' => $existing_data['gender'], 
        'pref_name' => $existing_data['pref_name'], 
        'address' => $existing_data['address'], 
        'email' => $existing_data['email'],
        
        // パスワードはフォームに表示しない（セキュリティ上の慣習）ため空文字で初期化
        'password' => '', 
        'password_confirm' => '', 
        
        // パスワードハッシュはDBから取得したものをそのまま利用
        'password_hash' => $existing_data['password']
    ];
}

// 💡 修正後のセッションキーを使用
$formData = $_SESSION['edit_data'];
$token = $_SESSION['token'];

// 現在のステージをセッションに保存 (GETリクエストの場合はunsetされているため保存されない)
$_SESSION['stage'] = $stage;


// ----------------------------------------------------
// 2. バリデーション関数 (編集用)
// ----------------------------------------------------

/**
 * 編集フォームデータのバリデーションを実行する (パスワード任意、メール重複チェック除外あり)
 * @param array $data フォームから受け取ったデータ
 * @param \PDO $pdo データベース接続
 * @param int $member_id 編集対象の会員ID
 * @return array エラーメッセージの配列 (キーはフォーム項目名)
 */
function validateEditForm(array $data, \PDO $pdo, int $member_id): array {
    $errors = [];

    // --- 氏名（姓）: 必須, 20文字以内 ---
    if (empty($data['name_sei'])) {
        $errors['name_sei'] = '氏名（姓）は必須入力です。';
    } elseif (mb_strlen($data['name_sei']) > 20) {
        $errors['name_sei'] = '氏名（姓）は20文字以内で入力してください。';
    }

    // --- 氏名（名）: 必須, 20文字以内 ---
    if (empty($data['name_mei'])) {
        $errors['name_mei'] = '氏名（名）は必須入力です。';
    } elseif (mb_strlen($data['name_mei']) > 20) {
        $errors['name_mei'] = '氏名（名）は20文字以内で入力してください。';
    }

    // --- 性別: 必須, テーブルのINT型に合う値かチェック ---
    $valid_gender_keys = array_keys(GENDERS);
    $gender = $data['gender'];
    if (!is_numeric($gender) || !in_array((int)$gender, $valid_gender_keys, true)) {
        $errors['gender'] = '性別は必須選択です。';
    }

    // --- 住所（都道府県）: 必須, 47都道府県以外の値は不正 ---
    if (empty($data['pref_name'])) {
        $errors['pref_name'] = '都道府県は必須選択です。';
    } elseif (!in_array($data['pref_name'], PREFECTURES, true)) {
        $errors['pref_name'] = '不正な都道府県が選択されました。';
    }

    // --- 住所（それ以降の住所）: 任意, 100文字以内 ---
    if (mb_strlen($data['address']) > 100) {
        $errors['address'] = '住所（それ以降）は100文字以内で入力してください。';
    }

    // --- パスワード: 任意（入力された場合のみチェック） ---
    $password = $data['password'];
    $password_confirm = $data['password_confirm'];
    
    // パスワード/確認用パスワードのどちらか、または両方が入力された場合
    if (!empty($password) || !empty($password_confirm)) {
        
        if (empty($password)) {
            $errors['password'] = 'パスワードを更新する場合は、パスワードを入力してください。';
        } elseif (strlen($password) < 8 || strlen($password) > 20) {
            $errors['password'] = 'パスワードは8文字以上20文字以内で入力してください。';
        } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
            $errors['password'] = 'パスワードは半角英数字で入力してください。';
        }

        if (empty($password_confirm)) {
            $errors['password_confirm'] = '確認用パスワードは必須入力です。';
        } elseif ($password !== $password_confirm) {
            $errors['password_confirm'] = 'パスワードと確認用パスワードが一致しません。';
        }
    }


    // --- メールアドレス: 必須, 200文字以内, メール形式, DB重複チェック（自身を除く） ---
    $email = $data['email'];
    if (empty($email)) {
        $errors['email'] = 'メールアドレスは必須入力です。';
    } elseif (mb_strlen($email) > 200) {
        $errors['email'] = 'メールアドレスは200文字以内で入力してください。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'メールアドレスの形式が正しくありません。';
    } else {
        // DB重複チェック: 自身のIDを除外してチェック
        try {
            // WHERE email = :email AND id != :id で自分以外のレコードを検索
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = :email AND id != :id");
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':id', $member_id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $errors['email'] = 'このメールアドレスは既に他の会員に登録されています。';
            }
        } catch (PDOException $e) {
            error_log("Email check failed in edit: " . $e->getMessage());
            $errors['email'] = 'メールアドレスの重複チェック中にエラーが発生しました。';
        }
    }
    
    return $errors;
}

// ----------------------------------------------------
// 3. HTMLの出力（テンプレートの読み込み）
// ----------------------------------------------------

// 現在のステージに応じてタイトルを設定
if ($stage === 1) {
    $title = '会員編集';
} elseif ($stage === 2) {
    $title = '会員編集';
} else {
    $title = 'エラー'; 
}

// テンプレートファイルを読み込み、HTMLを出力
require_once 'member_edit.html.php';
