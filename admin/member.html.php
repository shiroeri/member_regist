<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        /* CSSスタイル */
        .container { max-width: 1200px; margin: 30px auto; padding: 20px; border: 1px solid #ccc; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ccc; padding-bottom: 15px; margin-bottom: 20px; }
        .btn { padding: 8px 15px; cursor: pointer; border-radius: 4px; text-decoration: none; }
        .btn-primary { background-color: #007bff; color: white; border: none; }
        .btn-secondary { background-color: #6c757d; color: white; border: none; }
        
        .search-form { background: #f4f4f4; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        
        /* 検索項目を縦並びにするためのスタイル */
        .search-group { 
            margin-bottom: 15px; 
            /* 💡 修正: 既定を縦並びに戻す */
            display: block; 
        } 
        /* 💡 修正: ID検索グループのみを横並びにする */
        .search-form .search-group:nth-child(1) { /* ID検索グループ */
            display: flex; 
            align-items: center; 
        }

        .search-group label { 
            display: block; 
            font-weight: bold; 
            margin-bottom: 5px; 
        }
        /* 💡 修正: ID検索行では label のマージンを調整 */
        .search-form .search-group:nth-child(1) label {
            margin-bottom: 0; 
            margin-right: 10px; /* IDラベルと入力欄の間隔 */
        }
        
        .search-group input[type="text"], 
        .search-group select { 
            padding: 5px; 
            width: 250px; 
        }
        
        /* 💡 追加: ID入力欄の幅を調整 */
        .search-group #id_search {
            width: 60px; /* ID入力欄の幅 */
        }
        
        /* 💡 追加: エラーメッセージのスタイル */
        .error-message {
            color: red;
            font-size: 0.9em;
            margin-left: 15px; /* 入力欄とエラーメッセージの間隔 */
            white-space: nowrap; /* 折り返し禁止 */
        }
        
        /* チェックボックスとリセットボタンのためのスタイル */
        .checkbox-group label { display: inline-block; margin-right: 15px; font-weight: normal; }

        .member-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .member-table th, .member-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .member-table th { background-color: #f2f2f2; }
        
        /* 💡 修正: ヘッダーの幅安定化のための flex 設定 */
        .member-table th a { 
            text-decoration: none; 
            color: inherit; 
            display: flex; /* flexboxを使ってテキストとインジケーターを均等配置 */
            justify-content: space-between; 
            align-items: center;
            white-space: nowrap; 
            width: 100%; 
        } 
        /* 💡 追加: インジケーター用の固定幅コンテナ */
        .sort-indicator-box {
            display: inline-block;
            width: 15px; /* インジケーターの固定幅 */
            text-align: right;
            font-size: 0.9em;
        }

        .pagination { display: flex; justify-content: center; margin-top: 20px; }
        .pagination a, .pagination span { padding: 8px 12px; margin: 0 5px; border: 1px solid #ddd; text-decoration: none; color: #333; }
        .pagination span.current, .pagination span.disabled { background-color: #007bff; color: white; border-color: #007bff; cursor: default; }
        .pagination span.disabled { background-color: #f8f8f8; color: #ccc; border-color: #ddd; }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="header">
            <h1>会員一覧</h1>
            <a href="top.php" class="btn btn-secondary">トップへ戻る</a> 
        </div>
        
        <form action="member.php" method="GET" class="search-form">
            
            <div class="search-group">
                <label for="id_search">ID</label>
                <input type="text" id="id_search" name="id" value="<?= htmlspecialchars($search_id) ?>" style="width: 60px;">
                
                <?php if (!empty($error_message_id)): ?>
                    <span class="error-message"><?= htmlspecialchars($error_message_id) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="search-group">
                <label>性別</label>
                <div class="checkbox-group">
                    <?php 
                    $gender_options = ['男性', '女性'];
                    
                    // $search_genderが配列でない可能性も考慮し、チェック状態を判定
                    $is_gender_array = is_array($search_gender);
                    
                    foreach ($gender_options as $option):
                        // コントローラ側で配列処理が必要
                        $checked = $is_gender_array ? in_array($option, $search_gender) : ($search_gender === $option);
                    ?>
                        <label>
                            <input type="checkbox" name="gender[]" value="<?= htmlspecialchars($option) ?>" <?= $checked ? 'checked' : '' ?>>
                            <?= htmlspecialchars($option) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="search-group">
                <label for="pref_search">都道府県</label>
                <select id="pref_search" name="pref">
                    <option value="">全て</option>
                    <?php 
                    foreach ($prefectures_list as $pref_name_option): 
                    ?>
                        <option value="<?= htmlspecialchars($pref_name_option) ?>" 
                            <?= $search_pref === $pref_name_option ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pref_name_option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="search-group">
                <label for="free_word_search">フリーワード</label>
                <input type="text" id="free_word_search" name="free" value="<?= htmlspecialchars($search_free) ?>" style="width: 250px;">
            </div>
            
            <div class="search-group" style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">検索する</button>
            </div>
        </form>

        <?php
        $search_params_base = [
            'id' => $search_id, 
            // 性別は配列のまま
            'gender' => $search_gender,
            'pref' => $search_pref, 
            'free' => $search_free
        ];
        ?>

        <p>全 <?= $total_members ?> 件中、<?= $total_members > 0 ? $offset + 1 : 0 ?>～<?= min($offset + MEMBERS_PER_PAGE, $total_members) ?> 件を表示しています。</p>

        <table class="member-table">
        <thead>
                <tr>
                    <th>
                        <a href="<?= htmlspecialchars(getSortUrl('id', $sort_column, $sort_order, $search_params_base, $page)) ?>">
                            <span>ID</span>
                            <span class="sort-indicator-box"><?= getSortIndicator('id', $sort_column, $sort_order) ?></span>
                        </a>
                    </th>
                    <th>氏名</th>
                    <th>性別</th>
                    <th>住所</th>
                    <th>
                        <a href="<?= htmlspecialchars(getSortUrl('created_at', $sort_column, $sort_order, $search_params_base, $page)) ?>">
                            <span>登録日時</span>
                            <span class="sort-indicator-box"><?= getSortIndicator('created_at', $sort_column, $sort_order) ?></span>
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members)): ?>
                    <tr><td colspan="5" style="text-align: center;">該当する会員はいません。</td></tr>
                <?php else: ?>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td><?= htmlspecialchars($member['id']) ?></td>
                            <td><?= htmlspecialchars($member['name_sei'] . ' ' . $member['name_mei']) ?></td>
                            <td>
                                <?php 
                                    if ($member['gender'] === '1' || $member['gender'] === 1) {
                                        echo '男性';
                                    } elseif ($member['gender'] === '2' || $member['gender'] === 2) {
                                        echo '女性';
                                    } else {
                                        echo htmlspecialchars($member['gender']);
                                    }
                                ?>
                            </td>
                            <td><?= htmlspecialchars($member['pref_name'] ?? '') . htmlspecialchars($member['address'] ?? '') ?></td>
                            <td><?= htmlspecialchars($member['created_at']) ?></td>
                            </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php 
                // 検索パラメータをURLクエリ文字列に変換（ソートとオーダーを含む）
                $pagination_params = http_build_query(array_merge($search_params_base, ['sort' => $sort_column, 'order' => $sort_order]));
                
                $prev_page = $page - 1;
                $next_page = $page + 1;

                // ----------------------------------------------------
                // ページャー表示ロジックの修正: 現在のページを中心とする最大3ページ
                // ----------------------------------------------------
                
                // 現在のページ($page)を中心とした表示開始ページを決定
                // 前後1ページを表示するため、$page-1 から開始。ただし最小は1。
                $start_page = max(1, $page - 1); 

                // 表示終了ページを決定
                // 最大$total_pagesか、$start_pageから2ページ進んだページ（合計3ページ）
                $end_page = min($total_pages, $start_page + 2);

                // もし末尾で3ページ表示できていない場合、開始ページを左にずらして調整
                // 例: total=4, page=4 の場合、初期は start=3, end=4。この場合 start=2 にずらす。
                if ($end_page - $start_page < 2 && $start_page > 1) {
                    $start_page = max(1, $end_page - 2);
                }
                
                // 総ページ数が0の場合は表示なし
                if ($total_pages === 0) {
                    $start_page = 1;
                    $end_page = 0;
                }
                // ----------------------------------------------------
            ?>

            <?php if ($page > 1): ?>
                <a href="member.php?page=<?= $prev_page ?>&<?= $pagination_params ?>">前へ</a>
            <?php else: ?>
                <span class="disabled">前へ</span>
            <?php endif; ?>

            <?php 
            // ページ番号リンクの表示
            for ($i = $start_page; $i <= $end_page; $i++): 
            ?>
                <?php if ($i == $page): ?> <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="member.php?page=<?= $i ?>&<?= $pagination_params ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="member.php?page=<?= $next_page ?>&<?= $pagination_params ?>">次へ</a>
            <?php else: ?>
                <span class="disabled">次へ</span>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>