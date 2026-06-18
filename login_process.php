<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php?error=invalid_request');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: login.php?error=invalid_credentials');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'SELECT user_id, username, password, role FROM user_infor WHERE email = ? LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        header('Location: login.php?error=invalid_credentials');
        exit;
    }

    session_start();
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    if ($user['role'] === 'Farmer') {
        header('Location: farmer_dashboard.php');
        exit;
    }

    if ($user['role'] === 'Customer') {
        header('Location: products.php');
        exit;
    }

    if ($user['role'] === 'Transporter') {
        header('Location: transporters.php');
        exit;
    }

    header('Location: products.php');
    exit;
} catch (PDOException $e) {
    error_log('Login error: ' . $e->getMessage());
    header('Location: login.php?error=invalid_credentials');
    exit;
}


