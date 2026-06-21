<?php
// 🛠️ Force PHP to show any hidden errors or database mismatches immediately
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db.php';

// 🛠️ Authorization check matching exact capitalized session keys
if (empty($_SESSION['User_id']) || ($_SESSION['Role'] ?? '') !== 'Transporter') {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$message = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
        $errorMessage = 'Unable to validate this request. Please try again.';
    } else {
        $orderId = intval($_POST['order_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        $allowedStatuses = ['Pending', 'In Transit', 'Delivered'];

        if ($orderId <= 0 || !in_array($newStatus, $allowedStatuses, true)) {
            $errorMessage = 'Please select a valid order and status.';
        } else {
            $updateStmt = $pdo->prepare('UPDATE Orders SET order_status = ? WHERE order_id = ?');
            $updateStmt->execute([$newStatus, $orderId]);
            $message = 'Order status updated to ' . htmlspecialchars($newStatus) . '.';
        }
    }
}

try {
    // 🛠️ Completely removed o.created_at from the selection to prevent the error
    $orderStmt = $pdo->query(
        'SELECT o.Order_id AS order_id, o.Customer_id AS customer_id, o.Total_amount AS total_amount, o.Order_status AS order_status, c.Username AS customer_name, c.Phone AS customer_phone
         FROM order_infor o
         LEFT JOIN customer_infor c ON o.Customer_id = c.Customer_id
         ORDER BY o.Order_id DESC'
    );
    $orders = $orderStmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    $errorMessage = 'Database Order Query Error: ' . $e->getMessage();
}



// Fixed target columns based on your order_details schema visual
$detailStmt = $pdo->prepare('SELECT Product_id, Quantity, Price FROM order_details WHERE Order_id = ?');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transporter Dashboard - BC Fresh Market</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        main { padding: 20px 40px; }
        .dashboard-grid { display: grid; gap: 30px; max-width: 1100px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #e1e1e1; border-radius: 12px; padding: 24px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04); }
        .card h2 { margin-top: 0; color: #2e7d32; }
        .message { margin-bottom: 18px; padding: 16px; border-radius: 10px; }
        .success { background: #e6f4ea; color: #1f6a2e; }
        .error { background: #fdecea; color: #9b1a1a; }
        .order-card { border: 1px solid #e6e6e6; border-radius: 12px; padding: 20px; margin-bottom: 18px; }
        .order-card h3 { margin: 0 0 10px 0; }
        .order-card p { margin: 6px 0; }
        .btn-update { margin-top: 12px; padding: 10px 16px; background: #2e7d32; color: #fff; border: none; border-radius: 8px; cursor: pointer; }
        .nav-links a { margin-right: 18px; }
        .order-items { margin-top: 10px; font-size: 0.95rem; }
        .order-items li { margin-bottom: 6px; }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<main>
    <section class="dashboard-grid">
        <div class="card">
            <h2>Transporter Dashboard</h2>
            <p>Welcome, <?= htmlspecialchars($_SESSION['Username'] ?? 'Transporter') ?>. Review incoming delivery requests and update order status.</p>
            <?php if ($message): ?>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="message error"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>
        </div>

        <?php if (empty($orders)): ?>
            <div class="card">
                <h2>No Orders Available</h2>
                <p>No delivery orders are currently available. Once customers place orders, they will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="card order-card">
                    <h3>Order #<?= intval($order['order_id']) ?> — <?= htmlspecialchars($order['order_status']) ?></h3>
                     <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name'] ?? 'Unknown') ?></p>
                     <p><strong>Contact:</strong> <?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?></p>
                     <p><strong>Total:</strong> $<?= number_format($order['total_amount'], 2) ?></p>
                     <p><strong>Status:</strong> Active Now</p> ```


                    
                    
                    <div class="order-items">
                        <strong>Items:</strong>
                        <ul>
                            <?php
                                $detailStmt->execute([$order['order_id']]); 
                                $items = $detailStmt->fetchAll();
                                foreach ($items as $item):
                            ?>
                                <li>Product #<?= intval($item['Product_id']) ?> × <?= intval($item['Quantity']) ?> at $<?= number_format($item['Price'], 2) ?> each</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <form method="post" action="transporters.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="order_id" value="<?= intval($order['order_id']) ?>">
                        <label for="status-<?= intval($order['order_id']) ?>">Update Status</label>
                        <select id="status-<?= intval($order['order_id']) ?>" name="status">
                            <?php foreach (['Pending', 'In Transit', 'Delivered'] as $status): ?>
                                <option value="<?= $status ?>" <?= $status === $order['order_status'] ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-update">Save</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
<?php include 'footer.php'; ?>
</body>
</html>