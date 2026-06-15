<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header('Location: login.html?error=missing');
    exit;
}

$stmt = $pdo->prepare('SELECT User_id, Username, Password, Role FROM user_table WHERE Email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['Password'])) {
    header('Location: login.html?error=invalid');
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = $user['User_id'];
$_SESSION['fullname'] = $user['Username'];
$_SESSION['role'] = $user['Role'];

header('Location: index.html');
exit;
