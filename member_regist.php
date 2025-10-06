<?php
// PHPセッションを開始
session_start();

// ----------------------------------------------------
// 0. 定数と初期設定
// ----------------------------------------------------

// 47都道府県のリスト
$prefectures = [
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
$stage = isset($_SESSION['stage']) ? $_SESSION['stage'] : 1;
$errors = [];

// ----------------------------------------------------
// 1. データ処理（POSTリクエストの処理）
// ----------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // フォームから確認画面へ (stage 1 -> 2)
    if ($action === 'confirm') {
        // 入力値をサニタイズしてセッションに保存
        $_SESSION['form_data'] = [
            'last_name' => trim($_POST['last_name'] ?? ''),
            'first_name' => trim($_POST['first_name'] ?? ''),
            'gender' => $_POST['gender'] ?? '',
            'prefecture' => $_POST['prefecture'] ?? '',
            'address' => trim($_POST['address'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
            'email' => trim($_POST['email'] ?? ''),
        ];

        // 【バリデーション（入力チェック）】
        $data = $_SESSION['form_data'];
        
        if (empty($data['last_name']) || empty($data['first_name'])) {
            $errors[] = '氏名を入力してください。';
        }
        if (empty($data['gender'])) {
            $errors[] = '性別を選択してください。';
        }
        if (empty($data['prefecture'])) {
            $errors[] = '都道府県を選択してください。';
        }
        if (empty($data['address'])) {
            $errors[] = '住所を入力してください。';
        }
        if (empty($data['password']) || empty($data['password_confirm'])) {
            $errors[] = 'パスワードとパスワード確認を入力してください。';
        }
        if (strlen($data['password']) < 8 || strlen($data['password']) > 20) {
             $errors[] = 'パスワードは8〜20文字以内で入力してください。';
        }
        if ($data['password'] !== $data['password_confirm']) {
            $errors[] = 'パスワードが一致しません。';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = '有効なメールアドレスを入力してください。';
        }

        // エラーがなければステージを更新
        if (empty($errors)) {
            $stage = 2;
            $_SESSION['stage'] = 2;
        } else {
            // エラーがあればフォームに留まる
            $stage = 1;
            $_SESSION['stage'] = 1;
        }

    // 確認画面から登録完了へ (stage 2 -> 3)
    } elseif ($action === 'register') {
        // 🚨 データベース登録などの処理をここで行います 🚨

        $stage = 3;
        $_SESSION['stage'] = 3;
        
        // 登録完了後、セッションデータをクリア
        unset($_SESSION['form_data']);

    // 確認画面からフォームへ戻る (stage 2 -> 1)
    } elseif ($action === 'back') {
        $stage = 1;
        $_SESSION['stage'] = 1;
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

// ----------------------------------------------------
// 2. HTMLの出力（テンプレートの読み込み）
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
