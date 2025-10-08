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

        <!-- <?php 
        // グローバルエラーまたは認証エラーの表示
        if (isset($errors['global']) || isset($errors['auth'])): ?>
            <div class="error-box">
                <p><?= htmlspecialchars($errors['global'] ?? $errors['auth']) ?></p>
            </div>
        <?php endif; ?> -->

        <form action="login.php" method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"> 

            <div class="form-group mail_address">
                <label for="email" class="form-label">メールアドレス（ID）</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" class="form-input <?= isset($errors['email']) || isset($errors['auth']) ? 'is-error' : '' ?>">
                <?php 
                // エラー時はメールアドレスが表示され、エラーが表示される (仕様対応)
                if (isset($errors['email'])): ?>
                    <p class="error-message"><?= htmlspecialchars($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group pass-field">
    <label for="password" class="form-label">パスワード</label>
    <input 
        type="password" 
        id="password" 
        name="password" 
        value="" 
        class="form-input <?= isset($errors['password']) || isset($errors['auth']) ? 'is-error' : '' ?>" 
    >
    
    <?php if (isset($errors['password'])): ?>
        <p class="error-message"><?= htmlspecialchars($errors['password']) ?></p>
    <?php elseif (isset($errors['auth'])): ?>
        <p class="error-message">※IDもしくはパスワードが間違っています</p>
    <?php endif; ?>
</div>

            <div class="form-group confirmation_button ">
                <button type="submit" class="btn btn-primary">
                    ログイン
                </button>

                
            </div>
            <div style="text-align: center; margin-bottom: 20px;">
                <a href="logout.php" class="btn btn-secondary">トップに戻る</a>
            </div>
        </form>
    </div>
</body>

</html>