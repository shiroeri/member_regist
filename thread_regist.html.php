<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="member_regist.css"> 
    <style>
        .error-message { color: red; font-size: 0.9em; margin-top: 5px; }
        .is-error { border: 1px solid red; }
        .form-input { width: 90%; padding: 8px; margin-top: 5px; }
        textarea { resize: vertical; }
        .container { max-width: 600px; margin: 30px auto; padding: 20px; border: 1px solid #ccc; }
        .btn { padding: 10px 20px; cursor: pointer; border-radius: 4px; }
        .btn-primary { background-color: #007bff; color: white; border: none; }
        .btn-secondary { background-color: #6c757d; color: white; border: none; }
        .complete-screen { text-align: center; padding: 40px 0; }
        .button-group-row { display: flex; justify-content: center; align-items: center; gap: 10px; }
    </style>
</head>

<body>
    <div class="container">

        <?php if ($mode === 'complete'): // -------------------- 完了画面 -------------------- 
            /* * * NOTE: 現在のthread_regist.phpではDB登録成功後、直接logout.phpにリダイレクトするため、
             * この完了画面は実質使用されません。代わりにトップ画面に遷移します。
             * */
        ?>
            
            <div class="complete-screen">
                <h1 class="header-title">スレッド作成確認画面</h1>
                <p style="margin-top: 30px;">スレッド「<?= htmlspecialchars($formData['title']) ?>」を作成しました。</p>
                <a href="logout.php" class="btn btn-primary" style="text-decoration: none; margin-top: 20px;">トップに戻る</a>
            </div>

        <?php else: // -------------------- 入力画面 (input) または 確認画面 (confirm) -------------------- ?>
            
            <h1 class="header-title"><?= ($mode === 'input') ? 'スレッド作成フォーム' : 'スレッド作成確認画面' ?></h1>

            <?php 
            // グローバルエラー表示
            if (isset($errors['global'])): ?>
                <div class="error-box" style="color: red; margin-bottom: 20px;"><?= htmlspecialchars($errors['global']) ?></div>
            <?php endif; ?>

            
            <div class="form-group">
                <label for="title" class="form-label">スレッドタイトル</label>
                
                <?php if ($mode === 'confirm'): ?>
                    <p style="font-weight: bold; border: 1px solid #eee; padding: 10px;"><?= htmlspecialchars($formData['title']) ?></p>
                <?php else: ?>
                    <form action="thread_regist.php" method="POST">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"> 
                        <input type="text" id="title" name="title" value="<?= htmlspecialchars($formData['title']) ?>" class="form-input <?= isset($errors['title']) ? 'is-error' : '' ?>">
                        <?php if (isset($errors['title'])): ?>
                            <p class="error-message"><?= htmlspecialchars($errors['title']) ?></p>
                        <?php endif; ?>
                    
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="content" class="form-label">コメント</label>
                
                <?php if ($mode === 'confirm'): ?>
                    <p style="white-space: pre-wrap; border: 1px solid #eee; padding: 10px;"><?= htmlspecialchars($formData['content']) ?></p>
                <?php else: ?>
                    <textarea id="content" name="content" class="form-input <?= isset($errors['content']) ? 'is-error' : '' ?>" rows="5"><?= htmlspecialchars($formData['content']) ?></textarea>
                    <?php if (isset($errors['content'])): ?>
                        <p class="error-message"><?= htmlspecialchars($errors['content']) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="form-group button-group-row" style="margin-top: 30px;">
                <?php if ($mode === 'confirm'): ?>
                    
                    <div>
                        <form action="thread_regist.php" method="POST" style="margin-right: 10px;">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"> 
                            <input type="hidden" name="title" value="<?= htmlspecialchars($formData['title']) ?>">
                            <input type="hidden" name="content" value="<?= htmlspecialchars($formData['content']) ?>">
                            <input type="hidden" name="mode" value="confirm">
                            <input type="hidden" name="action" value="register"> 
                            <button type="submit" class="btn btn-primary" name="submit">スレッドを作成する</button>
                        </form>
                    </div>
                    
                    <div>
                        <form action="thread_regist.php" method="POST" style="margin-right: 10px;">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"> 
                            <input type="hidden" name="title" value="<?= htmlspecialchars($formData['title']) ?>">
                            <input type="hidden" name="content" value="<?= htmlspecialchars($formData['content']) ?>">
                            <button type="submit" class="btn btn-secondary" name="back_to_input">前に戻る</button>
                        </form>
                    </div>
                    
                    
                    <!-- <a href="logout.php" class="btn btn-secondary" style="text-decoration: none;">トップに戻る</a> -->

                <?php else: ?>
                    
                    <div>
                        <form>
                            <button type="submit" class="btn btn-primary" name="mode" value="confirm">確認画面へ</button>
                        </form>
                    </div>
                    <br>
                    <div>
                        <a href="logout.php" class="btn btn-secondary" style="text-decoration: none; margin-left: 10px;">トップに戻る</a>
                    </div>
                    
                    
                <?php endif; ?>
            </div>
            
            <?php if ($mode === 'input'): ?>
                <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>