<?php
// admin/member.php (会員一覧・検索・並べ替え機能のコントローラー)

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../db_config.php'; 

// ----------------------------------------------------
// 1. ログイン状態の確認（管理者ログイン必須）
// ----------------------------------------------------
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php'); 
    exit;
}

$pdo = getPdoConnection();
$title = '会員一覧';

// ----------------------------------------------------
// 2. パラメータの取得とデフォルト値の設定
// ----------------------------------------------------
$page = (int)($_GET['page'] ?? 1);
$page = max(1, $page); // ページは1以上
define('MEMBERS_PER_PAGE', 10); 
$offset = ($page - 1) * MEMBERS_PER_PAGE;

// 検索条件 (クエリパラメータから取得)
$search_id = $_GET['id'] ?? '';
$error_message_id = ''; 
$search_id_for_query = ''; 

// IDのバリデーションロジック
if ($search_id !== '') {
    if (!ctype_digit($search_id)) {
        $error_message_id = 'IDを入力してください。'; 
    } else {
        $search_id_for_query = $search_id;
    }
}

// 性別は配列で渡される可能性がある
$search_gender = $_GET['gender'] ?? []; 
if (!is_array($search_gender)) {
    $search_gender = $search_gender !== '' ? [$search_gender] : [];
}
$search_pref = $_GET['pref'] ?? ''; 
$search_free = $_GET['free'] ?? ''; 

// 並べ替え (デフォルトはIDの降順)
$sort_column = $_GET['sort'] ?? 'id';
$sort_order = $_GET['order'] ?? 'DESC'; 

// 許可された並べ替えカラムと順序のホワイトリスト
$allowed_sorts = ['id', 'created_at']; 
$allowed_orders = ['ASC', 'DESC'];

if (!in_array($sort_column, $allowed_sorts)) {
    $sort_column = 'id';
}
if (!in_array(strtoupper($sort_order), $allowed_orders)) {
    $sort_order = 'DESC';
}
$sort_order = strtoupper($sort_order);

// ----------------------------------------------------
// 3. SQLクエリの構築 (検索条件 AND 結合)
// ----------------------------------------------------

$where_clauses = ["deleted_at IS NULL"]; 

// 性別の検索値（文字列の配列）をDBの値（数値の配列）に変換
$db_gender_values = [];
foreach ($search_gender as $gender_str) {
    if ($gender_str === '男性') {
        $db_gender_values[] = 1;
    } elseif ($gender_str === '女性') {
        $db_gender_values[] = 2;
    }
}

// 3.1. 検索条件の追加
if ($search_id_for_query !== '') { 
    $where_clauses[] = "id = :id";
} 

// 選択された性別がある場合、IN句を使用する
if (!empty($db_gender_values)) {
    $gender_placeholders = [];
    for ($i = 0; $i < count($db_gender_values); $i++) {
        $gender_placeholders[] = ":gender_{$i}";
    }
    $where_clauses[] = "gender IN (" . implode(', ', $gender_placeholders) . ")";
}

// 都道府県名(pref_name)で検索
if ($search_pref !== '') { 
    $where_clauses[] = "pref_name = :pref_name"; 
}

// フリーワード検索
if ($search_free !== '') {
    $where_clauses[] = "(
        name_sei LIKE :free_word_1 OR name_mei LIKE :free_word_2 OR
        email LIKE :free_word_3
    )";
}
$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// ----------------------------------------------------
// 4. 総レコード数の取得 (ページネーション用)
// ----------------------------------------------------
$sql_count = "SELECT COUNT(*) FROM members {$where_sql}";
$stmt_count = $pdo->prepare($sql_count); 

// パラメータバインド (カウントと一覧の両方で使用)
$params = [];
if ($search_id_for_query !== '') { $params[':id'] = (int)$search_id_for_query; }

// 複数の性別値をバインド
if (!empty($db_gender_values)) {
    for ($i = 0; $i < count($db_gender_values); $i++) {
        $params[":gender_{$i}"] = $db_gender_values[$i];
    }
}

if ($search_pref !== '') { $params[':pref_name'] = $search_pref; } 

if ($search_free !== '') { 
    $free_value = "%{$search_free}%";
    $params[':free_word_1'] = $free_value;
    $params[':free_word_2'] = $free_value;
    $params[':free_word_3'] = $free_value;
}


// 総レコード数取得クエリの実行
$stmt_count->execute($params); 

$total_members = $stmt_count->fetchColumn();
$total_pages = ceil($total_members / MEMBERS_PER_PAGE);
if ($page > $total_pages && $total_pages > 0) { $page = $total_pages; $offset = ($page - 1) * MEMBERS_PER_PAGE; } elseif ($total_pages === 0) { $page = 1; }

// ----------------------------------------------------
// 5. 会員一覧の取得
// ----------------------------------------------------
$sql_members = "SELECT 
                    id, name_sei, name_mei, gender, pref_name, address, created_at
                FROM members 
                {$where_sql}
                ORDER BY {$sort_column} {$sort_order}
                LIMIT :limit OFFSET :offset"; 

$stmt_members = $pdo->prepare($sql_members);

$all_params = $params;

// LIMITとOFFSETを追加
$all_params[':limit'] = MEMBERS_PER_PAGE;
$all_params[':offset'] = $offset;

$stmt_members->execute($all_params); 

$members = $stmt_members->fetchAll(PDO::FETCH_ASSOC);

// ビューのセレクトボックス生成用に、都道府県名リスト（全47件）を定義
$prefectures_list = [
    '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県', '栃木県', '群馬県', 
    '埼玉県', '千葉県', '東京都', '神奈川県', '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', 
    '岐阜県', '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県', 
    '鳥取県', '島根県', '岡山県', '広島県', '山口県', '徳島県', '香川県', '愛媛県', '高知県', '福岡県', 
    '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
];


// 💡 修正: ソートリンクURL生成関数 (異なるカラムに切り替えた際、現在のソート順を反転)
function getSortUrl($column, $current_sort, $current_order, $search_params, $current_page) {
    if ($column === $current_sort) {
        // 同じカラムを連続でクリックした場合: 順序を反転
        $order = ($current_order === 'DESC') ? 'ASC' : 'DESC';
    } else {
        // 異なるカラムをクリックした場合: 現在のソート方向を反転させて、新しいカラムの最初のソート順とする
        $order = ($current_order === 'DESC') ? 'ASC' : 'DESC';
    }
    
    $params = http_build_query(array_merge($search_params, ['sort' => $column, 'order' => $order, 'page' => $current_page]));
    return "member.php?{$params}";
}

// ソートインジケーター生成関数（ソート中のカラムの実際の順序を表示）
function getSortIndicator($column, $current_sort, $current_order) {
    // 1. 現在ソート中のカラムである場合、その実際の順序を返す
    if ($column === $current_sort) {
        return ($current_order === 'ASC') ? '▼' : '▼';
    }

    // 2. 現在ソート中でないカラムの場合:
    //    getSortUrl のロジックに基づき、次に適用される順序を返す（現在の順序の逆）
    if (in_array($column, ['id', 'created_at'])) {
        // 現在のソート順を反転させたものを表示
        return ($current_order === 'DESC') ? '▼' : '▼';
    }
    
    // その他のカラムは空文字を返す
    return '';
}

// ----------------------------------------------------
// 6. ビューファイルの読み込み
// ----------------------------------------------------
require_once 'member.html.php';
?>