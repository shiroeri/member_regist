<?php
// PHPセッションを開始
session_start();

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

// ステージ管理
// 1: フォーム入力 (form), 2: 確認画面 (confirm), 3: 完了画面 (complete)
$stage = $_SESSION['stage'] ?? 1;
$errors = $_SESSION['errors'] ?? [];

// エラー情報をクリア（フォーム画面表示前にクリアしておく）
unset($_SESSION['errors']);

// ----------------------------------------------------
// 1. データ処理（POSTリクエストの処理）
// ----------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    $current_post_data = [
        'last_name'         => trim($_POST['last_name'] ?? ''),
        'first_name'        => trim($_POST['first_name'] ?? ''),
        'gender'            => $_POST['gender'] ?? '',
        'prefecture'        => $_POST['prefecture'] ?? '',
        'address'           => trim($_POST['address'] ?? ''),
        'password'          => $_POST['password'] ?? '',
        'password_confirm'  => $_POST['password_confirm'] ?? '',
        'email'             => trim($_POST['email'] ?? ''),
    ];

    // フォームから確認画面へ (stage 1 -> 2)
    if ($action === 'confirm') {

        // 1. バリデーションの実行
        $errors = validateForm($current_post_data);

        // 2. 画面遷移の決定
        if (empty($errors)) {
            // エラーがなければステージを更新し、確認画面へ
            $stage = 2;
        } else {
            // エラーがあればフォームに留まる
            $stage = 1;
            // エラーメッセージをセッションに保存（フォーム画面での表示用）
            $_SESSION['errors'] = $errors;
        }

        // フォームデータは、エラーがあってもセッションに保存する
        $_SESSION['form_data'] = array_diff_key(
            $current_post_data, 
            array_flip(['password', 'password_confirm'])
        );

    // 確認画面から登録完了へ (stage 2 -> 3)
    } elseif ($action === 'register') {
        //  データベース登録などの処理をここで行う 

        $stage = 3;
        $_SESSION['stage'] = 3;
        
        // 登録完了後、セッションデータをクリア
        unset($_SESSION['form_data']);
        unset($_SESSION['errors']);

    // 確認画面からフォームへ戻る (stage 2 -> 1)
    } elseif ($action === 'back') {
        $stage = 1;
    }
}

// フォームのリロード時や初めてのアクセス時の初期データ設定
if (!isset($_SESSION['form_data'])) {
    $_SESSION['form_data'] = [
        'last_name' => '', 'first_name' => '', 'gender' => '', 
        'prefecture' => '', 'address' => '', 'password' => '', 
        'password_confirm' => '', 'email' => ''
    ];
}

$formData = $_SESSION['form_data'];

// 現在のステージをセッションに保存
$_SESSION['stage'] = $stage;

// ----------------------------------------------------
// 2. バリデーション関数
// ----------------------------------------------------

/**
 * フォームデータのバリデーションを実行する
 * @param array $data フォームから受け取ったデータ
 * @return array エラーメッセージの配列 (キーはフォーム項目名)
 */
function validateForm(array $data): array {
    $errors = [];

    // --- 氏名（姓）: 必須, 20文字以内 ---
    if (empty($data['last_name'])) {
        $errors['last_name'] = '氏名（姓）は必須入力です。';
    } elseif (mb_strlen($data['last_name']) > 20) {
        $errors['last_name'] = '氏名（姓）は20文字以内で入力してください。';
    }

    // --- 氏名（名）: 必須, 20文字以内 ---
    if (empty($data['first_name'])) {
        $errors['first_name'] = '氏名（名）は必須入力です。';
    } elseif (mb_strlen($data['first_name']) > 20) {
        $errors['first_name'] = '氏名（名）は20文字以内で入力してください。';
    }

    // --- 性別: 必須, 「男性・女性」以外の値は不正 ---
    $valid_genders = ['男性', '女性'];
    if (empty($data['gender'])) {
        $errors['gender'] = '性別は必須選択です。';
    } elseif (!in_array($data['gender'], $valid_genders, true)) {
        $errors['gender'] = '不正な性別が選択されました。'; // チェック項目に対応
    }

    // --- 住所（都道府県）: 必須, 47都道府県以外の値は不正 ---
    if (empty($data['prefecture'])) {
        $errors['prefecture'] = '都道府県は必須選択です。';
    } elseif (!in_array($data['prefecture'], PREFECTURES, true)) {
        $errors['prefecture'] = '不正な都道府県が選択されました。'; // チェック項目に対応
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
    // パスワード確認も文字数・形式をチェック（パスワード本体のエラーに依存させないため）
    elseif (mb_strlen($password_confirm) < 8 || mb_strlen($password_confirm) > 20) {
        $errors['password_confirm'] = '確認用パスワードは8文字以上20文字以内で入力してください。';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $password_confirm)) {
        $errors['password_confirm'] = '確認用パスワードは半角英数字で入力してください。';
    }

    // --- メールアドレス: 必須, 200文字以内, メール形式 ---
    $email = $data['email'];
    if (empty($email)) {
        $errors['email'] = 'メールアドレスは必須入力です。';
    } elseif (mb_strlen($email) > 200) {
        $errors['email'] = 'メールアドレスは200文字以内で入力してください。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'メールアドレスの形式が正しくありません。';
    }
    // DB重複チェックは今回はDBがないためスキップ
    
    return $errors;
}

// ----------------------------------------------------
// 3. HTMLの出力（テンプレートの読み込み）
// ----------------------------------------------------

// 現在のステージに応じてタイトルを設定
if ($stage === 1) {
    $title = '会員情報登録フォーム';
} elseif ($stage === 2) {
    $title = '会員情報確認画面';
} else {
    $title = '会員登録完了';
}

// テンプレートファイルを読み込み、HTMLを出力
require_once 'member_regist.html.php';

// 完了画面から戻る際はセッションステージをリセット（フォームからやり直す）
if ($stage === 3) {
    unset($_SESSION['stage']);
}
?>
