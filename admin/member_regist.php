<?php
// member_regist.php
// PHPセッションを開始
session_start();



// データベース設定と接続関数を読み込み
require_once '../db_config.php'; 

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php'); 
    exit;
}

// フォームのリロード時や初めてのアクセス時の初期データ設定を行う前に、
// GETアクセス時（POSTではない初回アクセス時）に、編集や他のフォームのセッションデータをクリアする。
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // ステージとエラー情報をクリア
    unset($_SESSION['stage']);
    unset($_SESSION['regist_errors']);
    
    // 💡 編集画面などから残っているフォームデータをクリアする
    unset($_SESSION['regist_data']); 
}

// ----------------------------------------------------
// 0. 定数と初期設定
// ----------------------------------------------------

// 47都道府県のリスト
const PREFECTURES = [
    '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
    '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
    '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
    '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
    '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
    '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
    '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
];

// 性別の選択肢 (テーブルのgender: INTに合わせて数値を定義)
const GENDERS = [
    1 => '男性',
    2 => '女性',
];

// ステージ管理
// 1: フォーム入力 (form), 2: 確認画面 (confirm), 3: 完了画面 (complete)
$stage = $_SESSION['stage'] ?? 1;
$errors = $_SESSION['regist_errors'] ?? [];

// エラー情報をクリア（フォーム画面表示前にクリアしておく）
unset($_SESSION['regist_errors']);

// --- 二重送信防止トークンの管理 ---
if (!isset($_SESSION['token'])) {
    // トークンがなければ新規生成
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
    ];
    
    // フォームから確認画面へ (stage 1 -> 2)
    if ($action === 'confirm') {

        // 1. バリデーションの実行
        $pdo = getPdoConnection();
        $errors = validateForm($current_post_data, $pdo);

        // 2. 画面遷移の決定
        if (empty($errors)) {
            // エラーがなければステージを更新し、確認画面へ
            $stage = 2;
            // 確認画面へ遷移する前に新しいトークンを生成
            $_SESSION['token'] = bin2hex(random_bytes(32));
        } else {
            // エラーがあればフォームに留まる
            $stage = 1;
            $_SESSION['regist_errors'] = $errors;
        }

        // フォームデータは、エラーがあってもセッションに保存する
        $_SESSION['regist_data'] = array_diff_key(
            $current_post_data, 
            array_flip(['password', 'password_confirm'])
        );
        
        // エラーがない場合のみ、パスワードハッシュをセッションに保存
        if ($stage === 2) {
             // パスワードハッシュをセッションに保存
             $_SESSION['regist_data']['password_hash'] = password_hash($current_post_data['password'], PASSWORD_DEFAULT);
        }


    // 確認画面から登録完了へ (stage 2 -> 3)
    } elseif ($action === 'register') {
        
        // トークンチェック (セキュリティ強化のため追加)
        if (!isset($_SESSION['token']) || $post_token !== $_SESSION['token']) {
            $_SESSION['regist_errors']['global'] = '不正な操作または二重送信の可能性があります。最初からやり直してください。';
            $_SESSION['stage'] = 1; // フォーム入力画面に戻す
            header('Location: member_regist.php');
            exit;
        }

        // 2. データベース登録処理
        try {
            $pdo = getPdoConnection();
            
            // エラー時に例外をスローするように設定 (堅牢性のため維持)
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
            
            // トランザクション開始
            $pdo->beginTransaction();

            $formData = $_SESSION['regist_data']; // セッションのデータを使用
            $password_hash = $formData['password_hash']; // 確認画面へ遷移時に保存したハッシュ

            // SQL文のパスワードカラム名は 'password'、プレースホルダーは ':password' で確定
            $sql = "INSERT INTO members (name_sei, name_mei, gender, pref_name, address, password, email, created_at, updated_at) 
                    VALUES (:name_sei, :name_mei, :gender, :pref_name, :address, :password, :email, NOW(), NOW())";
            
            $stmt = $pdo->prepare($sql);
            
            $stmt->bindValue(':name_sei', $formData['name_sei']);
            $stmt->bindValue(':name_mei', $formData['name_mei']);
            $stmt->bindValue(':gender', (int)$formData['gender'], PDO::PARAM_INT);
            $stmt->bindValue(':pref_name', $formData['pref_name']);
            $stmt->bindValue(':address', $formData['address']);
            
            // 値はハッシュ化された $password_hash を使用
            $stmt->bindValue(':password', $password_hash);
            
            $stmt->bindValue(':email', $formData['email']);

            // 実行
            $stmt->execute(); 

            // トランザクションコミット
            $pdo->commit();

            // 💡 修正: 完了画面(stage 3)への遷移を削除
            
            // 3. 登録完了後、セッションデータをクリア（トークンもクリア）
            unset($_SESSION['regist_data']);
            unset($_SESSION['regist_errors']);
            unset($_SESSION['token']);
            unset($_SESSION['stage']); // stageもクリア

            // 💡 修正: member.phpへリダイレクト
            header('Location: member.php');
            exit;

        } catch (PDOException $e) {
            // エラーが発生した場合、ロールバック
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // ログ出力
            error_log("DB Registration failed: " . $e->getMessage()); 
            
            // ユーザーフレンドリーなエラー処理
            $stage = 2; 
            $_SESSION['regist_errors']['global'] = '登録処理中にデータベースエラーが発生しました。時間を置いて再度お試しください。';
            $_SESSION['stage'] = $stage;
            header('Location: member_regist.php');
            exit;
        }

    // 確認画面からフォームへ戻る (stage 2 -> 1)
    } elseif ($action === 'back') {
        $stage = 1;
        // 戻る際も新しいトークンを生成
        $_SESSION['token'] = bin2hex(random_bytes(32));
    }
}

// フォームのリロード時や初めてのアクセス時の初期データ設定
if (!isset($_SESSION['regist_data'])) {
    $_SESSION['regist_data'] = [
        'name_sei' => '', 'name_mei' => '', 'gender' => '', 
        'pref_name' => '', 'address' => '', 'password_hash' => '', 
        'email' => ''
    ];
}

$formData = $_SESSION['regist_data'];
$token = $_SESSION['token'];

// 現在のステージをセッションに保存
$_SESSION['stage'] = $stage;

// ----------------------------------------------------
// 2. バリデーション関数
// ----------------------------------------------------

/**
 * フォームデータのバリデーションを実行する
 * @param array $data フォームから受け取ったデータ
 * @param \PDO $pdo データベース接続
 * @return array エラーメッセージの配列 (キーはフォーム項目名)
 */
function validateForm(array $data, \PDO $pdo): array {
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

    // --- パスワード: 必須, 半角英数字8～20文字以内 ---
    $password = $data['password'];
    if (empty($password)) {
        $errors['password'] = 'パスワードは必須入力です。';
    } elseif (mb_strlen($password) < 8 || mb_strlen($password) > 20) {
        $errors['password'] = 'パスワードは8文字以上20文字以内で入力してください。';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
        $errors['password'] = 'パスワードは半角英数字で入力してください。';
    }

    // --- パスワード確認: 必須, 一致チェック, 半角英数字8～20文字以内 ---
    $password_confirm = $data['password_confirm'];
    if (empty($password_confirm)) {
        $errors['password_confirm'] = '確認用パスワードは必須入力です。';
    } elseif ($password !== $password_confirm) {
        $errors['password_confirm'] = 'パスワードと確認用パスワードが一致しません。';
    } 
    // パスワード確認も文字数・形式をチェック（本体のチェックと合わせる）
    elseif (mb_strlen($password_confirm) < 8 || mb_strlen($password_confirm) > 20) {
        $errors['password_confirm'] = '確認用パスワードは8文字以上20文字以内で入力してください。';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $password_confirm)) {
        $errors['password_confirm'] = '確認用パスワードは半角英数字で入力してください。';
    }

    // --- メールアドレス: 必須, 200文字以内, メール形式, DB重複チェック ---
    $email = $data['email'];
    if (empty($email)) {
        $errors['email'] = 'メールアドレスは必須入力です。';
    } elseif (mb_strlen($email) > 200) {
        $errors['email'] = 'メールアドレスは200文字以内で入力してください。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'メールアドレスの形式が正しくありません。';
    } else {
        // DB重複チェック
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = :email");
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $errors['email'] = 'このメールアドレスは既に登録されています。';
            }
        } catch (PDOException $e) {
            error_log("Email check failed: " . $e->getMessage());
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
    $title = '会員登録';
} elseif ($stage === 2) {
    $title = '会員登録';
} else {
    // 完了画面がないため、通常はここには来ない
    $title = 'エラー'; 
}

// テンプレートファイルを読み込み、HTMLを出力
require_once 'member_regist.html.php';

// stage 3 に来ることはないが、念のため残す（ただし上記でリダイレクトされるため実行されない）
// if ($stage === 3) {
//     unset($_SESSION['stage']);
// }