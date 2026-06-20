<?php
// 🛠️ Force PHP to show any hidden errors or database mismatches immediately
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
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
    // 🛠️ FIX: Changed lowercase 'email' to uppercase 'Email' to match your table layout
    $stmt = $pdo->prepare(
        'SELECT User_id, Username, Password, Role FROM user_table WHERE Email = ? LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();


    if (!$user || !password_verify($password, $user['Password'])) {
        header('Location: login.php?error=invalid_credentials');
        exit;
    }

    // 🛠️ FIX: Removed the duplicate session_start() that was crashing the runtime engine here
    session_regenerate_id(true);

    $_SESSION['User_id'] = (int)$user['User_id'];
    $_SESSION['Username'] = $user['Username'];
    $_SESSION['Role'] = $user['Role'];

    if ($user['Role'] === 'Farmer') {
        header('Location: farmers.php');
        exit;
    }

    if ($user['Role'] === 'Customer') {
        header('Location: products.php');
        exit;
    }

    if ($user['Role'] === 'Transporter') {
        header('Location: transporters.php');
        exit;
    }

    header('Location: products.php');
    exit;

} catch (PDOException $e) {
    // 🛠️ FIX: Stop hiding the error behind a redirect so we can debug instantly if it fails
    echo "<h1>Login Database Error Diagnostic</h1>";
    echo "<b>Error Message:</b> " . $e->getMessage() . "<br>";
    echo "<b>Line number:</b> " . $e->getLine();
    exit();
}