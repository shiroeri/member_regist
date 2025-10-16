<?php
// member_detail.php - 会員詳細情報の表示

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

// ----------------------------------------------------
// 1. データ取得
// ----------------------------------------------------

// URLパラメータから会員IDを取得
$member_id = $_GET['id'] ?? null;

if (!is_numeric($member_id) || $member_id <= 0) {
    // IDが不正または未指定の場合は一覧画面へリダイレクト
    header('Location: member.php');
    exit;
}

$pdo = getPdoConnection();
try {
    // 削除されていない（deleted_at IS NULL）会員情報を取得
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = :id AND deleted_at IS NULL");
    $stmt->bindValue(':id', (int)$member_id, PDO::PARAM_INT);
    $stmt->execute();
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("DB Fetch failed in member_detail: " . $e->getMessage());
    // エラー時は一覧へリダイレクト
    header('Location: member.php');
    exit;
}

if (!$member) {
    // 該当IDの会員が存在しない（または既に削除されている）場合は一覧へリダイレクト
    header('Location: member.php');
    exit;
}

// 性別表示用の変換
$member['gender_label'] = GENDERS[$member['gender']] ?? '不明';

// ----------------------------------------------------
// 2. HTMLの出力
// ----------------------------------------------------
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会員詳細 - <?= htmlspecialchars($member['name_sei'] . $member['name_mei']) ?></title>
    <!-- Tailwind CSS (CDN) -->
    <link rel="stylesheet" href="member_regist.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .detail-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-label {
            font-weight: 600;
            color: #4b5563;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-lg">
        <div class="flex justify-between items-center mb-6 border-b pb-2">
            <h1 class="text-3xl font-bold text-gray-800">会員詳細</h1>
            <!-- 一覧へ戻るボタン -->
            <a href="member.php" 
               class="bg-gray-500 hover:bg-gray-600 text-white text-sm font-bold py-2 px-4 rounded-lg transition duration-150 shadow-md">
                一覧へ戻る
            </a>
        </div>
        
        <div class="space-y-4 mb-8">
            <!-- ID (管理用) -->
            <div class="detail-row">
                <span class="detail-label">ID</span>
                <span><?= htmlspecialchars($member['id']) ?></span>
            </div>

            <!-- 氏名 -->
            <div class="detail-row">
                <span class="detail-label">氏名</span>
                <span><?= htmlspecialchars($member['name_sei'] . ' ' . $member['name_mei']) ?></span>
            </div>

            <!-- 性別 -->
            <div class="detail-row">
                <span class="detail-label">性別</span>
                <span><?= htmlspecialchars($member['gender_label']) ?></span>
            </div>

            <!-- 住所 -->
            <div class="detail-row">
                <span class="detail-label">住所</span>
                <span>
                    <?= htmlspecialchars($member['pref_name']) ?>
                    <?= htmlspecialchars($member['address']) ?>
                </span>
            </div>

            <!-- パスワード -->
            <div class="detail-row">
                <span class="detail-label">パスワード</span>
                <span>セキュリティのため非表示</span>
            </div>

            <!-- メールアドレス -->
            <div class="detail-row">
                <span class="detail-label">メールアドレス</span>
                <span><?= htmlspecialchars($member['email']) ?></span>
            </div>
        </div>

        <div class="flex flex-wrap gap-4 mt-8 pt-6">
            <!-- 編集ボタン -->
            <a href="member_edit.php?id=<?= htmlspecialchars($member['id']) ?>" 
               class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-150 shadow-md">
                編集
            </a>

            <!-- 削除ボタン (確認なしでmember_delete.phpへ直接遷移) -->
            <a href="member_delete.php?id=<?= htmlspecialchars($member['id']) ?>"
               class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-150 shadow-md">
                削除
            </a>
        </div>
    </div>
</body>
</html>
