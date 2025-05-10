<?php
header('Content-Type: text/html; charset=UTF-8');
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: defaut-src 'self'; script-src 'self'");

session_start();

if (!empty($_SESSION['login'])) {
    header('Location: ./');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    include('login_form.php');
    exit();
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
  $db = new PDO("mysql:host=localhost;dbname=u69070", 'u69070', '2731078', [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
  ]);
} catch (PDOException $e) {
  die("Ошибка БД: " . $e->getMessage());
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
?>