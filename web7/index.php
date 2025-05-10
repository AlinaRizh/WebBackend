<?php
header('Content-Type: text/html; charset=UTF-8');
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: defaut-src 'self'; script-src 'self'");

session_start();

$allowed_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];
$allowed_sex = ['Мужской', 'Женский'];

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = [];
    
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', time() - 3600, '/');
        $messages[] = 'Спасибо, результаты сохранены.';
        
        if (!empty($_COOKIE['login']) && !empty($_COOKIE['pass'])) {
            $messages[] = sprintf(
                'Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong> и паролем <strong>%s</strong> для изменения данных.',
                htmlspecialchars($_COOKIE['login'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($_COOKIE['pass'], ENT_QUOTES, 'UTF-8')
            );
            setcookie('login', '', time() - 3600, '/');
            setcookie('pass', '', time() - 3600, '/');
        }
    }
    
    $errors = [];
    if (!empty($_COOKIE['errors'])) {
        $errors = json_decode($_COOKIE['errors'], true) ?? [];
    }
    
    $forma = [];
    if (!empty($_SESSION['login'])) {
        $db = new PDO("mysql:host=localhost;dbname=u69070", 'u69070', '2731078', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        ]);

        $stmt = $db->prepare("SELECT r.* FROM requests r JOIN users u ON r.id = u.request_id WHERE u.login = ?");
        $stmt->execute([$_SESSION['login']]);
        $forma = $stmt->fetch();
        
        if ($forma) {
            $stmt = $db->prepare("SELECT l.name FROM request_languages rl JOIN languages l ON rl.language_id = l.id WHERE rl.request_id = ?");
            $stmt->execute([$forma['id']]);
            $forma['language'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    } elseif (!empty($_COOKIE['savedForma'])) {
        $forma = json_decode($_COOKIE['savedForma'], true);
    } elseif (!empty($_COOKIE['forma'])) {
        $forma = json_decode($_COOKIE['forma'], true);
    }
    
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
    $errors['phone'] = 'Номер телефона должен начинаться с +7!';
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
            $errors['language'] = 'Указан некорректный/неподдерживаемый язык';
            break;
        }
    }
}
if (!empty($_POST['biography']) && !preg_match('/^[а-яА-ЯёЁa-zA-Z0-9\s.,!?-]+$/u', $_POST['biography'])) {
    $errors['biography'] = 'Биография должна содержать лишь допустимые символы';
}
if (empty($_POST['contract'])) {
    $errors['contract'] = 'Необходимо подтверждение!';
}

if (!empty($errors)) {
    setcookie('savedForma', '', time() - 3600, '/');
    setcookie('forma', json_encode($forma), 0, '/');
    setcookie('errors', json_encode($errors), 0, '/');
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
    
    if (!empty($_SESSION['login'])) {
        $stmt = $db->prepare("SELECT r.id FROM requests r JOIN users u ON r.id = u.request_id WHERE u.login = ?");
        $stmt->execute([$_SESSION['login']]);
        $requestId = $stmt->fetchColumn();
        
        if ($requestId) {
            $stmt = $db->prepare("UPDATE requests SET name = :name, phone = :phone, email = :email, 
                                 birthday = :birthday, gender = :gender, biography = :biography WHERE id = :id");
            $stmt->execute([
                ':name' => $_POST['name'],
                ':phone' => $_POST['phone'],
                ':email' => $_POST['email'],
                ':birthday' => $_POST['birthday'],
                ':gender' => $_POST['gender'],
                ':biography' => $_POST['biography'] ?? null,
                ':id' => $requestId
            ]);
            
            $stmt = $db->prepare("DELETE FROM request_languages WHERE request_id = ?");
            $stmt->execute([$requestId]);
            
            $stmt = $db->prepare("INSERT INTO request_languages (request_id, language_id)
                              SELECT :req_id, id FROM languages WHERE name = :lang_name");
            foreach ($_POST['language'] as $lang) {
                $stmt->execute([':req_id' => $requestId, ':lang_name' => $lang]);
            }
        }
    } else {
        $stmt = $db->prepare("INSERT INTO requests (name, phone, email, birthday, gender, biography) 
                         VALUES (:name, :phone, :email, :birthday, :gender, :biography)");
        $stmt->execute([
            ':name' => $_POST['name'],
            ':phone' => $_POST['phone'],
            ':email' => $_POST['email'],
            ':birthday' => $_POST['birthday'],
            ':gender' => $_POST['gender'],
            ':biography' => $_POST['biography'] ?? null
        ]);
        
        $requestId = $db->lastInsertId();
        
        $stmt = $db->prepare("INSERT INTO request_languages (request_id, language_id)
                          SELECT :req_id, id FROM languages WHERE name = :lang_name");
        foreach ($_POST['language'] as $lang) {
            $stmt->execute([':req_id' => $requestId, ':lang_name' => $lang]);
        }
        
        $login = uniqid('user_');
        $pass = bin2hex(random_bytes(4));
        $passHash = password_hash($pass, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO users (request_id, login, pass_hash) VALUES (?, ?, ?)");
        $stmt->execute([$requestId, $login, $passHash]);
        
        setcookie('login', $login, time() + 60 * 60 * 24 * 30, '/');
        setcookie('pass', $pass, time() + 60 * 60 * 24 * 30, '/');
    }
    
    $db->commit();
    
    setcookie('errors', '', time() - 3600, '/');
    setcookie('forma', '', time() - 3600, '/');
    setcookie('savedForma', json_encode($forma), time() + 60 * 60 * 24 * 365, '/');
    setcookie('save', '1', time() + 60 * 60 * 24 * 365, '/');
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
} catch (PDOException $e) {
    $db->rollBack();
    die("Ошибка БД: " . $e->getMessage());
}
?>