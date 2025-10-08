<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="member_regist.css">
</head>

<body>
    <div class="container">
        <h1 class="header-title"><?= $title ?></h1>

        <?php 
        // グローバルエラーの表示
        if (isset($errors['global'])): ?>
            <div class="error-box">
                <p><?= htmlspecialchars($errors['global']) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($stage === 1): // -------------------- フォーム入力画面 -------------------- 
        ?>

            <form action="member_regist.php" method="POST">
                <input type="hidden" name="action" value="confirm">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"> 

                <div class="form-group name-group">
                    <div class="name-field">
                        <label for="name_sei" class="form-label">氏名（姓）</label>
                        <input type="text" id="name_sei" name="name_sei" value="<?= htmlspecialchars($formData['name_sei']) ?>" class="form-input <?= isset($errors['name_sei']) ? 'is-error' : '' ?>">
                        <?php if (isset($errors['name_sei'])): ?><p class="error-message"><?= htmlspecialchars($errors['name_sei']) ?></p><?php endif; ?>
                    </div>
                    <div class="name-field">
                        <label for="name_mei" class="form-label">氏名（名）</label>
                        <input type="text" id="name_mei" name="name_mei" value="<?= htmlspecialchars($formData['name_mei']) ?>" class="form-input <?= isset($errors['name_mei']) ? 'is-error' : '' ?>">
                        <?php if (isset($errors['name_mei'])): ?><p class="error-message"><?= htmlspecialchars($errors['name_mei']) ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <span class="form-label">性別</span>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="gender" value="1" <?= ((int)$formData['gender'] === 1) ? 'checked' : '' ?>>
                            <span>男性</span>
                        </label>
                        <label>
                            <input type="radio" name="gender" value="2" <?= ((int)$formData['gender'] === 2) ? 'checked' : '' ?>>
                            <span>女性</span>
                        </label>
                    </div>
                    <?php if (isset($errors['gender'])): ?><p class="error-message"><?= htmlspecialchars($errors['gender']) ?></p><?php endif; ?>
                </div>

                <div class="form-group">
                    <div class="prefectures">
                        <label for="pref_name" class="form-label">住所（都道府県）</label>
                        <select id="pref_name" name="pref_name" class="form-input form-select <?= isset($errors['pref_name']) ? 'is-error' : '' ?>">
                            <option value="">-- 選択してください --</option>
                            <?php
                            foreach (PREFECTURES as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= ($formData['pref_name'] === $p) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['pref_name'])): ?><p class="error-message"><?= htmlspecialchars($errors['pref_name']) ?></p><?php endif; ?>
                    </div>

                    <div class="after_address">
                        <label for="address" class="form-label">住所（それ以降の住所）</label>
                        <input type="text" id="address" name="address" value="<?= htmlspecialchars($formData['address']) ?>" class="form-input <?= isset($errors['address']) ? 'is-error' : '' ?>">
                        <?php if (isset($errors['address'])): ?><p class="error-message"><?= htmlspecialchars($errors['address']) ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <div class="pass-field">
                        <label for="password" class="form-label">パスワード</label>
                        <input type="password" id="password" name="password" value="" class="form-input <?= isset($errors['password']) ? 'is-error' : '' ?>">
                        <?php if (isset($errors['password'])): ?><p class="error-message"><?= htmlspecialchars($errors['password']) ?></p><?php endif; ?>
                    </div>
                    <br>
                    <div class="pass-field">
                        <label for="password_confirm" class="form-label">パスワード確認</label>
                         <input type="password" id="password_confirm" name="password_confirm" value="" class="form-input <?= isset($errors['password_confirm']) ? 'is-error' : '' ?>" placeholder="確認のため再入力">
                        <?php if (isset($errors['password_confirm'])): ?><p class="error-message"><?= htmlspecialchars($errors['password_confirm']) ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="form-group mail_address">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" class="form-input <?= isset($errors['email']) ? 'is-error' : '' ?>">
                    <?php if (isset($errors['email'])): ?><p class="error-message"><?= htmlspecialchars($errors['email']) ?></p><?php endif; ?>
                </div>

                <div class="form-group confirmation_button">
                    <button type="submit" class="btn btn-primary">
                        確認画面へ
                    </button>
                </div>
                <br>
                <div style="text-align: center; margin-bottom: 20px;">
                  <a href="logout.php" class="btn btn-secondary">トップに戻る</a>
                </div>
            </form>

        <?php elseif ($stage === 2): // -------------------- 確認画面 -------------------- 
        ?>

            <?php
            // 確認画面表示用のデータリスト
            // ★ 修正点: 表示用のキーを新しい名前に合わせる
            // $formData['gender']は数値 (1, 2) になっているため、GENDERS定数を使って文字列に変換
            $displayFields = [
                '氏名' => htmlspecialchars($formData['name_sei']) . ' ' . htmlspecialchars($formData['name_mei']),
                '性別' => htmlspecialchars(GENDERS[(int)$formData['gender']] ?? '未選択'),
                '住所' => htmlspecialchars($formData['pref_name']) . ' ' . htmlspecialchars($formData['address']),
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

            <div class="button-container button-group">
                <div class="verification_screen_button">
                    <form action="member_regist.php" method="POST" style="flex: 1;">
                        <input type="hidden" name="action" value="register">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <button type="submit" class="btn btn-success">
                            登録完了
                        </button>
                    </form>
                </div>
                <div class="verification_screen_button">
                    <form action="member_regist.php" method="POST" style="flex: 1;">
                        <input type="hidden" name="action" value="back">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"> 
                        <button type="submit" class="btn btn-secondary">
                            前に戻る
                        </button>
                    </form>
                </div>
            </div>

        <?php elseif ($stage === 3): // -------------------- 完了画面 -------------------- 
        ?>

            <div class="complete-screen">
                <svg xmlns="http://www.w3.org/2000/svg" class="complete-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2>会員登録が完了しました</h2>
                <br>
                <div style="text-align: center; margin-bottom: 20px;">
                  <a href="logout.php" class="btn btn-secondary">トップに戻る</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>