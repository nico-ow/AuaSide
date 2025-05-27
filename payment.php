<?php
session_start();

if (isset($_GET['order_single'])) {
    $singleId = intval($_GET['order_single']);
    if ($singleId > 0) {
        $_SESSION['cart'] = [$singleId => 1];
    }
    header('Location: payment.php');
    exit;
}

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: shop.php');
    exit;
}

require_once __DIR__ . '/app/includes/database.php';
$db = new \Aries\Dbmodel\Includes\Database();
$pdo = $db->getConnection();
$productIds = array_keys($_SESSION['cart']);
$productsMap = [];
$total = 0;
if ($productIds) {
    $in = str_repeat('?,', count($productIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id, name, price, image FROM products WHERE id IN ($in)");
    $stmt->execute($productIds);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $productsMap[$row['id']] = $row;
    }
    foreach ($_SESSION['cart'] as $productId => $qty) {
        $product = $productsMap[$productId] ?? null;
        if ($product) {
            $total += $product['price'] * $qty;
        }
    }
}

$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['customer_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $payment_mode = $_POST['payment_mode'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';
    if ($address === '') $errors[] = 'Address is required.';
    if ($contact === '') $errors[] = 'Contact number is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (!in_array($payment_mode, ['COD', 'GCash', 'Credit Card'])) $errors[] = 'Select a valid payment mode.';

    if (!$errors) {
        $orderDetails = [];
        foreach ($_SESSION['cart'] as $productId => $qty) {
            $product = $productsMap[$productId] ?? null;
            if ($product) {
                $orderDetails[] = [
                    'id' => $productId,
                    'name' => $product['name'],
                    'qty' => $qty,
                    'price' => $product['price']
                ];
            }
        }
        $orderDetailsJson = json_encode($orderDetails);
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("INSERT INTO payment (user_id, customer_name, address, contact, email, payment_mode, order_details, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $name,
            $address,
            $contact,
            $email,
            $payment_mode,
            $orderDetailsJson,
            $total
        ]);
        $success = true;
        $_SESSION['cart'] = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | AquaSide Fish Store</title>
    <link rel="stylesheet" href="style.css">
    <style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%);
        margin: 0;
        color: #184e77;
    }
    .navbar {
        background: #023e8a;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.2rem 2.5rem;
        box-shadow: 0 2px 8px rgba(2,62,138,0.08);
    }
    .brand {
        font-size: 2rem;
        font-weight: bold;
        letter-spacing: 2px;
        color: #90e0ef;
        text-shadow: 1px 2px 6px #0077b6;
    }
    .nav-links {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    .nav-links a, .nav-link-btn {
        color: #caf0f8;
        text-decoration: none;
        font-weight: 500;
        font-size: 1.1em;
        transition: color 0.2s;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0 0.7em;
        vertical-align: middle;
        display: inline-block;
    }
    .nav-links a:hover, .nav-link-btn:hover {
        color: #00b4d8;
    }
    .payment-form {
        max-width: 440px;
        margin: 2.5rem auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(2,62,138,0.10);
        padding: 2.5rem 2rem 2rem 2rem;
    }
    .payment-form h2 {
        margin-bottom: 1.5rem;
        text-align: center;
        color: #023e8a;
        letter-spacing: 1px;
    }
    .payment-form label {
        font-weight: 500;
        margin-bottom: 0.3rem;
        display: block;
        color: #184e77;
    }
    .payment-form input, .payment-form textarea, .payment-form select {
    width: 100%;
    padding: 0.7rem;
    margin-bottom: 1.1rem;
    border: 1.5px solid #b2ebf2;
    border-radius: 7px;
    font-size: 1em;
    background: #e0f7fa;
    color: #184e77;
    transition: border 0.2s;
    }
    .payment-form textarea {
    font-size: 1.1em;
    }
    .payment-form input:focus, .payment-form textarea:focus, .payment-form select:focus {
        border: 1.5px solid #00b4d8;
        outline: none;
        background: #fff;
    }
    .payment-form button {
        width: 100%;
        background: linear-gradient(90deg, #00b4d8 0%, #48cae4 100%);
        color: #fff;
        padding: 0.8rem 0;
        font-size: 1.1em;
        border-radius: 7px;
        border: none;
        font-weight: bold;
        letter-spacing: 1px;
        cursor: pointer;
        margin-top: 0.5rem;
        box-shadow: 0 2px 8px rgba(2,62,138,0.08);
        transition: background 0.2s;
    }
    .payment-form button:hover {
        background: linear-gradient(90deg, #023e8a 0%, #00b4d8 100%);
    }
    .payment-form .total {
        text-align: right;
        font-weight: bold;
        font-size: 1.1em;
        margin-bottom: 1.2rem;
    }
    .payment-form .error {
        color: #e53935;
        background: #fffbe7;
        border-radius: 7px;
        padding: 0.6rem 1rem;
        margin-bottom: 1rem;
        text-align: center;
        font-size: 1rem;
        border: 1px solid #ffe082;
    }
    .payment-form .success {
        color: #388e3c;
        margin-bottom: 1rem;
        font-size: 1.05em;
        text-align: center;
    }
    .order-summary {
        background: #f7f7f7;
        border-radius: 6px;
        padding: 1.2em;
        margin-bottom: 1.5em;
    }
    .order-summary h3 {
        margin-top: 0;
        color: #023e8a;
        font-size: 1.15em;
    }
    .order-summary ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .order-summary li {
        display: flex;
        align-items: center;
        gap: 1em;
        margin-bottom: 0.7em;
    }
    .order-summary img {
        width: 48px;
        height: 48px;
        object-fit: contain;
        background: transparent;
    }
    @media (max-width: 600px) {
        .payment-form {
            max-width: 98vw;
            margin: 1rem;
            border-radius: 10px;
            padding: 1.2rem 0.5rem 1.2rem 0.5rem;
        }
    }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php" class="brand" style="text-decoration: none;">AquaSide Fish Store</a>
        <div class="nav-links">
            <a href="shop.php">Shop</a>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="nav-link-btn" style="cursor:default;"> <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?> </span>
                <form method="post" style="display:inline;margin:0;padding:0;">
                    <button type="submit" name="logout" class="nav-link-btn">Logout</button>
                </form>
                <a href="shop.php#cart-link" class="nav-link-btn">Cart</a>
            <?php else: ?>
                <a href="register.php" class="nav-link-btn">Cart</a>
            <?php endif; ?>
        </div>
    </div>
     <div class="payment-form">
        <h2>Payment Details</h2>
        <a href="shop.php" style="display:inline-block;margin-bottom:1.2rem;color:#00b4d8;text-decoration:none;font-weight:bold;">
            ← Back to Shop
        </a>
        <?php if ($success): ?>
            <div class="success">Thank you for your order!<br>Your payment details have been received.</div>
            <div style="text-align:center;margin-top:1.5rem;">
                <a href="shop.php" style="color:#18181b;text-decoration:underline;">Continue Shopping</a>
            </div>
        <?php else: ?>
            <?php if ($errors): ?>
                <div class="error">
                    <?php foreach ($errors as $err) echo htmlspecialchars($err) . '<br>'; ?>
                </div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <label for="customer_name">Full Name</label>
                <input type="text" id="customer_name" name="customer_name" required value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>">

                <label for="address">Address</label>
                <textarea id="address" name="address" rows="2" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>

                <label for="contact">Contact Number</label>
                <input type="text" id="contact" name="contact" required value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>">

                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

                <label for="payment_mode">Mode of Payment</label>
                <select id="payment_mode" name="payment_mode" required>
                    <option value="">Select...</option>
                    <option value="COD" <?php if(($_POST['payment_mode'] ?? '')==='COD') echo 'selected'; ?>>Cash on Delivery</option>
                    <option value="GCash" <?php if(($_POST['payment_mode'] ?? '')==='GCash') echo 'selected'; ?>>GCash</option>
                    <option value="Credit Card" <?php if(($_POST['payment_mode'] ?? '')==='Credit Card') echo 'selected'; ?>>Credit Card</option>
                </select>
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <ul>
                        <?php $total = 0; foreach ($_SESSION['cart'] as $productId => $qty):
                            $product = $productsMap[$productId] ?? null;
                            if (!$product) continue;
                            $subtotal = $product['price'] * $qty;
                            $total += $subtotal;
                        ?>
                        <li>
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php endif; ?>
                            <span style="flex:1;"><?php echo htmlspecialchars($product['name']); ?></span>
                            <span style="min-width:32px; text-align:center;">x<?php echo $qty; ?></span>
                            <span style="min-width:70px; text-align:right;">₱<?php echo number_format($subtotal,2); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="total">Total: ₱<?php echo number_format($total,2); ?></div>
                </div>
                <button type="submit">Submit Payment</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>