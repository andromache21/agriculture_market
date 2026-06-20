<?php
session_start();
require_once 'db.php';

// 1. SECURITY CHECK: Ensure user is logged in as a Customer
if (empty($_SESSION['User_id']) || strtolower(trim($_SESSION['Role'] ?? '')) !== 'customer') {
    die("Validation Failed: You must be logged in as a Customer to check out.");
}

// 2. REQUEST METHOD CHECK: Must be a POST form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

// 3. DATABASE CHECK: Fetch the unique Customer_id for this logged-in User from customer_infor
$customerStmt = $pdo->prepare('SELECT Customer_id FROM customer_infor WHERE User_id = ?');
$customerStmt->execute([$_SESSION['User_id']]);
$customer = $customerStmt->fetch();

if (!$customer) {
    die("Validation Failed: No matching customer profile row found in 'customer_infor' for User_id: " . $_SESSION['User_id']);
}

// 4. READ POST FIELDS
$cartJson = $_POST['cart_json'] ?? '';
$paymentMethod = trim($_POST['payment_method'] ?? '');
$fullName = trim($_POST['full_name'] ?? '');
$deliveryAddress = trim($_POST['delivery_address'] ?? '');
$phone = trim($_POST['phone'] ?? '');

// 5. VALIDATION: Check for blank inputs
if ($cartJson === '' || !in_array($paymentMethod, ['EcoCash', 'Bank Transfer', 'Cash On Delivery'], true) || $fullName === '' || $deliveryAddress === '' || $phone === '') {
    die("Validation Failed: One of your inputs is blank or missing. <br>
         <b>Cart JSON:</b> " . htmlspecialchars($cartJson) . "<br>
         <b>Payment Method:</b> " . htmlspecialchars($paymentMethod) . "<br>
         <b>Name:</b> " . htmlspecialchars($fullName) . "<br>
         <b>Address:</b> " . htmlspecialchars($deliveryAddress) . "<br>
         <b>Phone:</b> " . htmlspecialchars($phone));
}

// 6. DECODE AND VERIFY CART ITEMS
$items = json_decode($cartJson, true);
if (!is_array($items) || count($items) === 0) {
    die("Validation Failed: Your cart data is empty or corrupted. Raw JSON: " . htmlspecialchars($cartJson));
}

// Calculate total cart value
$total = 0;
foreach ($items as $item) {
    if (!isset($item['product_id']) || !isset($item['price']) || !isset($item['quantity'])) {
        die("Validation Failed: Missing inner item properties (product_id, price, or quantity).");
    }
    $total += floatval($item['price']) * intval($item['quantity']);
}

// 7. TRANSACTION PROCESSING
try {
    $pdo->beginTransaction();

    // 1. Insert into order_infor table
    $orderStmt = $pdo->prepare('INSERT INTO order_infor (Customer_id, Total_amount, Order_status) VALUES (?, ?, ?)');
    $orderStmt->execute([$customer['Customer_id'], $total, 'Pending']);
    $orderId = $pdo->lastInsertId();

    // 2. Insert into order_details table
    $detailStmt = $pdo->prepare('INSERT INTO order_details (Order_id, Product_id, Quantity, Price) VALUES (?, ?, ?, ?)');
    foreach ($items as $item) {
        $detailStmt->execute([
            $orderId,
            intval($item['product_id']),
            intval($item['quantity']),
            floatval($item['price'])
        ]);
    }

    // 3. Insert into payment table
    $paymentStmt = $pdo->prepare('INSERT INTO payment (Order_id, Method, Amount, Status) VALUES (?, ?, ?, ?)');
    $paymentStmt->execute([$orderId, $paymentMethod, $total, 'Pending']);

    $pdo->commit();

    // Success! Redirect back to checkout.php with the success URL parameter
    header('Location: checkout.php?success=1');
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<h1>Checkout Database Error Diagnostic</h1>";
    echo "<b>Error Message:</b> " . $e->getMessage() . "<br>";
    echo "<b>Line number:</b> " . $e->getLine();
    exit();
}