<?php
session_start();
require_once 'db.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$productStmt = $pdo->query('SELECT product_id,product_name, description, price, quantity FROM products WHERE quantity > 0 ORDER BY created_at DESC');
$products = $productStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - BC Fresh Market</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 22px;
            margin: 30px 0;
        }
        .product-card {
            background: #fff;
            border: 1px solid #e1e1e1;
            border-radius: 12px;
            padding: 22px;
            text-align: center;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.04);
        }
        .product-card h3 {
            margin: 12px 0 8px;
            color: #2e7d32;
        }
        .product-price {
            margin-bottom: 14px;
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
        }
        .select-btn {
            width: 100%;
            cursor: pointer;
            border: 2px solid #2e7d32;
            background: #f4f7f5;
            color: #2e7d32;
            padding: 12px;
            font-size: 0.95rem;
            border-radius: 8px;
        }
        .select-btn.selected {
            background: #2e7d32;
            color: #ffffff;
        }
        .btn-go-to-checkout {
            display: inline-block;
            margin-top: 26px;
            padding: 14px 24px;
            background: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
        }
        .product-card input[type="number"] {
            width: 80px;
            margin-top: 12px;
            padding: 8px;
            border: 1px solid #c4c4c4;
            border-radius: 6px;
            text-align: center;
        }
        header, footer { padding: 20px 40px; }
        nav a { margin-right: 18px; }
        main { padding: 0 40px 40px; }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<main>
    <section class="catalog-container">
        <h2>Marketplace Products</h2>
        <p>Select one or more products and proceed to checkout.</p>
        <div class="products-grid">
            <?php if (empty($products)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #fff; border: 1px solid #e1e1e1; border-radius: 12px;">
                    <h3>No products available yet.</h3>
                    <p>Farmers can add fresh produce from the dashboard.</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-id="<?= intval($product['product_id']) ?>" data-name="<?= htmlspecialchars($product['product_name']) ?>" data-price="<?= number_format($product['price'], 2, '.', '') ?>">
                        <div style="font-size: 3rem;">🥬</div>
                        <h3><?= htmlspecialchars($product['product_name']) ?></h3>
                        <div class="product-price">$<?= number_format($product['price'], 2) ?></div>
                        <p><?= htmlspecialchars($product['description']) ?></p>
                        <p style="margin: 10px 0 0; color: #555;">Stock: <?= intval($product['quantity']) ?></p>
                        <button type="button" class="select-btn" onclick="toggleSelect(this)">Select Item</button>
                        <input type="number" min="1" value="1" class="quantity-input" title="Quantity">
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if (!empty($products)): ?>
            <button type="button" class="btn-go-to-checkout" onclick="goToCheckout()">Proceed to Checkout</button>
        <?php endif; ?>
    </section>
</main>
<?php include 'footer.php'; ?>
<script>
    function toggleSelect(button) {
        button.classList.toggle('selected');
        button.textContent = button.classList.contains('selected') ? '✓ Selected' : 'Select Item';
    }
    function goToCheckout() {
        const selectedItems = [];
        document.querySelectorAll('.product-card').forEach(card => {
            const button = card.querySelector('.select-btn');
            if (button.classList.contains('selected')) {
                const quantityInput = card.querySelector('.quantity-input');
                const quantity = Math.max(1, parseInt(quantityInput.value, 10) || 1);
                selectedItems.push({
                    product_id: parseInt(card.dataset.id, 10),
                    name: card.dataset.name,
                    price: parseFloat(card.dataset.price),
                    quantity: quantity
                });
            }
        });
        if (selectedItems.length === 0) {
            alert('Please select at least one item before going to checkout.');
            return;
        }
        localStorage.setItem('checkoutCart', JSON.stringify(selectedItems));
        window.location.href = 'checkout.php';
    }
</script>
</body>
</html>
