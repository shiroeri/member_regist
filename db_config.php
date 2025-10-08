<?php
// ----------------------------------------------------
// 0. 定数と初期設定
// ----------------------------------------------------

// データベース設定
const DB_HOST = 'localhost'; // MySQLホスト名 (環境に合わせて変更)
const DB_NAME = 'member_db'; // MySQLデータベース名 (事前に作成が必要)
const DB_USER = 'root';      // MySQLユーザー名 (環境に合わせて変更)
const DB_PASS = 'C8vDKUMhKe';  // MySQLパスワード (環境に合わせて変更)
const DB_TYPE = 'mysql';     // DBタイプを 'mysql' に変更
const DB_CHARSET = 'utf8mb4';

/**
 * PDO接続を確立し、インスタンスを返す
 * @return \PDO
 */
function getPdoConnection(): \PDO
{
    // DSN (Data Source Name) の構築
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    try {
        $pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            // オプション設定
            [
                // エラーモードを設定 (例外を投げるようにする)
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // フェッチモードを設定 (連想配列)
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // エミュレートプリペアドステートメントを無効に (セキュリティ向上)
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return $pdo;

    } catch (\PDOException $e) {
        // 接続失敗時の処理
        error_log("DB Connection failed: " . $e->getMessage());
        die("システムエラーが発生しました。データベースの接続設定を確認してください。");
    }
}

?>