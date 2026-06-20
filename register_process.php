<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php?error=invalid_request');
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = trim($_POST['role'] ?? '');
$allowedRoles = ['Customer', 'Farmer', 'Transporter'];

// Validate required fields
if ($username === '' || $email === '' || $password === '' || !in_array($role, $allowedRoles, true)) {
    header('Location: register.php?error=invalid_input');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: register.php?error=invalid_email');
    exit;
}

try {
    // Start transaction to ensure both user and role-specific records are created together
    $pdo->beginTransaction();

    // Ensure email is unique
    $checkStmt = $pdo->prepare('SELECT User_id FROM user_table WHERE Email = ?');
    $checkStmt->execute([$email]);

    if ($checkStmt->fetch()) {
        $pdo->rollBack();
        header('Location: register.php?error=account_exists');
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insertUserStmt = $pdo->prepare(
        'INSERT INTO user_table (Username, Email, Password, Role) VALUES (?, ?, ?, ?)'
    );
    $insertUserStmt->execute([$username, $email, $hashedPassword, $role]);

    $userId = $pdo->lastInsertId();

    if ($role === 'Farmer') {
        // 🛠️ FIX: Fixed table name to farmers_infor, matched uppercase columns, and provided 4 placeholders/values
        $insertFarmerStmt = $pdo->prepare(
            'INSERT INTO farmers_infor (User_id, Farm_name, Location, Phone) VALUES (?, ?, ?, ?)'
        );
        $insertFarmerStmt->execute([$userId, 'Pending Setup', 'Pending Setup', '0000000000']);

    } elseif ($role === 'Customer') {
        // 🛠️ FIX: Matched uppercase columns for customer_infor
        $insertCustomerStmt = $pdo->prepare(
            'INSERT INTO customer_infor (User_id, Phone, Address) VALUES (?, ?, ?)'
        );
        $insertCustomerStmt->execute([$userId, '0000000000', 'Pending Setup']);

    } elseif ($role === 'Transporter') {
        // 🛠️ FIX: Matched exact table columns from your transporters table layout
        $insertTransporterStmt = $pdo->prepare(
            'INSERT INTO transporters (User_id, Company_name, Vehicle, Phone) VALUES (?, ?, ?, ?)'
        );
        $insertTransporterStmt->execute([$userId, 'Pending Setup', 'Pending Setup', '0000000000']);
    }

    $pdo->commit();

    header('Location: login.php?registered=success');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // This forces the page to stop and print the exact SQL problem if something goes wrong
    echo "<h1>Database Error Diagnostic</h1>";
    echo "<b>Error Message:</b> " . $e->getMessage() . "<br>";
    echo "<b>Error Code:</b> " . $e->getCode() . "<br>";
    echo "<b>Line number:</b> " . $e->getLine();
    exit();
}