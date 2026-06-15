<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Customer') {
    header('Location: login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    header('Location: checkout.php?error=invalid');
    exit;
}

$customerStmt = $pdo->prepare('SELECT customer_id FROM customer_infor WHERE user_id = ?');
$customerStmt->execute([$_SESSION['user_id']]);
$customer = $customerStmt->fetch();
if (!$customer) {
    header('Location: checkout.php?error=customer_required');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

$cartJson = $_POST['cart_json'] ?? '';
$paymentMethod = trim($_POST['payment_method'] ?? '');
$fullName = trim($_POST['full_name'] ?? '');
$deliveryAddress = trim($_POST['delivery_address'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if ($cartJson === '' || !in_array($paymentMethod, ['EcoCash', 'Bank Transfer', 'Cash On Delivery'], true) || $fullName === '' || $deliveryAddress === '' || $phone === '') {
    header('Location: checkout.php?error=invalid');
    exit;
}

$items = json_decode($cartJson, true);
if (!is_array($items) || count($items) === 0) {
    header('Location: checkout.php?error=empty_cart');
    exit;
}

$total = 0;
foreach ($items as $item) {
    if (empty($item['product_id']) || empty($item['price']) || empty($item['quantity'])) {
        header('Location: checkout.php?error=invalid');
        exit;
    }
    $total += floatval($item['price']) * intval($item['quantity']);
}

try {
    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare('INSERT INTO Orders (customer_id, total_amount, order_status) VALUES (?, ?, ?)');
    $orderStmt->execute([$customer['customer_id'], $total, 'Pending']);
    $orderId = $pdo->lastInsertId();

    $detailStmt = $pdo->prepare('INSERT INTO Order_Details (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
    foreach ($items as $item) {
        $detailStmt->execute([
            $orderId,
            intval($item['product_id']),
            intval($item['quantity']),
            floatval($item['price'])
        ]);
    }

    $paymentStmt = $pdo->prepare('INSERT INTO Payments (order_id, payment_method, amount, payment_status) VALUES (?, ?, ?, ?)');
    $paymentStmt->execute([$orderId, $paymentMethod, $total, 'Pending']);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: checkout.php?error=failed');
    exit;
}

header('Location: checkout.php?success=1');
exit;
