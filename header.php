<?php
$loggedIn = !empty($_SESSION['user_id']);
$role = $_SESSION['role'] ?? '';
$fullname = htmlspecialchars($_SESSION['fullname'] ?? '');
?>
<header>
    <h1>BC Fresh Market</h1>
    <nav>
        <a href="index.html">Home</a>
        <a href="products.php">Products</a>
        <?php if ($loggedIn): ?>
            <?php if ($role === 'Farmer'): ?>
                <a href="farmers.php">Farmer Dashboard</a>
            <?php endif; ?>
            <?php if ($role === 'Transporter'): ?>
                <a href="transporters.php">Transporters</a>
            <?php endif; ?>
            <a href="cart.html">Cart</a>
            <a href="checkout.php">Checkout</a>
            <a href="logout.php">Logout</a>
            <span style="margin-left: 18px; font-weight: 600;">Hello, <?= $fullname ?: 'Member' ?></span>
        <?php else: ?>
            <a href="register.php">Register</a>
            <a href="login.php">Login</a>
            <a href="cart.html">Cart</a>
            <a href="checkout.php">Checkout</a>
            <a href="transporters.php">Transporters</a>
            <a href="contact.html">Contact Us</a>
        <?php endif; ?>
    </nav>
</header>

