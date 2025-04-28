<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <link href="./css/style2.css" rel="stylesheet">
</head>
<body>
    <div class="main-content">
        <h1>Вход</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="login">Логин:</label>
                <input type="text" id="login" name="login" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="pass">Пароль:</label>
                <input type="password" id="pass" name="pass">
            </div>
            
            <div class="form-group">
                <input type="submit" value="Войти">
            </div>
        </form>
        
        <p><a href="./">Вернуться на главную</a></p>
    </div>
</body>
</html>