<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['customer']);
require_once __DIR__ . '/../includes/db.php';

$cartItems = get_cart_items();
$subtotal = get_cart_total();
if (empty($cartItems)) {
    redirect('/customer/cart.php');
}
$deliveryAddress = $_SESSION['delivery_address'] ?? 'Bole, Hawassa, Building 12, Apt 5A';
$cartCount = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout · Gebeta</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <h1>Checkout</h1>
        <a class="pill-button" href="/customer/cart.php">Back to cart</a>
    </header>
    <main class="page-content">
        <section class="checkout-card">
            <h2>Delivery address</h2>
            <textarea id="delivery_address" rows="3"><?= htmlspecialchars($deliveryAddress) ?></textarea>
        </section>

        <section class="checkout-card">
            <h2>Payment method</h2>
            <label class="radio-card"><input type="radio" name="payment" value="cash" checked> 💵 Cash on delivery</label>
            <label class="radio-card"><input type="radio" name="payment" value="bank_transfer"> 🏦 Bank transfer</label>
            <label class="radio-card"><input type="radio" name="payment" value="telebirr"> 📱 Telebirr</label>
            <label class="radio-card"><input type="radio" name="payment" value="mpesa"> 📱 M-Pesa</label>
        </section>

        <section class="checkout-summary">
            <div><span>Total</span><span><?= format_price($subtotal) ?></span></div>
            <button id="place-order" class="primary-btn">Place order</button>
        </section>
    </main>
    <footer class="bottom-bar">
        <a href="/customer/dashboard.php">🏠 Home</a>
        <a href="/customer/cart.php">🛒 Cart (<?= $cartCount ?>)</a>
        <a href="/customer/orders.php">📄 Orders</a>
        <a href="/customer/profile.php">👤 Profile</a>
    </footer>
    <script>
        document.getElementById('place-order').addEventListener('click', function () {
            const address = document.getElementById('delivery_address').value.trim();
            const payment = document.querySelector('input[name="payment"]:checked').value;
            if (!address) {
                alert('Please enter delivery address.');
                return;
            }
            fetch('/api/place-order.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'delivery_address=' + encodeURIComponent(address) + '&payment_method=' + encodeURIComponent(payment)
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Unable to place order');
                }
            });
        });
    </script>
</body>
</html>
