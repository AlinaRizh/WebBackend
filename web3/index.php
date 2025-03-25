<?php
header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!empty($_GET['sendmsg'])) {
        echo '<div class="success">Форма отправлена!</div>';
    }
    include('form.html');
    exit();
}

$errors = [];
if (empty($_POST['name']) || !preg_match('/^[а-яА-ЯёЁa-zA-Z\s]+$/u', $_POST['name'])) {
    $errors[] = 'Введите корректное ФИО!';
}
if (empty($_POST['phone']) || !preg_match('/^\+7[0-9]{10}$/', $_POST['phone'])) {
    $errors[] = 'Введите корректно телефон!';
}
if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Введите корректный Email!';
}
if (empty($_POST['birthday'])) {
    $errors[] = 'Введите дату рождения!';
}
if (empty($_POST['gender']) || !in_array($_POST['gender'], ['Мужской', 'Женский'])) {
    $errors[] = 'Укажите пол!';
}
if (empty($_POST['language'])) {
    $errors[] = 'Выберите язык программирования!';
}
if (empty($_POST['contract'])) {
    $errors[] = 'Необходимо подтверждение!';
}
if (!empty($errors)) {
    echo '<div class="errors">';
    foreach ($errors as $error) {
        echo htmlspecialchars($error) . '<br>';
    }
    echo '</div>';
    include('form.html');
    exit();
}

try {
    $db = new PDO("mysql:host=localhost;dbname=u69070", 'u69070', '2731078', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
    ]);

    $db->beginTransaction();

    $statement = $db->prepare("INSERT INTO requests (name, phone, email, birthday, gender, biography) 
                         VALUES (:name, :phone, :email, :birthday, :gender, :biography)");

    $statement->execute([
        ':name' => $_POST['name'],
        ':phone' => $_POST['phone'],
        ':email' => $_POST['email'],
        ':birthday' => $_POST['birthday'],
        ':gender' => $_POST['gender'],
        ':biography' => $_POST['biography'] ?? null
    ]);

    $reqId = $db->lastInsertId();

    $statement = $db->prepare("INSERT INTO request_languages (request_id, language_id)
                          SELECT :req_id, id FROM languages WHERE name = :lang_name");

    foreach ($_POST['language'] as $lang) {
        $statement->execute([':req_id' => $reqId, ':lang_name' => $lang]);
    }

    $db->commit();
    header('Location: ?sendmsg=1');
    exit();
} 
catch (PDOException $e) {
    $db->rollBack();
    die("Ошибка БД:" . $e->getMessage());
}

?>