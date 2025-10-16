<?php
// member_form.php
// 会員登録 (regist) と会員編集 (edit) のロジックを統合した共通ファイル

// PHPセッションを開始
session_start();

// 🚀 修正: ブラウザのキャッシュを無効化するヘッダーを設定
// これにより、一覧画面から戻った際などに、ブラウザがセッション変更後の最新の状態を必ず取得するようになる
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');


// ----------------------------------------------------
// 認証チェック
// ----------------------------------------------------
// セッションに管理者ID（admin_id）が存在するかを確認します。
if (!isset($_SESSION['admin_id'])) {
    // 💡 認証失敗時のリダイレクト前にもセッションを閉じる
    session_write_close();
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

// --- モード判定 ---
// URLパラメータにIDが存在すれば編集モード、なければ登録モード
$member_id = $_GET['id'] ?? null;
$is_edit_mode = is_numeric($member_id) && $member_id > 0;
$action_label = $is_edit_mode ? '編集' : '登録';

// セッションキーの分離 (モードごと)
$session_key_data = $is_edit_mode ? 'edit_data' : 'regist_data';
$session_key_errors = $is_edit_mode ? 'edit_errors' : 'regist_errors';
$session_key_stage = $is_edit_mode ? 'edit_stage' : 'regist_stage';
$session_key_member_id = $is_edit_mode ? 'edit_member_id' : null;
$session_key_token = $is_edit_mode ? 'edit_token' : 'regist_token'; 
// 🚀 追加: PRGリダイレクトを判別するための一時フラグ
$session_key_prg_flag = $is_edit_mode ? 'edit_prg_flag' : 'regist_prg_flag';


// ステージ管理
$stage = $_SESSION[$session_key_stage] ?? 1;
$errors = $_SESSION[$session_key_errors] ?? [];

// エラー情報をクリア（フォーム画面表示前にクリアしておく）
unset($_SESSION[$session_key_errors]);


// --- 編集モード: IDチェックと既存データ取得 ---
$existing_data = null;
if ($is_edit_mode) {
    
    // IDがセッションと異なる場合、フォームデータをクリア
    if (isset($_GET['id']) && (string)$_GET['id'] !== (string)($_SESSION[$session_key_member_id] ?? '')) {
        unset($_SESSION[$session_key_data]); 
        unset($_SESSION[$session_key_stage]); 
    }

    // IDをセッションに保持
    $_SESSION[$session_key_member_id] = $member_id;

    $pdo = getPdoConnection();
    // 削除済み会員は取得しない
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = :id AND deleted_at IS NULL");
    $stmt->bindValue(':id', (int)$member_id, PDO::PARAM_INT);
    $stmt->execute();
    $existing_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing_data) {
        // 該当IDの会員が存在しない場合は一覧画面へリダイレクト
        unset($_SESSION[$session_key_member_id]);
        session_write_close(); // 💡 リダイレクト前にセッションを閉じる
        header('Location: member.php');
        exit;
    }
}

// GETアクセスの場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 💡 トークンはGETアクセス時にリセットし、新しいトークンを生成させる
    unset($_SESSION[$session_key_token]);

    // 🚀 PRGリダイレクトのチェックとフラグの削除
    $is_prg_redirect = $_SESSION[$session_key_prg_flag] ?? false;
    unset($_SESSION[$session_key_prg_flag]);

    // 🚀 Stage 2からの手動アクセスリセット (共通)
    if ($stage === 2 && !$is_prg_redirect) {
        // Stage 2 の状態が残っていて、それが PRG 直後ではない場合、Stage 1に強制リセット
        unset($_SESSION[$session_key_data]); // フォームデータもクリアして、フォームが初期化されるようにする
        unset($_SESSION[$session_key_stage]);
        $stage = 1; // ローカル変数も更新
    }

    // 🚀 修正: Stage 1でエラーセッションがない場合のデータリセット（登録/編集共通）
    // バリデーションエラー後に一覧に戻って再アクセスした際、古いセッションデータをクリアし、
    // 後のブロックでDBの最新データ（編集）または空データ（登録）をロードさせる。
    // エラーがある状態でのPRGリダイレクト（backボタンによる戻り）の時以外はリセットする。
    if ($stage === 1 && empty($errors) && !$is_prg_redirect) {
        unset($_SESSION[$session_key_data]);
        unset($_SESSION[$session_key_stage]);
        $stage = 1; // ローカル変数も更新
    }
}


// --- 二重送信防止トークンの管理 ---
// モード別トークンキーを使用。unsetされた場合、または未設定の場合に再生成。
if (!isset($_SESSION[$session_key_token])) {
    $_SESSION[$session_key_token] = bin2hex(random_bytes(32));
}
$token = $_SESSION[$session_key_token];


// ----------------------------------------------------
// 1. データ処理（POSTリクエストの処理）
// ----------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $post_token = $_POST['token'] ?? ''; 

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

        // 1. バリデーションの実行 (モードによって引数を変更)
        $pdo = getPdoConnection();
        // 編集モード判定とmember_idをバリデーション関数に渡す
        $errors = validateForm($current_post_data, $pdo, $is_edit_mode, $member_id);

        // 2. 画面遷移の決定
        if (empty($errors)) {
            $stage = 2;
            // トークンを再生成（確認画面から登録/更新へ）
            $_SESSION[$session_key_token] = bin2hex(random_bytes(32)); 
            // 🚀 追加: PRGフラグを設定
            $_SESSION[$session_key_prg_flag] = true; 
        } else {
            $stage = 1;
            $_SESSION[$session_key_errors] = $errors;
        }

        // フォームデータはセッションに保存 (パスワード系は除外)
        $_SESSION[$session_key_data] = array_diff_key(
            $current_post_data, 
            array_flip(['password', 'password_confirm'])
        );
        
        // エラーがない場合のみ、パスワードハッシュをセッションに保存
        if ($stage === 2) {
             // パスワードが入力されているかチェック (登録モード or 編集モードでパスワードが入力された場合)
             if (!$is_edit_mode || !empty($current_post_data['password'])) {
                 // 新しいパスワードが入力された場合、ハッシュ化して保存
                 $_SESSION[$session_key_data]['password_hash'] = password_hash($current_post_data['password'], PASSWORD_DEFAULT);
             } else {
                 // 編集モードでパスワードが空の場合、既存のハッシュをそのまま使用
                 $_SESSION[$session_key_data]['password_hash'] = $existing_data['password'];
             }
        }
        
        // 🚀 PRGパターン: Stage 1/2へ常にリダイレクトする
        $_SESSION[$session_key_stage] = $stage;
        session_write_close(); 
        header('Location: ' . ($is_edit_mode ? "member_edit.php?id={$member_id}" : "member_regist.php"));
        exit;


    // 確認画面から完了へ (stage 2 -> member.phpへリダイレクト)
    } elseif ($action === 'register' || $action === 'update') {
        
        // トークンチェック
        if (!isset($_SESSION[$session_key_token]) || $post_token !== $_SESSION[$session_key_token]) {
            
            // --- 💡 デバッグログ出力 & UI表示 (一時的な原因特定用) ---
            $session_token_prefix = isset($_SESSION[$session_key_token]) ? substr($_SESSION[$session_key_token], 0, 8) : 'NONE';
            $post_token_prefix = empty($post_token) ? 'EMPTY' : substr($post_token, 0, 8);

            $debug_hint = '';
            if (!isset($_SESSION[$session_key_token])) {
                $debug_hint = 'セッショントークンが見つかりません。';
            } elseif (empty($post_token)) {
                $debug_hint = '送信されたトークンが空です。';
            } else {
                $debug_hint = 'トークンが一致しません。';
            }

            error_log("Token Check Failed ({$session_key_token}) for {$action_label} (ID: {$member_id}). Hint: {$debug_hint}. Session Token: {$session_token_prefix}, POST Token: {$post_token_prefix}");
            // --- デバッグログ出力終了 ---

            // 💡 トークンエラーの場合、確実にステージ1に戻すために、トークンを再生成してリダイレクト
            $_SESSION[$session_key_errors]['global'] = "不正な操作または二重送信の可能性があります。最初からやり直してください。（ヒント: {$debug_hint} - Session: {$session_token_prefix}, POST: {$post_token_prefix}）";
            $_SESSION[$session_key_stage] = 1; 
            $_SESSION[$session_key_token] = bin2hex(random_bytes(32)); // 新しいトークンを生成
            
            // 🚀 最終対策: セッション不整合を強制的にリセットするために、セッションIDを再生成します
            session_regenerate_id(true);

            session_write_close(); // 💡 対策: エラーリダイレクト前にセッションを閉じる
            
            header('Location: ' . ($is_edit_mode ? "member_edit.php?id={$member_id}" : "member_regist.php"));
            exit;
        }

        // トークンチェック成功後、すぐにセッションから削除し、再利用を不可能にする
        unset($_SESSION[$session_key_token]); 

        // 2. データベース操作処理
        try {
            $pdo = getPdoConnection();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
            $pdo->beginTransaction();

            $formData = $_SESSION[$session_key_data]; 
            $password_hash = $formData['password_hash']; 

            if ($action === 'register') {
                // 登録 (INSERT) 処理
                $sql = "INSERT INTO members (name_sei, name_mei, gender, pref_name, address, password, email, created_at, updated_at) 
                        VALUES (:name_sei, :name_mei, :gender, :pref_name, :address, :password, :email, NOW(), NOW())";
            } else {
                // 更新 (UPDATE) 処理
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
            }
            
            $stmt = $pdo->prepare($sql);
            
            // 共通バインド
            $stmt->bindValue(':name_sei', $formData['name_sei']);
            $stmt->bindValue(':name_mei', $formData['name_mei']);
            $stmt->bindValue(':gender', (int)$formData['gender'], PDO::PARAM_INT);
            $stmt->bindValue(':pref_name', $formData['pref_name']);
            $stmt->bindValue(':address', $formData['address']);
            $stmt->bindValue(':password', $password_hash);
            $stmt->bindValue(':email', $formData['email']);
            
            // 編集モードでのみIDをバインド
            if ($action === 'update') {
                $stmt->bindValue(':id', (int)$member_id, PDO::PARAM_INT);
            }

            $stmt->execute(); 
            $pdo->commit();
            
            // 3. 完了後、セッションデータをクリアし、一覧へリダイレクト
            unset($_SESSION[$session_key_data]);
            unset($_SESSION[$session_key_errors]);
            unset($_SESSION[$session_key_prg_flag]); // 完了時はPRGフラグもクリア
            // トークンは既に上でunset済み
            unset($_SESSION[$session_key_stage]);
            if ($is_edit_mode) {
                unset($_SESSION[$session_key_member_id]);
            }
            
            session_write_close(); // 💡 対策: 成功リダイレクト前にセッションを閉じる

            header('Location: member.php');
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB transaction failed ({$action}): " . $e->getMessage()); 
            
            // エラー時もステージ2に戻る（確認画面）
            $_SESSION[$session_key_errors]['global'] = "{$action_label}処理中にデータベースエラーが発生しました。時間を置いて再度お試しください。";
            $_SESSION[$session_key_stage] = 2;
            $_SESSION[$session_key_prg_flag] = true; // DBエラー時も確認画面に留まるため、PRGフラグを立てておく
            // データベースエラーの場合、再試行のために新しいトークンを発行する
            $_SESSION[$session_key_token] = bin2hex(random_bytes(32)); 
            
            session_write_close(); // 💡 対策: DBエラーリダイレクト前にセッションを閉じる

            header('Location: ' . ($is_edit_mode ? "member_edit.php?id={$member_id}" : "member_regist.php"));
            exit;
        }

    // 確認画面からフォームへ戻る (stage 2 -> 1)
    } elseif ($action === 'back') {
        $stage = 1;
        // トークンを再生成し、フォームに戻った際に新しいトークンを使わせる
        $_SESSION[$session_key_token] = bin2hex(random_bytes(32));
        
        // 💡 修正点: 'back'時もリダイレクトを行い、クリーンなGETリクエストとしてステージ1を再表示させる
        $_SESSION[$session_key_stage] = $stage;
        $_SESSION[$session_key_prg_flag] = true; // Stage 1に戻るリダイレクトもPRGフラグを立てておく
        
        session_write_close(); // 💡 対策: 'back'リダイレクト前にセッションを閉じる
        
        header('Location: ' . ($is_edit_mode ? "member_edit.php?id={$member_id}" : "member_regist.php"));
        exit;
    }
}


// フォームのリロード時や初めてのアクセス時の初期データ設定
if (!isset($_SESSION[$session_key_data])) {
    if ($is_edit_mode && $existing_data) {
        // 編集モードの場合、DBの既存データを初期値にセット
        $_SESSION[$session_key_data] = [
            'name_sei' => $existing_data['name_sei'], 
            'name_mei' => $existing_data['name_mei'], 
            'gender' => $existing_data['gender'], 
            'pref_name' => $existing_data['pref_name'], 
            'address' => $existing_data['address'], 
            'email' => $existing_data['email'],
            // パスワードは空で初期化
            'password' => '',
            'password_confirm' => '',
            // 既存のハッシュを保持
            'password_hash' => $existing_data['password']
        ];
    } else {
        // 登録モードの場合、空のデータを初期値にセット
        $_SESSION[$session_key_data] = [
            'name_sei' => '', 
            'name_mei' => '', 
            'gender' => '', 
            'pref_name' => '', 
            'address' => '', 
            'email' => '',
            'password' => '',
            'password_confirm' => '',
            'password_hash' => ''
        ];
    }
}

$formData = $_SESSION[$session_key_data];

// 現在のステージをセッションに保存
$_SESSION[$session_key_stage] = $stage;


// ----------------------------------------------------
// 2. 共通バリデーション関数
// ----------------------------------------------------

/**
 * 登録・編集共通のフォームデータバリデーションを実行する
 * @param array $data フォームから受け取ったデータ
 * @param \PDO $pdo データベース接続
 * @param bool $is_edit_mode 編集モードか否か
 * @param int|null $member_id 編集対象の会員ID (登録時はnull)
 * @return array エラーメッセージの配列
 */
function validateForm(array $data, \PDO $pdo, bool $is_edit_mode, ?int $member_id): array {
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

    // --- 性別: 必須 ---
    $valid_gender_keys = array_keys(GENDERS);
    $gender = $data['gender'];
    if (!is_numeric($gender) || !in_array((int)$gender, $valid_gender_keys, true)) {
        $errors['gender'] = '性別は必須選択です。';
    }

    // --- 住所（都道府県）: 必須 ---
    if (empty($data['pref_name'])) {
        $errors['pref_name'] = '都道府県は必須選択です。';
    } elseif (!in_array($data['pref_name'], PREFECTURES, true)) {
        $errors['pref_name'] = '不正な都道府県が選択されました。';
    }

    // --- 住所（それ以降の住所）: 任意, 100文字以内 ---
    if (mb_strlen($data['address']) > 100) {
        $errors['address'] = '住所（それ以降）は100文字以内で入力してください。';
    }

    // --- パスワード: 登録時は必須、編集時は任意 ---
    $password = $data['password'];
    $password_confirm = $data['password_confirm'];
    
    $is_password_required = !$is_edit_mode; // 登録時は必須
    $is_password_updated = !empty($password) || !empty($password_confirm); // 編集時に入力があったか

    if ($is_password_required || $is_password_updated) {
        
        if (empty($password)) {
            $errors['password'] = $is_edit_mode ? 'パスワードを更新する場合は、パスワードを入力してください。' : 'パスワードは必須入力です。';
        } 
        // 💡 mb_strlen() で文字数として長さを正確に判定する
        elseif (mb_strlen($password) < 8 || mb_strlen($password) > 20) {
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


    // --- メールアドレス: 必須, DB重複チェック（モードによって除外あり） ---
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
            $sql = "SELECT COUNT(*) FROM members WHERE email = :email";
            if ($is_edit_mode) {
                // 編集モード: 自身のIDを除外
                $sql .= " AND id != :id";
            }
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            if ($is_edit_mode) {
                $stmt->bindValue(':id', $member_id, PDO::PARAM_INT);
            }
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $errors['email'] = 'このメールアドレスは既に登録されています。';
            }
        } catch (PDOException $e) {
            error_log("Email check failed in form: " . $e->getMessage());
            $errors['email'] = 'メールアドレスの重複チェック中にエラーが発生しました。';
        }
    }
    
    return $errors;
}


// ----------------------------------------------------
// 3. HTMLの出力
// ----------------------------------------------------

// テンプレートに渡す変数
$title = '会員' . $action_label; 
$form_action_url = $is_edit_mode ? "member_edit.php?id={$member_id}" : "member_regist.php";
$action_button_value = $is_edit_mode ? 'update' : 'register';
$action_button_label = $action_label . '完了';


// テンプレートファイルを読み込み、HTMLを出力
require_once 'member_form.html.php';
