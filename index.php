<?php

require_once 'vendor/autoload.php';

use Aries\Dbmodel\Models\User;

$user = new User();

session_start();
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (empty($_SESSION['cart_token'])) {
    $_SESSION['cart_token'] = bin2hex(random_bytes(16));
}
if (isset($_POST['add_to_cart'])) {
    $productId = $_POST['product_id'];
    $token = $_POST['cart_token'] ?? '';
    if (isset($_SESSION['cart_token']) && hash_equals($_SESSION['cart_token'], $token)) {
        if (!isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = 1;
        } else {
            $_SESSION['cart'][$productId]++;
        }
        $_SESSION['cart_token'] = bin2hex(random_bytes(16));
        header('Location: index.php');
        exit;
    }
}
if (isset($_POST['cart_add'])) {
    $pid = $_POST['cart_add'];
    if (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid]++;
    }
}
if (isset($_POST['cart_minus'])) {
    $pid = $_POST['cart_minus'];
    if (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid]--;
        if ($_SESSION['cart'][$pid] <= 0) {
            unset($_SESSION['cart'][$pid]);
        }
    }
}
if (isset($_POST['checkout'])) {
    $_SESSION['cart'] = [];
    header('Location: index.php?checkout=success');
    exit;
}
$cartCount = array_sum($_SESSION['cart']);

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    if (empty($_SESSION['username']) || empty($_SESSION['role'])) {
        require_once __DIR__ . '/app/includes/database.php';
        $db = new \Aries\Dbmodel\Includes\Database();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare('SELECT name, role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $_SESSION['username'] = $row['name'] ?? 'User';
            $_SESSION['role'] = $row['role'] ?? 'user';
        } else {
            $_SESSION['username'] = 'User';
            $_SESSION['role'] = 'user';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaSide Fish Store</title>
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
        }
        .nav-links a:hover, .nav-link-btn:hover {
            color: #00b4d8;
        }
        .banner-section {
            position: relative;
            height: 48vh;
            min-height: 340px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            background: #48cae4;
            overflow: hidden;
            border-bottom-left-radius: 60px 30px;
            border-bottom-right-radius: 60px 30px;
            box-shadow: 0 8px 32px rgba(2,62,138,0.10);
        }
        .banner-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg,rgba(2,62,138,0.7) 60%,rgba(2,62,138,0.2) 100%);
            z-index: 1;
        }
        .banner-text {
            position: relative;
            z-index: 2;
            color: #fff;
            text-align: left;
            max-width: 600px;
            margin-left: 3vw;
        }
        .banner-text h1 {
            font-size: 3.2rem;
            margin: 0 0 0.5rem 0;
            letter-spacing: 2px;
            color: #caf0f8;
            text-shadow: 1px 2px 8px #0077b6;
        }
        .banner-text p {
            font-size: 1.3rem;
            margin: 0;
            color: #e0f7fa;
        }
        .banner-dots {
            position: absolute;
            bottom: 24px;
            left: 3vw;
            z-index: 3;
        }
        .dot {
            display: inline-block;
            width: 13px;
            height: 13px;
            margin: 0 4px;
            border-radius: 50%;
            background: #fff;
            opacity: 0.5;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .dot.active {
            opacity: 1;
            background: #00b4d8;
        }
        .collection-featured-image {
            display: inline-block;
            width: 32%;
            margin: 1.5% 0.5%;
            vertical-align: top;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(2,62,138,0.10);
            background: #fff;
        }
        .collection-featured-image img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            display: block;
        }
        .featured-products {
            max-width: 1200px;
            margin: 3rem auto 2rem auto;
            padding: 0 2vw;
        }
        .featured-products h2 {
            font-size: 2.2rem;
            margin-bottom: 1.5rem;
            color: #023e8a;
            letter-spacing: 1px;
        }
        .products {
            display: flex;
    gap: 2.5vw;
    flex-wrap: wrap;
    justify-content: center;
        }
        .product {
            background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(2,62,138,0.08);
    padding: 1.5rem 1.2rem 1.2rem 1.2rem;
    width: 300px;
    text-align: center;
    transition: transform 0.18s;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 2rem;
        }
        .product:hover {
            transform: translateY(-7px) scale(1.03);
            box-shadow: 0 6px 24px rgba(0,180,216,0.10);
        }
        .product img {
           width: 100%;
    height: 180px;
    object-fit: cover;
    margin-bottom: 1rem;
    border-radius: 10px;
    background: #e0f7fa;
    box-shadow: 0 2px 8px rgba(2,62,138,0.07);
        }
        .product h3 {
            font-size: 1.15rem;
            margin: 0.5rem 0 0.2rem 0;
            color: #184e77;
            font-weight: 600;
        }
        .price {
            color: #00b4d8;
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 0.7rem;
        }
        .view-btn {
            background: #023e8a;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 0.6em 1.2em;
            font-size: 1em;
            font-weight: 500;
            text-decoration: none;
            margin-top: auto;
            transition: background 0.2s;
        }
        .view-btn:hover {
            background: #00b4d8;
        }
        .site-footer {
            background: #023e8a;
            color: #fff;
            padding: 2.5rem 0 1.2rem 0;
            margin-top: 3rem;
            border-top-left-radius: 40px 20px;
            border-top-right-radius: 40px 20px;
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
        }
        .footer-brand {
            font-size: 1.7rem;
            font-weight: bold;
            letter-spacing: 2px;
            color: #90e0ef;
        }
        .footer-links a {
            color: #fff;
            margin-right: 1.5rem;
            text-decoration: none;
            font-size: 1.05em;
            transition: color 0.2s;
        }
        .footer-links a:hover {
            color: #00b4d8;
        }
        .footer-social {
            display: flex;
            gap: 1.2rem;
        }
        .footer-copy {
            width: 100%;
            text-align: center;
            margin-top: 1.5rem;
            color: #caf0f8;
            font-size: 0.97em;
        }
      
        .cart-modal {
            position: fixed;
            z-index: 1000;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.4);
            display: none;
            pointer-events: none;
        }
        .cart-modal.open {
            display: block;
            pointer-events: auto;
        }
        .cart-modal-content {
            background: #fff; padding: 2rem; border-radius: 8px; min-width: 320px; position: fixed;
            top: 0; right: 0; height: 100vh; max-width: 90vw;
            box-shadow: -2px 0 16px rgba(2,62,138,0.15);
            transform: translateX(100%);
            transition: transform 0.45s cubic-bezier(.4,0,.2,1);
            pointer-events: auto;
            overflow-y: auto;
            will-change: transform;
        }
        .cart-modal.open .cart-modal-content {
            transform: translateX(0);
        }
        .cart-modal .close {
            position: absolute; top: 10px; right: 16px; font-size: 1.5rem; cursor: pointer;
        }
        @media (max-width: 900px) {
            .products { flex-direction: column; align-items: center; }
            .collection-featured-image { width: 98%; margin: 1.5% 0; }
            .footer-content { flex-direction: column; gap: 1.2rem; }
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
    <div id="cart-modal" class="cart-modal">
        <div class="cart-modal-content">
            <span class="close" id="close-cart">&times;</span>
            <h2>Your Cart</h2>
            <?php if (!empty($_SESSION['cart'])): ?>
                <form method="post" id="cart-update-form">
                    <ul style="list-style:none;padding:0;">
                    <?php
                    $total = 0;
                    require_once __DIR__ . '/app/includes/database.php';
                    $db = new \Aries\Dbmodel\Includes\Database();
                    $pdo = $db->getConnection();
                    $productIds = array_keys($_SESSION['cart']);
                    $productsMap = [];
                    if ($productIds) {
                        $in = str_repeat('?,', count($productIds) - 1) . '?';
                        $stmt = $pdo->prepare("SELECT id, name, price, image FROM products WHERE id IN ($in)");
                        $stmt->execute($productIds);
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $productsMap[$row['id']] = $row;
                        }
                    }
                    foreach ($_SESSION['cart'] as $productId => $qty):
                        $product = isset($productsMap[$productId]) ? $productsMap[$productId] : null;
                        if (!$product) continue;
                        $subtotal = $product['price'] * $qty;
                        $total += $subtotal;
                    ?>
                        <li style="display:flex;align-items:center;gap:1rem;margin-bottom:1.2rem;">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width:60px;height:60px;object-fit:contain;border-radius:0;background:transparent;border:none;">
                            <div style="flex:1;">
                                <div style="font-weight:bold;"> <?php echo htmlspecialchars($product['name']); ?> </div>
                                <div style="color:#555;">₱<?php echo number_format($product['price'],2); ?></div>
                            </div>
                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                <button type="submit" name="cart_minus" value="<?php echo $productId; ?>" style="width:28px;height:28px;font-size:1.2em;">-</button>
                                <span style="min-width:24px;display:inline-block;text-align:center;"> <?php echo $qty; ?> </span>
                                <button type="submit" name="cart_add" value="<?php echo $productId; ?>" style="width:28px;height:28px;font-size:1.2em;">+</button>
                            </div>
                            <div style="width:80px;text-align:right;">₱<?php echo number_format($subtotal,2); ?></div>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                    <div style="text-align:right;font-weight:bold;font-size:1.1em;margin-bottom:1.2rem;">Total: ₱<?php echo number_format($total,2); ?></div>
                    <button type="submit" name="checkout" style="width:100%;background:#023e8a;color:#fff;padding:0.7rem 0;font-size:1.1em;border-radius:4px;border:none;">Checkout</button>
                </form>
            <?php else: ?>
                <p>Your cart is empty.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="banner-section" id="banner-section">
        <div class="banner-overlay"></div>
        <div class="banner-text">
            <h1>Aquaside Fish Store</h1>
            <p>Dive into the freshest selection of aquarium fish and aquatic plants!</p>
        </div>
        <div class="banner-dots" id="banner-dots"></div>
    </div>
    <div class="collection-featured-image">
        <img src="collection-images/1 (2).jpg" alt="Freshwater Collection">
    </div>
    <div class="collection-featured-image">
        <img src="collection-images/1 (1).jpg" alt="Saltwater Collection">
    </div>
    <div class="collection-featured-image">
        <img src="collection-images/1 (3).jpg" alt="Aquatic Plants">
    </div>
    <section class="featured-products">
        <h2>New Arrivals</h2>
    <div class="products">
        <div class="product">
            <img src="new-arrivals-images/4.jpg" alt="Betta Fish">
            <h3>Betta Fish (Male)</h3>
            <span class="price">₱150</span>
            <a href="product.php?id=3" class="view-btn">View Details</a>
        </div>
        <div class="product">
            <img src="new-arrivals-images/2.jpg" alt="Discus Fish">
            <h3>Discus Fish</h3>
            <span class="price">₱250</span>
            <a href="product.php?id=2" class="view-btn">View Details</a>
        </div>
        <div class="product">
            <img src="new-arrivals-images/1.jpg" alt="Aquatic Plant">
            <h3>Java Fern (Aquatic Plant)</h3>
            <span class="price">₱100</span>
            <a href="product.php?id=1" class="view-btn">View Details</a>
        </div>
    </div>
    </section>
    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-brand">AquaSide Fish Store</div>
            <div class="footer-links">
                <a href="#">About</a>
                <a href="#">Contact</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms</a>
            </div>
            <div class="footer-social">
                
                <a href="#" class="social-icon" aria-label="Facebook" title="Facebook">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.595 0 0 .592 0 1.326v21.348C0 23.406.595 24 1.325 24h11.495v-9.294H9.692v-3.622h3.128V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.797.143v3.24l-1.918.001c-1.504 0-1.797.715-1.797 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116C23.406 24 24 23.406 24 22.674V1.326C24 .592 23.406 0 22.675 0"/></svg>
                </a>
                <a href="#" class="social-icon" aria-label="Instagram" title="Instagram">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.334 3.608 1.308.974.974 1.246 2.241 1.308 3.608.058 1.266.069 1.646.069 4.85s-.012 3.584-.07 4.85c-.062 1.366-.334 2.633-1.308 3.608-.974.974-2.241 1.246-3.608 1.308-1.266.058-1.646.069-4.85.069s-3.584-.012-4.85-.07c-1.281-.058-2.393-.265-3.373-1.245-.98-.98-1.187-2.092-1.245-3.373C2.012 5.668 2 6.077 2 12c0 5.923.012 6.332.07 7.612.058 1.281.265 2.393 1.245 3.373.98.98 2.092 1.187 3.373 1.245C8.332 23.988 8.741 24 12 24s3.668-.012 4.948-.07c1.281-.058 2.393-.265 3.373-1.245.98-.98 1.187-2.092 1.245-3.373.058-1.28.07-1.689.07-7.612 0-5.923-.012-6.332-.07-7.612-.058-1.281-.265-2.393-1.245-3.373-.98-.98-2.092-1.187-3.373-1.245C15.668.012 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zm0 10.162a3.999 3.999 0 1 1 0-7.998 3.999 3.999 0 0 1 0 7.998zm7.2-11.162a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88z"/></svg>
                </a>
                <a href="#" class="social-icon" aria-label="X" title="X (Twitter)">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M17.53 2.477h3.934l-8.59 9.86 10.13 12.186h-7.97l-6.24-7.51-7.14 7.51H.52l9.17-10.53L0 2.477h8.13l5.7 6.86zm-1.13 17.01h2.18L6.47 4.36H4.17z"/></svg>
                </a>
                <a href="#" class="social-icon" aria-label="TikTok" title="TikTok">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12.75 2.001a1 1 0 0 1 1 1v13.25a2.25 2.25 0 1 1-2.25-2.25h.25a1 1 0 1 1 0 2h-.25a.25.25 0 1 0 .25.25V3.001a1 1 0 0 1 1-1zm6.5 0a1 1 0 0 1 1 1v2.25a5.25 5.25 0 0 1-5.25 5.25h-1.25v-2h1.25a3.25 3.25 0 0 0 3.25-3.25V3.001a1 1 0 0 1 1-1z"/></svg>
                </a>
            </div>
            <div class="footer-copy">©2025, AquaSide Fish Store, Designed by Nico Deiparine</div>
        </div>
    </footer>
    <script>
   
    document.addEventListener('DOMContentLoaded', function() {
        var cartLink = document.getElementById('cart-link');
        var cartModal = document.getElementById('cart-modal');
        var closeCart = document.getElementById('close-cart');
        if(cartLink && cartModal && closeCart) {
            cartLink.addEventListener('click', function(e) {
                e.preventDefault();
                cartModal.classList.add('open');
            });
            closeCart.addEventListener('click', function() {
                cartModal.classList.remove('open');
            });
            window.addEventListener('click', function(e) {
                if (e.target === cartModal) {
                    cartModal.classList.remove('open');
                }
            });
        }
        if (cartModal) {
            document.querySelector('.cart-modal-content').addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        var cartForm = document.getElementById('cart-update-form');
        if (cartForm) {
            cartForm.addEventListener('click', function(e) {
                if (e.target.name === 'cart_add' || e.target.name === 'cart_minus') {
                    e.preventDefault();
                    e.stopPropagation();
                    const formData = new FormData(cartForm);
                    formData.append(e.target.name, e.target.value);
                    fetch('index.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newCart = doc.querySelector('.cart-modal-content');
                        if (newCart) {
                            document.querySelector('.cart-modal-content').innerHTML = newCart.innerHTML;
                        }
                    });
                }
            });
        }
    });

    
    const images = [
        'banner-images/1 (1).jpg',
        'banner-images/1 (2).jpg',
        'banner-images/1 (3).jpg',
        'banner-images/1 (4).jpg',
        'banner-images/1 (5).jpg'
    ];
    let current = 0;
    const bannerSection = document.getElementById('banner-section');
    const bannerDots = document.getElementById('banner-dots');
    let intervalId;
    function setBannerBg(idx) {
        bannerSection.style.backgroundImage = `url('${images[idx]}')`;
        bannerSection.style.backgroundSize = 'cover';
        bannerSection.style.backgroundPosition = 'center';
        bannerSection.style.transition = 'background-image 0.7s ease-in-out';
        updateDots(idx);
    }
    function showAt(idx) {
        current = idx;
        setBannerBg(current);
        resetInterval();
    }
    function updateDots(activeIdx) {
        bannerDots.innerHTML = images.map((_, i) =>
            `<span class="dot${i === activeIdx ? ' active' : ''}" data-idx="${i}"></span>`
        ).join('');
        document.querySelectorAll('.dot').forEach(dot => {
            dot.onclick = () => showAt(Number(dot.dataset.idx));
        });
    }
    function showNext() {
        current = (current + 1) % images.length;
        setBannerBg(current);
    }
    function resetInterval() {
        clearInterval(intervalId);
        intervalId = setInterval(showNext, 3500);
    }
    setBannerBg(current);
    intervalId = setInterval(showNext, 3500);
    </script>
</body>
</html>