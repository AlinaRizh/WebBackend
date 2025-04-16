<?php
header('Content-Type: text/html; charset=UTF-8');

$allowed_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];
$allowed_sex = ['Мужской', 'Женский'];

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    setcookie('errors', '', time() - 3600, '/');
    setcookie('forma', '', time() - 3600, '/');
    include('form.php');
    exit();
}

$polya = ['name', 'phone', 'email', 'birthday', 'gender', 'language', 'biography', 'contract'];
$forma = [];
foreach ($polya as $pole) {
    if (isset($_POST[$pole])) {
        $forma[$pole] = $_POST[$pole];
    }
}

$errors = [];
if (empty($_POST['name'])) {
    $errors['name'] = 'Введите корректное ФИО!';
} elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s]+$/u', $_POST['name'])) {
    $errors['name'] = 'ФИО может содержать лишь кириллицу/латинский алфавит!';
}
if (empty($_POST['phone'])) {
    $errors['phone'] = 'Введите корректно телефон!';
} elseif (!preg_match('/^\+7[0-9]{10}$/', $_POST['phone'])) {
    $errors['phone'] = 'Номер телефона должен начинаться с +7 либо 8!';
}
if (empty($_POST['email'])) {
    $errors['email'] = 'Введите корректный Email!';
} elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Почта должна быть формата example@domain.com!';
}
if (empty($_POST['birthday'])) {
    $errors['birthday'] = 'Введите дату рождения!';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['birthday'])) {
    $errors['birthday'] = 'Дата должна быть формата дд-мм-гггг!';
}
if (empty($_POST['gender'])) {
    $errors['gender'] = 'Укажите пол!';
} elseif(!in_array($_POST['gender'], $allowed_sex)) {
    $errors['gender'] = 'Выберите корректный пол!';
}
if (empty($_POST['language'])) {
    $errors['language'] = 'Выберите язык программирования!';
} else {
    foreach ($_POST['language'] as $lang) {
        if (!in_array($lang, $allowed_languages)) {
            $error['language'] = 'Указан некорректный/неподдерживаемый язык';
            break;
        }
    }
}
if (!empty($_POST['biography']) && !preg_match('/^[а-яА-ЯёЁa-zA-Z0-9\s]+$/u', $_POST['biography'])) {
    $errors['biography'] = 'Биография должна содержать лишь допустимые символы';
}
if (empty($_POST['contract'])) {
    $errors['contract'] = 'Необходимо подтверждение!';
}
if (!empty($errors)) {
    setcookie('errors', json_encode($errors), 0, '/');
    setcookie('forma', json_encode($forma), 0, '/');
    header('Location: ' . $_SERVER['PHP_SELF']);
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

    setcookie('savedForma', json_encode($forma), time() + 60 * 60 * 24 * 365, '/');

    setcookie('errors', '', time() - 3600, '/');
    setcookie('forma', '', time() - 3600, '/');

    header('Location: ?sendmsg=1');
    exit();
} 
catch (PDOException $e) {
    $db->rollBack();
    die("Ошибка БД:" . $e->getMessage());
}
?>