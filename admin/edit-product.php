<?php
// admin/edit-product.php - Edit Existing Product Form
$adminTitle = "Edit Product";
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit;
}

$allCategories = getNavigationCategories();

$errorMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $brand = sanitize($_POST['brand'] ?? '');
    $gender = sanitize($_POST['gender'] ?? 'Unisex');
    $price = (float)($_POST['price'] ?? 0);
    $discountPrice = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
    $shortDesc = sanitize($_POST['short_description'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $sportType = sanitize($_POST['sport_type'] ?? '');
    $material = sanitize($_POST['material'] ?? '');
    $country = sanitize($_POST['country_of_manufacture'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $status = sanitize($_POST['status'] ?? 'active');

    // Main Image update if uploaded
    $mainImage = $product['main_image'];
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['main_image']['tmp_name'];
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['main_image']['name']);
        $targetPath = __DIR__ . '/../uploads/products/' . $fileName;
        if (move_uploaded_file($fileTmp, $targetPath)) {
            $mainImage = 'uploads/products/' . $fileName;
        }
    }

    if (!empty($name) && !empty($brand) && $price > 0) {
        $stmtUpd = $pdo->prepare("
            UPDATE products 
            SET name = ?, brand = ?, gender = ?, price = ?, discount_price = ?, main_image = ?, is_featured = ?, status = ?, short_description = ?, description = ?, sport_type = ?, material = ?, country_of_manufacture = ?
            WHERE id = ?
        ");
        $stmtUpd->execute([
            $name, $brand, $gender, $price, $discountPrice, $mainImage,
            $isFeatured, $status, $shortDesc, $description, $sportType, $material, $country, $productId
        ]);

        setFlash('success', 'Product details updated successfully!');
        header("Location: products.php");
        exit;
    } else {
        $errorMsg = "Please fill in all required fields.";
    }
}
?>

<div class="admin-card" style="max-width:800px; margin:0 auto;">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fas fa-edit text-red-500"></i> Edit Product: <?php echo htmlspecialchars($product['name']); ?></h2>
        <a href="products.php" class="btn-admin-action btn-admin-secondary">Back to Products</a>
    </div>

    <?php if ($errorMsg): ?>
        <div style="background:#991B1B; color:white; padding:0.8rem; border-radius:6px; margin-bottom:1rem;">
            <?php echo htmlspecialchars($errorMsg); ?>
        </div>
    <?php endif; ?>

    <form action="edit-product.php?id=<?php echo $productId; ?>" method="POST" enctype="multipart/form-data">
        <div class="admin-grid-2">
            <div class="admin-form-group">
                <label>Product Name *</label>
                <input type="text" name="name" class="admin-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            <div class="admin-form-group">
                <label>Brand *</label>
                <input type="text" name="brand" class="admin-control" value="<?php echo htmlspecialchars($product['brand']); ?>" required>
            </div>
        </div>

        <div class="admin-grid-2">
            <div class="admin-form-group">
                <label>Gender *</label>
                <select name="gender" class="admin-control" required>
                    <?php foreach (['Unisex', 'Male', 'Female', 'Kids'] as $g): ?>
                        <option value="<?php echo $g; ?>" <?php echo $product['gender'] === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form-group">
                <label>Status *</label>
                <select name="status" class="admin-control" required>
                    <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>

        <div class="admin-grid-2">
            <div class="admin-form-group">
                <label>Regular Price (Rs.) *</label>
                <input type="number" step="0.01" name="price" class="admin-control" value="<?php echo $product['price']; ?>" required>
            </div>
            <div class="admin-form-group">
                <label>Discount Price (Rs.)</label>
                <input type="number" step="0.01" name="discount_price" class="admin-control" value="<?php echo $product['discount_price']; ?>">
            </div>
        </div>

        <div class="admin-form-group">
            <label>Main Image Upload (Leave blank to keep current)</label>
            <input type="file" name="main_image" class="admin-control" accept="image/*">
            <small style="color:#94A3B8;">Current: <?php echo htmlspecialchars($product['main_image']); ?></small>
        </div>

        <div class="admin-grid-2">
            <div class="admin-form-group">
                <label>Sport Type</label>
                <input type="text" name="sport_type" class="admin-control" value="<?php echo htmlspecialchars($product['sport_type']); ?>">
            </div>
            <div class="admin-form-group">
                <label>Material</label>
                <input type="text" name="material" class="admin-control" value="<?php echo htmlspecialchars($product['material']); ?>">
            </div>
        </div>

        <div class="admin-form-group">
            <label>Country of Manufacture</label>
            <input type="text" name="country_of_manufacture" class="admin-control" value="<?php echo htmlspecialchars($product['country_of_manufacture']); ?>">
        </div>

        <div class="admin-form-group">
            <label>Short Description *</label>
            <input type="text" name="short_description" class="admin-control" value="<?php echo htmlspecialchars($product['short_description']); ?>" required>
        </div>

        <div class="admin-form-group">
            <label>Full Product Description *</label>
            <textarea name="description" rows="4" class="admin-control" required><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>

        <div class="admin-form-group">
            <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                <input type="checkbox" name="is_featured" value="1" <?php echo !empty($product['is_featured']) ? 'checked' : ''; ?>>
                <span>Mark as Featured Product</span>
            </label>
        </div>

        <button type="submit" class="btn-admin-action" style="padding:0.8rem 2rem;">Save Product Changes</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
