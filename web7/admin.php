<?php
header('Content-Type: text/html; charset=UTF-8');
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: defaut-src 'self'; script-src 'self'");

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

session_start();

$cfg = parse_ini_file('./db.ini');
$username = $cfg['database_username'];
$password = $cfg['database_password'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] =  bin2hex(random_bytes(32));
}

if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
        header('WWW-Authenticate: Basic realm="Restricted Area"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Требуется аутентификация.';
        exit;
    }

    try {
        $db = new PDO("mysql:host=localhost;dbname=$username", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $stmt = $db->prepare("SELECT * FROM admins WHERE login = :login");
        $stmt->execute([':login' => $_SERVER['PHP_AUTH_USER']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password'])) {
            header('WWW-Authenticate: Basic realm="Restricted Area"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Неверные учетные данные.';
            exit;
        }

        $_SESSION['admin_auth'] = true;
        $_SESSION['admin_login'] = $_SERVER['PHP_AUTH_USER'];
        
    } catch (PDOException $e) {
        die("Ошибка БД!");
    }
}

try {
    $db = new PDO("mysql:host=localhost;dbname=$username", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("Недействительный CSRF-токен!");
        }

        if (isset($_POST['delete'])) {
            $db->beginTransaction();
                
            $stmt = $db->prepare("DELETE FROM users WHERE request_id = :id");
            $stmt->execute([':id' => $_POST['delete']]);
                
            $stmt = $db->prepare("DELETE FROM request_languages WHERE request_id = :id");
            $stmt->execute([':id' => $_POST['delete']]);
                
            $stmt = $db->prepare("DELETE FROM requests WHERE id = :id");
            $stmt->execute([':id' => $_POST['delete']]);
                
            $db->commit();
            header("Location: admin.php");
            exit();
        } 
        elseif (isset($_POST['edit'])) {
            $stmt = $db->prepare("SELECT r.*, GROUP_CONCAT(l.name) as languages 
                                    FROM requests r 
                                    LEFT JOIN request_languages rl ON r.id = rl.request_id 
                                    LEFT JOIN languages l ON rl.language_id = l.id 
                                    WHERE r.id = :id 
                                    GROUP BY r.id");
            $stmt->execute([':id' => $_POST['edit']]);
            $userData = $stmt->fetch();
                
            if ($userData) {
                $editData = [
                    'id' => $userData['id'],
                    'name' => $userData['name'],
                    'phone' => $userData['phone'],
                    'email' => $userData['email'],
                    'birthday' => $userData['birthday'],
                    'gender' => $userData['gender'],
                    'biography' => $userData['biography'],
                    'languages' => $userData['languages'] ? explode(',', $userData['languages']) : []
                ];
            }
        } 
        elseif (isset($_POST['update'])) {
            $db->beginTransaction();
                
            $stmt = $db->prepare("UPDATE requests SET 
                                    name = :name, phone = :phone, email = :email, 
                                    birthday = :birthday, gender = :gender, biography = :biography 
                                    WHERE id = :id");
            $stmt->execute([
                ':name' => $_POST['name'],
                ':phone' => $_POST['phone'],
                ':email' => $_POST['email'],
                ':birthday' => $_POST['birthday'],
                ':gender' => $_POST['gender'],
                ':biography' => $_POST['biography'] ?? null,
                ':id' => $_POST['id']
            ]);
                
            $stmt = $db->prepare("DELETE FROM request_languages WHERE request_id = :req_id");
            $stmt->execute([':req_id' => $_POST['id']]);
                
            if (!empty($_POST['language'])) {
                $stmt = $db->prepare("INSERT INTO request_languages (request_id, language_id)                                        SELECT :req_id, id FROM languages WHERE name = :lang_name");
                foreach ($_POST['language'] as $lang) {
                    $stmt->execute([':req_id' => $_POST['id'], ':lang_name' => $lang]);
                }
            }
                
            $db->commit();
            header("Location: admin.php");
            exit();
        }
    } 

    $stmt = $db->prepare("SELECT r.*, GROUP_CONCAT(l.name) as languages 
                         FROM requests r 
                         JOIN request_languages rl ON r.id = rl.request_id 
                         JOIN languages l ON rl.language_id = l.id 
                         GROUP BY r.id");


    $stmt->execute();
    $requests = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT l.name, COUNT(rl.request_id) as count 
                         FROM languages l 
                         LEFT JOIN request_languages rl ON l.id = rl.language_id 
                         GROUP BY l.name 
                         ORDER BY count DESC");
    $stmt->execute();
    $languagesStat = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка БД!");
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./css/style3.css" rel="stylesheet">
    <title>Админка</title>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Админка</h1>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($editData)): ?>
            <div class="card edit-panel">
                <div class="card-header">Редактирование записи #<?= htmlspecialchars($editData['id'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($editData['id'], ENT_QUOTES, 'UTF-8') ?>">
                        
                        <div class="form-group">
                            <label>Полное имя</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($editData['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Контактный телефон</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($editData['phone'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Электронная почта</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($editData['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Дата рождения</label>
                            <input type="date" name="birthday" value="<?= htmlspecialchars($editData['birthday'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Пол</label>
                            <select name="gender" required>
                                <option value="Мужской" <?= $editData['gender'] == 'Мужской' ? 'selected' : '' ?>>Мужской</option>
                                <option value="Женский" <?= $editData['gender'] == 'Женский' ? 'selected' : '' ?>>Женский</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Биография</label>
                            <textarea name="biography"><?= htmlspecialchars($editData['biography'], ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Технические навыки (удерживайте Ctrl для выбора нескольких)</label>
                            <select name="language[]" multiple required>
                                <?php 
                                $languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskel', 'Clojure', 'Prolog', 'Scala', 'Go'];
                                foreach ($languages as $language): ?>
                                    <option value="<?= htmlspecialchars($language, ENT_QUOTES, 'UTF-8') ?>" 
                                        <?= in_array($language, $editData['languages']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($language, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" name="update" class="btn">Сохранить изменения</button>
                        <a href="admin.php" class="btn btn-secondary">Вернуться к списку</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">Зарегистрированные пользователи</div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя</th>
                            <th>Контакты</th>
                            <th>Дата рождения</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?= htmlspecialchars($request['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($request['name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                    <small><?= htmlspecialchars($request['gender'], ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($request['email'], ENT_QUOTES, 'UTF-8') ?><br>
                                    <?= htmlspecialchars($request['phone'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td><?= htmlspecialchars($request['birthday'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div class="action-btns">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="edit" value="<?= $request['id'] ?>">
                                            <button type="submit" class="btn">Изменить</button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить эту запись?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="delete" value="<?= $request['id'] ?>">
                                            <button type="submit" class="btn btn-danger">Удалить</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Статистика по языка программирования</div>
            <div class="card-body">
                <div class="stats-grid">
                    <?php foreach ($languagesStat as $stat): ?>
                        <div class="stat-card">
                            <h3><?= htmlspecialchars($stat['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p><?= htmlspecialchars($stat['count'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>