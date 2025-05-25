<?php
session_start();
require_once __DIR__ . '/app/includes/database.php';

if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $img = $_FILES['img_file'];
    $success = false;
    $uploadError = '';
    $uploadedImagePath = '';

    
    $db = new \Aries\Dbmodel\Includes\Database();
    $pdo = $db->getConnection();

    
    if ($img['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $targetDir = __DIR__ . "/products/{$category}/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $filename = uniqid('prod_', true) . '.' . $ext;
            $targetPath = $targetDir . $filename;
            if (move_uploaded_file($img['tmp_name'], $targetPath)) {
                $uploadedImagePath = "products/{$category}/$filename";
                
                $pdo->beginTransaction();
                $catStmt = $pdo->prepare("SELECT id FROM product_categories WHERE category_name = ? LIMIT 1");
                $catStmt->execute([$category]);
                $catRow = $catStmt->fetch(PDO::FETCH_ASSOC);
                if ($catRow) {
                    $categoryId = $catRow['id'];
                } else {
                    $now = date('Y-m-d H:i:s');
                    $insertCat = $pdo->prepare("INSERT INTO product_categories (category_name, created_at, updated_at) VALUES (?, ?, ?)");
                    $insertCat->execute([$category, $now, $now]);
                    $categoryId = $pdo->lastInsertId();
                }
               
                $now = date('Y-m-d H:i:s');
                $prodStmt = $pdo->prepare("INSERT INTO products (name, price, description, image, created_at, updated_at, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $prodStmt->execute([
                    $name,
                    $price,
                    $description,
                    $uploadedImagePath,
                    $now,
                    $now,
                    $categoryId
                ]);
                $pdo->commit();
                $success = true;
            } else {
                $uploadError = 'Failed to move uploaded file.';
            }
        } else {
            $uploadError = 'Invalid file type.';
        }
    } else {
        $uploadError = 'Image upload failed.';
    }
}

$categories = ['Freshwater', 'Saltwater', 'Plants', 'Accessories'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | Solitary Fish Store</title>
    <link rel="stylesheet" href="style.css">
    <style>
    .add-product-container {
        max-width: 440px;
        margin: 3rem auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(2,62,138,0.10);
        padding: 2.5rem 2rem 2rem 2rem;
    }
    .add-product-container h2 {
        text-align: center;
        margin-bottom: 1.5rem;
        color: #023e8a;
        letter-spacing: 1px;
    }
    .add-product-container label {
        display: block;
        margin-bottom: 0.3rem;
        font-weight: bold;
        color: #184e77;
    }
    .add-product-container input, .add-product-container select, .add-product-container textarea {
        width: 100%;
        padding: 0.7rem;
        margin-bottom: 1.2rem;
        border-radius: 7px;
        border: 1.5px solid #b2ebf2;
        font-size: 1rem;
        background: #e0f7fa;
        color: #184e77;
        transition: border 0.2s;
        box-sizing: border-box;
    }
    .add-product-container input:focus, .add-product-container select:focus, .add-product-container textarea:focus {
        border: 1.5px solid #00b4d8;
        outline: none;
        background: #fff;
    }
    .add-product-container button {
        width: 100%;
        background: linear-gradient(90deg, #00b4d8 0%, #48cae4 100%);
        color: #fff;
        border: none;
        padding: 0.8rem 0;
        border-radius: 7px;
        font-size: 1.1rem;
        font-weight: bold;
        letter-spacing: 1px;
        cursor: pointer;
        transition: background 0.2s;
        margin-top: 0.5rem;
        box-shadow: 0 2px 8px rgba(2,62,138,0.08);
    }
    .add-product-container button:hover {
        background: linear-gradient(90deg, #023e8a 0%, #00b4d8 100%);
    }
    .success-message {
        background: #e6ffed;
        color: #1a7f37;
        border: 1px solid #b7ebc6;
        padding: 0.7rem 1rem;
        border-radius: 7px;
        margin-bottom: 1.2rem;
        text-align: center;
        font-size: 1.05em;
    }
    .error-message {
        background: #ffeaea;
        color: #b71c1c;
        border: 1px solid #f5c6cb;
        padding: 0.7rem 1rem;
        border-radius: 7px;
        margin-bottom: 1.2rem;
        text-align: center;
        font-size: 1.05em;
    }
    </style>
</head>
<body>
    <div class="add-product-container">
        <h2>Add New Product</h2>
        <?php if (!empty($success)): ?>
            <div class="success-message">Product added successfully!<br>Image uploaded to <code><?php echo htmlspecialchars($uploadedImagePath); ?></code></div>
        <?php elseif (!empty($uploadError)): ?>
            <div class="error-message">Error: <?php echo htmlspecialchars($uploadError); ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <label for="name">Product Name</label>
            <input type="text" name="name" id="name" required>

            <label for="price">Price (₱)</label>
            <input type="number" name="price" id="price" min="0" step="0.01" required>

            <label for="description">Description</label>
            <textarea name="description" id="description" rows="3" required></textarea>

            <label for="category">Category</label>
            <select name="category" id="category" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat; ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="img_file">Product Image</label>
            <input type="file" name="img_file" id="img_file" accept="image/*" required>

            <button type="submit" name="add_product">Add Product</button>
        </form>
        <a href="shop.php" style="display:inline-block;margin-top:1.2rem;text-align:center;width:100%;background:#f1f1f1;color:#023e8a;padding:0.7rem 0;border-radius:7px;text-decoration:none;font-weight:bold;transition:background 0.2s;">&larr; Back to Shop</a>
    </div>
</body>
</html>