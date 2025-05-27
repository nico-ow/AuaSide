<?php
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
        header('Location: shop.php');
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
    header('Location: shop.php?checkout=success');
    exit;
}
$cartCount = array_sum($_SESSION['cart']);
$products = [];
$categories = ['Freshwater', 'Saltwater', 'Plants', 'Accessories'];
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';

require_once __DIR__ . '/app/includes/database.php';
$db = new \Aries\Dbmodel\Includes\Database();
$pdo = $db->getConnection();
$sql = "SELECT p.id, p.name, p.price, p.description, p.image, c.category_name FROM products p JOIN product_categories c ON p.category_id = c.id ORDER BY p.created_at DESC";
$stmt = $pdo->query($sql);
$products = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $products[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'price' => $row['price'],
        'description' => $row['description'],
        'img' => $row['image'],
        'category' => $row['category_name']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop | AquaSide Fish Store</title>
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
    .shop-layout {
        display: flex;
        align-items: stretch;
        gap: 2rem;
        max-width: 1200px;
        margin: 2rem auto;
    }
    .category-sidebar {
        min-width: 180px;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }
    .category-sidebar form {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(2,62,138,0.04);
        padding: 1.2rem;
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
        height: 100%;
    }
    .category-sidebar label {
        font-weight: bold;
        color: #222;
    }
    .category-sidebar select {
        width: 100%;
        padding: 0.4rem;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    .shop-products {
        display: flex;
    gap: 2.5vw;
    flex-wrap: wrap;
    justify-content: center;
    align-items: stretch;
    }
    .shop-product {
        background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(2,62,138,0.08);
    padding: 1.5rem 1.2rem 1.2rem 1.2rem;
    width: 270px;
    text-align: center;
    transition: transform 0.18s;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 2rem;
    }
    .shop-product:hover {
        box-shadow: 0 6px 24px rgba(0,180,216,0.10);
        transform: translateY(-7px) scale(1.03);
    }
    .shop-product img {
         width: 100%;
    height: 180px;
    object-fit: cover;
    margin-bottom: 1rem;
    border-radius: 10px;
    background: #e0f7fa;
    box-shadow: 0 2px 8px rgba(2,62,138,0.07);
    }
    .shop-product h3 {
        margin: 0.5rem 0;
        font-size: 1.2rem;
        color: #184e77;
        font-weight: 600;
    }
    .shop-product .price {
        color: #00b4d8;
        font-weight: bold;
        font-size: 1.1rem;
        margin-bottom: 0.7rem;
    }
    .shop-product form {
        margin-top: 1rem;
        width: 100%;
    }
    .shop-product button {
        background: #023e8a;
        color: #fff;
        border: none;
        padding: 0.5rem 1.2rem;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.2s;
        font-size: 1em;
        font-weight: 500;
        width: 100%;
        margin-bottom: 0.4rem;
    }
    .shop-product button:hover {
        background: #00b4d8;
    }
    .view-btn {
        display: inline-block;
        background: #f3f3f3;
        color: #222;
        border-radius: 4px;
        padding: 0.4rem 1.2rem;
        text-decoration: none;
        font-size: 0.97em;
        transition: background 0.2s, color 0.2s;
        margin-bottom: 0.2rem;
    }
    .view-btn:hover {
        background: #18181b;
        color: #fff;
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
        .shop-layout { flex-direction: column; gap: 1.2rem; }
        .category-sidebar { min-width: 100%; }
        .shop-products { justify-content: center; }
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
                    fetch('shop.php', {
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

        document.querySelectorAll('.add-to-cart-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const formData = new FormData(form);
                formData.append('add_to_cart', '1');
                fetch('shop.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newCartLink = doc.getElementById('cart-link');
                    if (newCartLink) {
                        document.getElementById('cart-link').innerHTML = newCartLink.innerHTML;
                    }
                    const newCartModalContent = doc.querySelector('.cart-modal-content');
                    if (newCartModalContent) {
                        document.querySelector('.cart-modal-content').innerHTML = newCartModalContent.innerHTML;
                    }
                    const newTokenInput = doc.querySelector('.add-to-cart-form input[name="cart_token"]');
                    if (newTokenInput) {
                        const newToken = newTokenInput.value;
                        document.querySelectorAll('.add-to-cart-form input[name="cart_token"]').forEach(function(input) {
                            input.value = newToken;
                        });
                    }
                });
            });
        });
    });
    </script>
    <main>
       <div class="shop-layout">
            <aside class="category-sidebar">
                <form method="get">
                    <label for="category">Category</label>
                    <select name="category" id="category" onchange="this.form.submit()">
                        <option value="">All</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"<?php if ($selectedCategory === $cat) echo ' selected'; ?>><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="add-product.php" class="view-btn" style="margin-top:1.5rem;text-align:center;">+ Add Product</a>
                <?php endif; ?>
               
                <a href="index.php" class="view-btn" style="margin-top:1.5rem;text-align:center;background:#e0f7fa;">&larr; Back to Main Page</a>
            </aside>
             <div class="shop-products">
                <?php if (empty($products)): ?>
                    <div style="text-align:center;width:100%;color:#888;">No products found.</div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
    <?php if ($selectedCategory && $product['category'] !== $selectedCategory) continue; ?>
    <div class="shop-product">
        <img src="<?php echo htmlspecialchars($product['img']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
        <div class="price">₱<?php echo number_format($product['price'], 2); ?></div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <form class="add-to-cart-form" method="post" style="margin-bottom: 0.5rem;">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="cart_token" value="<?php echo $_SESSION['cart_token']; ?>">
                <button type="submit" name="add_to_cart">Add to Cart</button>
            </form>
            <a href="product.php?id=<?php echo $product['id']; ?>" class="view-btn">View Details</a>
        <?php else: ?>
            <button type="button" class="login-popup-btn" style="width:100%;background:#ccc;color:#888;cursor:pointer;margin-bottom:0.5rem;">Login to Add to Cart</button>
            <a href="product.php?id=<?php echo $product['id']; ?>" class="view-btn" style="width:100%;">View Details</a>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div id="login-modal" style="display:none;position:fixed;z-index:2000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);align-items:center;justify-content:center;">
        <div style="background:#fff;padding:2rem 2.5rem;border-radius:8px;max-width:350px;margin:auto;box-shadow:0 6px 32px rgba(2,62,138,0.18);position:relative;">
            <span id="close-login-modal" style="position:absolute;top:10px;right:16px;font-size:1.5rem;cursor:pointer;">&times;</span>
            <h3 style="margin-top:0;">Login Required</h3>
            <p style="color:#444;">You must be logged in to add items to your cart.</p>
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