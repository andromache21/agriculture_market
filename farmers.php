<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Farmer') {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$farmerId = $_SESSION['user_id'];
$message = '';
$errors = [];

$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
        $errors[] = 'The request could not be validated. Please refresh and try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $quantity = $_POST['quantity'] ?? '';

    if ($name === '') {
        $errors[] = 'Product name is required.';
    }
    if (!is_numeric($price) || floatval($price) < 0) {
        $errors[] = 'Please enter a valid price.';
    }
    if (!ctype_digit(strval($quantity)) || intval($quantity) < 0) {
        $errors[] = 'Please enter a valid stock quantity.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO products (farmer_id, name, description, price, quantity) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $farmerId,
            $name,
            $description,
            number_format((float)$price, 2, '.', ''),
            intval($quantity)
        ]);
        $message = 'Product added successfully.';
    }
}

$productStmt = $pdo->prepare('SELECT product_id, name, description, price, quantity, created_at FROM products WHERE farmer_id = ? ORDER BY created_at DESC');
$productStmt->execute([$farmerId]);
$farmerProducts = $productStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - BC Fresh Market</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        main { padding: 20px 40px; }
        .dashboard-grid { display: grid; gap: 30px; max-width: 1100px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #e1e1e1; border-radius: 12px; padding: 24px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04); }
        .card h2 { margin-top: 0; color: #2e7d32; }
        .message { margin-bottom: 18px; padding: 16px; border-radius: 10px; }
        .success { background: #e6f4ea; color: #1f6a2e; }
        .error { background: #fdecea; color: #9b1a1a; }
        .product-form label { display: block; margin-top: 14px; font-weight: 600; }
        .product-form input, .product-form textarea { width: 100%; padding: 12px; margin-top: 6px; border: 1px solid #c4c4c4; border-radius: 8px; }
        .product-form button { margin-top: 18px; padding: 14px 24px; background: #2e7d32; color: #fff; border: none; border-radius: 10px; cursor: pointer; }
        .product-table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .product-table th, .product-table td { border: 1px solid #e1e1e1; padding: 12px; text-align: left; }
        .product-table th { background: #f4f7f5; }
        .nav-links a { margin-right: 18px; }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<main>
    <div class="dashboard-grid">
        <section class="card">
            <h2>Welcome, <?= htmlspecialchars($_SESSION['fullname']) ?>.</h2>
            <p>Use this page to add fresh produce and manage your stock pricing.</p>
        </section>

        <section class="card">
            <h2>Add a Product</h2>
            <?php if ($message): ?>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form class="product-form" method="post" action="farmers.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <label for="name">Product Name</label>
                <input id="name" name="name" type="text" required>

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"></textarea>

                <label for="price">Price (USD)</label>
                <input id="price" name="price" type="number" step="0.01" min="0" required>

                <label for="quantity">Quantity Available</label>
                <input id="quantity" name="quantity" type="number" min="0" value="1" required>

                <button type="submit">Save Product</button>
            </form>
        </section>

        <section class="card">
            <h2>Your Product Listings</h2>
            <?php if (empty($farmerProducts)): ?>
                <p>No products have been added yet. Add your first product using the form above.</p>
            <?php else: ?>
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($farmerProducts as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['description']) ?></td>
                                <td>$<?= number_format($product['price'], 2) ?></td>
                                <td><?= intval($product['quantity']) ?></td>
                                <td><?= htmlspecialchars($product['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php include 'footer.php'; ?>
</body>
</html>

