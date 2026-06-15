<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Customer') {
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - BC Fresh Market</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .checkout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin: 30px auto;
            max-width: 1100px;
        }
        .checkout-card {
            background: #fff;
            border: 1px solid #e1e1e1;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 3px 18px rgba(0,0,0,0.05);
        }
        .checkout-card h2,
        .checkout-card h3 {
            margin-bottom: 18px;
            color: #2e7d32;
        }
        .checkout-card label {
            display: block;
            margin-top: 14px;
            font-weight: 600;
        }
        .checkout-card input,
        .checkout-card select {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #c4c4c4;
            border-radius: 8px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .summary-row.total {
            font-weight: 700;
            margin-top: 12px;
        }
        .summary-empty {
            color: #a00;
            margin-top: 14px;
        }
        .message-box {
            margin-top: 14px;
            padding: 14px;
            border-radius: 8px;
        }
        .message-success { background: #e6f4ea; color: #1c662f; }
        .message-error { background: #fbe9e9; color: #a11; }
        .btn-submit {
            width: 100%;
            margin-top: 22px;
            padding: 14px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<main>
    <div class="checkout-grid">
        <section class="checkout-card">
            <h2>Complete Your Order</h2>
            <div id="checkout-message"></div>
            <form id="checkout-form" action="place_order.php" method="post">
                <label for="full_name">Full Name</label>
                <input id="full_name" name="full_name" type="text" required>
                <label for="delivery_address">Delivery Address</label>
                <input id="delivery_address" name="delivery_address" type="text" required>
                <label for="phone">Phone Number</label>
                <input id="phone" name="phone" type="tel" required>
                <label for="payment_method">Payment Method</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="EcoCash">EcoCash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Cash On Delivery">Cash On Delivery</option>
                </select>
                <input type="hidden" id="cart_json" name="cart_json">
                <input type="hidden" id="total_amount" name="total_amount">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="btn-submit">Place Order</button>
            </form>
        </section>
        <aside class="checkout-card">
            <h3>Order Summary</h3>
            <div id="checkout-items"></div>
            <div class="summary-row total">
                <span>Total</span>
                <span id="total-display">$0.00</span>
            </div>
        </aside>
    </div>
</main>
<?php include 'footer.php'; ?>
<script>
    const cartKey = 'checkoutCart';
    const rawCart = localStorage.getItem(cartKey);
    const cartItems = rawCart ? JSON.parse(rawCart) : [];
    const checkoutItems = document.getElementById('checkout-items');
    const totalDisplay = document.getElementById('total-display');
    const cartJsonInput = document.getElementById('cart_json');
    const totalAmountInput = document.getElementById('total_amount');
    const messageBox = document.getElementById('checkout-message');
    const checkoutForm = document.getElementById('checkout-form');
    function getQueryValue(key) {
        return new URLSearchParams(window.location.search).get(key);
    }
    function showMessage(text, isSuccess = false) {
        if (!text) return;
        messageBox.innerHTML = `<div class="message-box ${isSuccess ? 'message-success' : 'message-error'}">${text}</div>`;
    }
    function renderOrderSummary() {
        if (cartItems.length === 0) {
            checkoutItems.innerHTML = '<div class="summary-empty">No items in your cart. Please select products on the products page.</div>';
            checkoutForm.querySelector('button[type="submit"]').disabled = true;
            return;
        }
        let total = 0;
        checkoutItems.innerHTML = '';
        cartItems.forEach(item => {
            const row = document.createElement('div');
            row.className = 'summary-row';
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            row.innerHTML = `
                <span>${item.name} × ${item.quantity}</span>
                <strong>$${itemTotal.toFixed(2)}</strong>
            `;
            checkoutItems.appendChild(row);
        });
        totalDisplay.textContent = `$${total.toFixed(2)}`;
        cartJsonInput.value = JSON.stringify(cartItems);
        totalAmountInput.value = total.toFixed(2);
    }
    window.addEventListener('DOMContentLoaded', () => {
        renderOrderSummary();
        if (getQueryValue('success') === '1') {
            showMessage('Order placed successfully! Your payment is pending confirmation.', true);
            localStorage.removeItem(cartKey);
        }
        if (getQueryValue('error')) {
            const error = getQueryValue('error');
            const messages = {
                invalid: 'Your order could not be submitted. Please verify your information.',
                empty_cart: 'No items were found in your cart.',
                failed: 'Something went wrong while saving your order. Please try again.',
                customer_required: 'Only logged-in customers can place orders. Please log in first.',
            };
            showMessage(messages[error] || 'Unable to place order. Please try again.');
        }
    });
    checkoutForm.addEventListener('submit', (event) => {
        if (cartItems.length === 0) {
            event.preventDefault();
            showMessage('Please select products before placing an order.');
        }
    });
</script>
</body>
</html>
