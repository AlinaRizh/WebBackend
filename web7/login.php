<?php
header('Content-Type: text/html; charset=UTF-8');
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: defaut-src 'self'; script-src 'self'");

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

session_start();

$cfg = parse_ini_file('/config.ini');
$username = $cfg['database_username'];
$password = $cfg['database_password'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] =  bin2hex(random_bytes(32));
}

if (!empty($_SESSION['login'])) {
    header('Location: ./');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    include('login_form.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Недействительный CSRF-токен!");
    }

    $login = $_POST['login'] ?? '';
    $pass = $_POST['pass'] ?? '';
    $errors = [];

    if (empty($login)) {
        $errors[] = 'Введите логин';
    }
    if (empty($pass)) {
        $errors[] = 'Введите пароль';
    }

    if (!empty($errors)) {
        include('login_form.php');
        exit();
    }

    try {
        $db = new PDO("mysql:host=localhost;dbname=$username", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        ]);
    } catch (PDOException $e) {
    die("Ошибка БД!");
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['pass_hash'])) {
        $errors[] = 'Неверный логин или пароль';
        include('login_form.php');
        exit();
    }

    $_SESSION['login'] = $user['login'];
    $_SESSION['uid'] = $user['id'];

    header('Location: ./');
}
?>