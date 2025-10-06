<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <!-- CSSファイルを読み込む -->
    <link rel="stylesheet" href="member_regist.css">
</head>
<body>
    <div class="container">
        <h1 class="header-title"><?= $title ?></h1>
        
        <?php if (!empty($errors)): ?>
            <!-- エラー表示エリア -->
            <div class="error-box">
                <p>入力エラーがあります</p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($stage === 1): // -------------------- フォーム入力画面 -------------------- ?>
            
            <form action="member_regist.php" method="POST">
                <input type="hidden" name="action" value="confirm">

                <!-- 氏名 -->
                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="name">
                        <label for="last_name" class="form-label">氏名（姓）</label>
                        <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($formData['last_name']) ?>" class="form-input" required>
                    </div>
                    <div class="name">
                        <label for="first_name" class="form-label">氏名（名）</label>
                        <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($formData['first_name']) ?>" class="form-input" required>
                    </div>
                </div>

                <!-- 性別 -->
                <div class="form-group">
                    <span class="form-label">性別</span>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="gender" value="男性" <?= ($formData['gender'] === '男性') ? 'checked' : '' ?> required>
                            <span>男性</span>
                        </label>
                        <label>
                            <input type="radio" name="gender" value="女性" <?= ($formData['gender'] === '女性') ? 'checked' : '' ?>>
                            <span>女性</span>
                        </label>
                    </div>
                </div>

                <!-- 住所 -->
                <div class="form-group">
                    <div class="prefectures">
                        <label for="prefecture" class="form-label">住所（都道府県）</label>
                        <select id="prefecture" name="prefecture" class="form-input form-select" required>
                            <option value="">-- 選択してください --</option>
                            <?php foreach ($prefectures as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= ($formData['prefecture'] === $p) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <br>
                    <div class="after_address">
                        <label for="address" class="form-label">住所（それ以降の住所）</label>
                        <input type="text" id="address" name="address" value="<?= htmlspecialchars($formData['address']) ?>" class="form-input" required>
                    </div>
                </div>
                <!-- パスワード -->
                <div class="form-group">
                    <div class="pass">
                        <label for="password" class="form-label">パスワード</label>
                        <!-- type="password" により、入力文字は非表示 -->
                        <input type="password" id="password" name="password" value="<?= htmlspecialchars($formData['password']) ?>" class="form-input" required>
                    </div>
                    <br>
                    <div class="pass">
                        <label for="password_confirm" class="form-label">パスワード確認</label>
                        <input type="password" id="password_confirm" name="password_confirm" value="<?= htmlspecialchars($formData['password_confirm']) ?>" class="form-input" placeholder="確認のため再入力" required>
                    </div>
                </div>

                <!-- メールアドレス -->
                <div class="form-group mail_address">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" class="form-input" required>
                </div>

                <div class="form-group confirmation_button">
                    <button type="submit" class="btn btn-primary">
                        確認画面へ
                    </button>
                </div>
            </form>

        <?php elseif ($stage === 2): // -------------------- 確認画面 -------------------- ?>
            
            <?php
            // 確認画面表示用のデータリスト
            $displayFields = [
                '氏名' => htmlspecialchars($formData['last_name']) . ' ' . htmlspecialchars($formData['first_name']),
                '性別' => htmlspecialchars($formData['gender']),
                '住所' => htmlspecialchars($formData['prefecture']) . ' ' . htmlspecialchars($formData['address']),
                // パスワード: セキュリティのため非表示
                'パスワード' => '<span class="password-hidden">セキュリティのため非表示</span>',
                'メールアドレス' => htmlspecialchars($formData['email']),
            ];
            ?>
            
            <dl class="confirm-list">
                <?php foreach ($displayFields as $label => $value): ?>
                    <div class="confirm-item">
                        <dt class="confirm-dt"><?= $label ?></dt>
                        <dd class="confirm-dd"><?= $value ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>

            <div class="button-container">
                <!-- 登録完了ボタン (前に移動) -->
                <form action="member_regist.php" method="POST">
                    <input type="hidden" name="action" value="register">
                    <button type="submit" class="btn btn-success">
                        登録完了
                    </button>
                </form>
            </div>
            <div class="button-container">
                <!-- 戻るボタン (後に移動) -->
                <form action="member_regist.php" method="POST">
                    <input type="hidden" name="action" value="back">
                    <button type="submit" class="btn btn-secondary">
                        前に戻る
                    </button>
                </form>
            </div>
            
        <?php elseif ($stage === 3): // -------------------- 完了画面 -------------------- ?>

            <div class="complete-screen">
                <!-- SVG Checkmark Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="complete-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2>会員登録が完了しました</h2>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
