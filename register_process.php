<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.html');
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';
$allowedRoles = ['Customer', 'Farmer', 'Transporter'];

if ($username === '' || $email === '' || $phone === '' || $password === '' || !in_array($role, $allowedRoles, true)) {
    header('Location: register.html?error=invalid');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: register.html?error=invalid_email');
    exit;
}

$check = $pdo->prepare('SELECT User_id FROM user_table WHERE Email = ?');
$check->execute([$email]);
if ($check->fetch()) {
    header('Location: register.html?error=account_exists');
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$insertUser = $pdo->prepare('INSERT INTO user_table (Username, Email, Password, Role) VALUES (?, ?, ?, ?)');
$insertUser->execute([$username, $email, $hashedPassword, $role]);
$userId = $pdo->lastInsertId();

if ($role === 'Customer') {
    $insertProfile = $pdo->prepare('INSERT INTO customer_infor (User_id, Username, Phone, Address) VALUES (?, ?, ?, ?)');
    $insertProfile->execute([$userId, $username, $phone, '']);
} elseif ($role === 'Farmer') {
    $insertProfile = $pdo->prepare('INSERT INTO farmers_infor (User_id, Farm_name, Location, Phone) VALUES (?, ?, ?, ?)');
    $insertProfile->execute([$userId, $username, '', $phone]);
} else {
    $insertProfile = $pdo->prepare('INSERT INTO transporters (User_id, Company_name, Vehicle, Phone) VALUES (?, ?, ?, ?)');
    $insertProfile->execute([$userId, $username, 'Unknown', $phone]);
}

header('Location: login.html?registered=1');
exit;
