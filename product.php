<?php
session_start();
require_once __DIR__ . '/app/includes/database.php';

$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$productId) {
    echo '<h2 style="text-align:center;margin-top:3rem;">Product not found.</h2>';
    exit;
}

$db = new \Aries\Dbmodel\Includes\Database();
$pdo = $db->getConnection();
$stmt = $pdo->prepare('SELECT p.id, p.name, p.price, p.description, p.image, c.category_name FROM products p JOIN product_categories c ON p.category_id = c.id WHERE p.id = ? LIMIT 1');
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo '<h2 style="text-align:center;margin-top:3rem;">Product not found.</h2>';
    exit;
}

if (empty($_SESSION['cart_token'])) {
    $_SESSION['cart_token'] = bin2hex(random_bytes(16));
}

if (isset($_POST['add_to_cart'])) {
    $token = $_POST['cart_token'] ?? '';
    if (isset($_SESSION['cart_token']) && hash_equals($_SESSION['cart_token'], $token)) {
        if (!isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = 1;
        } else {
            $_SESSION['cart'][$productId]++;
        }
        $_SESSION['cart_token'] = bin2hex(random_bytes(16));
        header('Location: product.php?id=' . $productId . '&added=1');
        exit;
    }
}

$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> | Solitary Fish Store</title>
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
    .product-detail-container {
        max-width: 900px;
        margin: 3rem auto;
        display: flex;
        gap: 2.5rem;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(2,62,138,0.08);
        padding: 2.5rem 2rem;
        align-items: flex-start;
    }
    .product-detail-image {
        flex: 0 0 340px;
        max-width: 340px;
        background: transparent;
        border-radius: 8px;
        padding: 1.2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 340px;
    }
    .product-detail-image img {
        width: 100%;
        max-height: 320px;
        object-fit: contain;
        border-radius: 6px;
        background: #fff;
    }
    .product-detail-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
    }
    .product-detail-info h2 {
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
        font-weight: bold;
    }
    .product-detail-info .price {
        color: #00b4d8;
        font-size: 1.3rem;
        font-weight: bold;
        margin-bottom: 0.7rem;
    }
    .product-detail-info .desc {
        color: #444;
        font-size: 1.08rem;
        margin-bottom: 1.2rem;
    }
    .product-detail-info .category {
        color: #888;
        font-size: 0.98rem;
        margin-bottom: 1.2rem;
    }
    .product-detail-actions {
        display: flex;
        gap: 1.2rem;
        align-items: center;
        margin-top: 1.2rem;
    }
    .product-detail-actions button, .product-detail-actions a.order-btn {
        background: #023e8a;
        color: #fff;
        border: none;
        border-radius: 4px;
        padding: 0.7rem 2.2rem;
        font-size: 1.08rem;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
        display: inline-block;
    }
    .product-detail-actions button:hover, .product-detail-actions a.order-btn:hover {
        background: #00b4d8;
    }
    .added-msg {
        color: #388e3c;
        font-weight: bold;
        margin-bottom: 1rem;
    }
    @media (max-width: 900px) {
        .product-detail-container {
            flex-direction: column;
            gap: 1.5rem;
            padding: 1.2rem 0.5rem;
        }
        .product-detail-image {
            max-width: 100%;
            min-height: 220px;
            padding: 0.5rem;
        }
    }
    .back-btns-row {
    display: flex;
    gap: 1.5rem;
    margin-top: 2.5rem;
    justify-content: flex-start;
    flex-wrap: wrap;
}
.back-shop-btn,
.back-home-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.6em;
    background: linear-gradient(90deg,#00b4d8 0%,#48cae4 100%);
    color: #fff;
    font-weight: bold;
    font-size: 1.15rem;
    padding: 1rem 2.2rem;
    border-radius: 7px;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(2,62,138,0.10);
    transition: background 0.2s, transform 0.15s;
    border: none;
}
.back-shop-btn:hover,
.back-home-btn:hover {
    background: linear-gradient(90deg,#48cae4 0%,#00b4d8 100%);
    transform: translateY(-2px) scale(1.04);
    color: #fff;
}
@media (max-width: 600px) {
    .back-btns-row {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    .back-shop-btn,
    .back-home-btn {
        width: 100%;
        justify-content: center;
        font-size: 1rem;
        padding: 0.9rem 1.2rem;
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
                <a href="#" id="cart-link">Cart (<?php echo $cartCount; ?>)</a>
            <?php else: ?>
                <a href="register.php" id="cart-link">Cart (<?php echo $cartCount; ?>)</a>
            <?php endif; ?>
        </div>
    </div>
   <?php

?>
   <main>
        <div class="product-detail-container">
            <div class="product-detail-image">
                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
           <div class="product-detail-info">
                <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                <div class="price">₱<?php echo number_format($product['price'], 2); ?></div>
                <div class="category">Category: <?php echo htmlspecialchars($product['category_name']); ?></div>
                <div class="desc"><?php echo nl2br(htmlspecialchars($product['description'])); ?></div>
                <?php if (isset($_GET['added'])): ?>
                    <div class="added-msg">Added to cart!</div>
                <?php endif; ?>

                <div class="product-detail-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="cart_token" value="<?php echo $_SESSION['cart_token']; ?>">
                            <button type="submit" name="add_to_cart">Add to Cart</button>
                        </form>
                        <a href="payment.php?order_single=<?php echo $product['id']; ?>" class="order-btn">Order</a>
                    <?php else: ?>
                        <button type="button" class="login-popup-btn" style="background:#ccc;color:#888;cursor:pointer;">Login to Add to Cart</button>
                        <button type="button" class="login-popup-btn order-btn" style="background:#ccc;color:#888;cursor:pointer;">Login to Order</button>
                    <?php endif; ?>
                </div>

                <div class="back-btns-row">
                    <a href="shop.php" class="back-shop-btn">
                        <svg width="22" height="22" fill="currentColor" style="vertical-align:middle;" viewBox="0 0 20 20"><path d="M10.707 15.707a1 1 0 01-1.414 0l-5-5a1 1 0 010-1.414l5-5a1 1 0 111.414 1.414L7.414 9H17a1 1 0 110 2H7.414l3.293 3.293a1 1 0 010 1.414z"/></svg>
                        Back to Shop
                    </a>
                    <a href="index.php" class="back-home-btn">
                        <svg width="22" height="22" fill="currentColor" style="vertical-align:middle;" viewBox="0 0 20 20"><path d="M10 20a1 1 0 01-1-1v-7H5a1 1 0 01-1-1V8.414a1 1 0 01.293-.707l6-6a1 1 0 011.414 0l6 6A1 1 0 0118 8.414V11a1 1 0 01-1 1h-4v7a1 1 0 01-1 1z"/></svg>
                        Back to Home
                    </a>
                </div>
            </div>
    </main>

    
    <div id="login-modal" style="display:none;position:fixed;z-index:2000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);align-items:center;justify-content:center;">
        <div style="background:#fff;padding:2rem 2.5rem;border-radius:8px;max-width:350px;margin:auto;box-shadow:0 6px 32px rgba(2,62,138,0.18);position:relative;">
            <span id="close-login-modal" style="position:absolute;top:10px;right:16px;font-size:1.5rem;cursor:pointer;">&times;</span>
            <h3 style="margin-top:0;">Login Required</h3>
            <p style="color:#444;">You must be logged in to add items to your cart or order.</p>
            <a href="login.php" style="display:inline-block;margin-top:1rem;background:#023e8a;color:#fff;padding:0.6rem 1.5rem;border-radius:5px;text-decoration:none;font-weight:bold;">Go to Login</a>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.login-popup-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('login-modal').style.display = 'flex';
            });
        });
        document.getElementById('close-login-modal').addEventListener('click', function() {
            document.getElementById('login-modal').style.display = 'none';
        });
        window.addEventListener('click', function(e) {
            var modal = document.getElementById('login-modal');
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>