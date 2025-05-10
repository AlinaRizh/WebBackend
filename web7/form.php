<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <title>Задача 5</title>
  <link href="./css/style.css" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>

<?php if (!empty($errors)): ?>
    <div class="errors">
      <?php foreach ($errors as $error): ?>
        <li class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  
  <?php if (!empty($_SESSION['login'])): ?>
    <div class="logout">
      <form action="logout.php" method="post">
        <input type="submit" value="Выйти">
      </form>
    </div>
  <?php endif; ?>
  
  <div class="request_form">
    <form id="form" method="POST" action="index.php">
      <label for="name">Фамилия Имя Отчество:</label>
      <input class="<?= isset($errors['name']) ? 'has-error' : '' ?>" id="name" name="name" placeholder="Введите ФИО" type="text" value="<?=htmlspecialchars($forma['name'] ?? '', ENT_QUOTES, 'UTF-8')?>">
      <br>

      <label for="phone">Телефон:</label>
      <input id="phone" name="phone" placeholder="+71234567890" type="tel" value="<?=htmlspecialchars($forma['phone'] ?? '', ENT_QUOTES, 'UTF-8')?>" class="<?= isset($errors['phone']) ? 'has-error' : '' ?>">
      <br>

      <label for="email">Email:</label>
      <input id="email" name="email" placeholder="example@mail.ru"  type="email" value="<?=htmlspecialchars($forma['email'] ?? '', ENT_QUOTES, 'UTF-8')?>" class="<?= isset($errors['email']) ? 'has-error' : '' ?>">
      <br>

      <label for="birthday">Дата рождения:</label>
      <input id="birthday" name="birthday" type="date" value="<?=htmlspecialchars($forma['birthday'] ?? '', ENT_QUOTES, 'UTF-8')?>" class="<?= isset($errors['birthday']) ? 'has-error' : '' ?>">
      <br>

      <label>Пол:</label>
      <div class="<?= isset($errors['gender']) ? 'has-error' : '' ?>">
        <label>
          <input name="gender" type="radio" value="Женский" <?=@$forma['gender'] == 'Женский' ? 'checked' : '' ?>>
          Женский
        </label>
        <label>
          <input name="gender" type="radio" value="Мужской" <?=@$forma['gender'] == 'Мужской' ? 'checked' : '' ?>>
          Мужской
        </label>
      </div>
      <br>

      <label for="language">Любимый язык программирования:</label>
      <select id="language" name="language[]" multiple class="<?= isset($errors['language']) ? 'has-error' : '' ?>">
        <?php foreach ($allowed_languages as $lang): ?>
          <option value="<?=htmlspecialchars($lang, ENT_QUOTES, 'UTF-8')?>" <?= isset($forma['language']) && in_array($lang, $forma['language']) ? 'selected' : '' ?>> <?=htmlspecialchars($lang, ENT_QUOTES, 'UTF-8')?> </option>       
        <?php endforeach; ?>
      </select>
      <br>

      <label for="biography">Биография:</label>
      <textarea class="<?=isset($errors['biography']) ? 'has-error' : '' ?>" id="biography" name="biography" placeholder="Я родился..."><?=htmlspecialchars($forma['biography'] ?? '', ENT_QUOTES, 'UTF-8')?></textarea>
      <br>

      <div class="<?= isset($errors['contract']) ? 'has-error' : '' ?>">
        <label>
          <input name="contract" type="checkbox" value="on" <?=isset($forma['contract']) ? 'checked' : ''?>>
          С контрактом ознакомлен(а)
        </label>
      </div>
      <br>

      <button type="submit">Сохранить</button>
    </form>

    <?php if (!empty($messages)): ?>
      <div class="messages">
        <?php foreach ($messages as $message): ?>
          <div class="message"><?= $message ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</body>
</html>