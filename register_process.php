<?php
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
    $checkStmt = $pdo->prepare('SELECT user_id FROM user_infor WHERE email = ?');
    $checkStmt->execute([$email]);

    if ($checkStmt->fetch()) {
        $pdo->rollBack();
        header('Location: register.php?error=account_exists');
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insertUserStmt = $pdo->prepare(
        'INSERT INTO user_infor (username, email, password, role) VALUES (?, ?, ?, ?)'
    );
    $insertUserStmt->execute([$username, $email, $hashedPassword, $role]);

    $userId = $pdo->lastInsertId();

    if ($role === 'Farmer') {
        $insertFarmerStmt = $pdo->prepare(
            'INSERT INTO Farmers (user_id, farm_name, location) VALUES (?, ?, ?)'
        );
        $insertFarmerStmt->execute([$userId, 'Pending Setup', 'Pending Setup']);
    } elseif ($role === 'Customer') {
        $insertCustomerStmt = $pdo->prepare(
            'INSERT INTO customer_infor (user_id, full_name) VALUES (?, ?)'
        );
        $insertCustomerStmt->execute([$userId, $username]);
    } elseif ($role === 'Transporter') {
        // Add transporter placeholder row if your application maintains a transporters table
        $insertTransporterStmt = $pdo->prepare(
            'INSERT INTO transporters (user_id, company_name, location) VALUES (?, ?, ?)'
        );
        $insertTransporterStmt->execute([$userId, 'Pending Setup', 'Pending Setup']);
    }

    $pdo->commit();

    header('Location: login.php?registered=success');
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Registration error: ' . $e->getMessage());
    header('Location: register.php?error=server_error');
    exit;
}

