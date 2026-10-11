<?php
// admin/add-product.php - Add New Product Form
$adminTitle = "Add New Product";
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();
$allCategories = getNavigationCategories();

$errorMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $brand = sanitize($_POST['brand'] ?? '');
    $gender = sanitize($_POST['gender'] ?? 'Unisex');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $subcategoryId = (int)($_POST['subcategory_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $discountPrice = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
    $shortDesc = sanitize($_POST['short_description'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $sportType = sanitize($_POST['sport_type'] ?? '');
    $material = sanitize($_POST['material'] ?? '');
    $country = sanitize($_POST['country_of_manufacture'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    // Handle Main Image Upload
    $mainImage = 'uploads/products/nike_pegasus_40_black.jpg'; // Default fallback
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['main_image']['tmp_name'];
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['main_image']['name']);
        $targetPath = __DIR__ . '/../uploads/products/' . $fileName;
        if (move_uploaded_file($fileTmp, $targetPath)) {
            $mainImage = 'uploads/products/' . $fileName;
        }
    }

    if (!empty($name) && !empty($brand) && $categoryId > 0 && $subcategoryId > 0 && $price > 0) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name . '-' . time()), '-'));
        $sku = strtoupper(substr($brand, 0, 3) . "-" . time());

        $stmtIns = $pdo->prepare("
            INSERT INTO products (category_id, subcategory_id, brand, name, slug, gender, description, short_description, price, discount_price, main_image, status, is_featured, sport_type, material, country_of_manufacture, sku, rating, reviews_count)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?, 5.0, 0)
        ");
        $stmtIns->execute([
            $categoryId, $subcategoryId, $brand, $name, $slug, $gender,
            $description, $shortDesc, $price, $discountPrice, $mainImage,
            $isFeatured, $sportType, $material, $country, $sku
        ]);
        $productId = $pdo->lastInsertId();

        setFlash('success', 'Product created successfully! Now add variants for colors and sizes.');
        header("Location: variants.php?product_id=$productId");
        exit;
    } else {
        $errorMsg = "Please fill in all required fields.";
    }
}
?>

<div class="admin-card" style="max-width:800px; margin:0 auto;">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fas fa-plus-circle text-red-500"></i> Add New Product</h2>
        <a href="products.php" class="btn-admin-action btn-admin-secondary">Back to Products</a>
    </div>

    <?php if ($errorMsg): ?>
        <div style="background:#991B1B; color:white; padding:0.8rem; border-radius:6px; margin-bottom:1rem;">
            <?php echo htmlspecialchars($errorMsg); ?>
        </div>
    <?php endif; ?>

    <form action="add-product.php" method="POST" enctype="multipart/form-data">
        <div class="admin-grid-2">
            <div class="admin-form-group">
                <label>Product Name *</label>
                <input type="text" name="name" class="admin-control" placeholder="e.g. Nike Air Zoom Pegasus 41" required>
            </div>
            <div class="admin-form-group">
                <label>Brand *</label>
                <input type="text" name="brand" class="admin-control" placeholder="e.g. Nike, Adidas, ASICS" required>
            </div>
        </div>

        <div class="admin-grid-2">
            <div class="admin-form-group">
                <label>Category *</label>
                <select name="category_id" id="catSelect" class="admin-control" required onchange="updateSubcatOptions()">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form-group">
                <label>Subcategory *</label>
                <select name="subcategory_id" id="subSelect" class="admin-control" required>
                    <option value="">-- Select Category First --</option>
                </select>
            </div>
        </div>

        <div class="admin-grid-2">
            <div class="admin-form-group">
                <label>Gender *</label>
                <select name="gender" class="admin-control" required>
                    <option value="Unisex">Unisex</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Kids">Kids</option>
                </select>
            </div>
            <div class="admin-form-group">
                <label>Regular Price (Rs.) *</label>
                <input type="number" step="0.01" name="price" class="admin-control" placeholder="32500.00" required>
            </div>
        </div>

        <div class="admin-grid-2">
            <div class="admin-form-group">
                <label>Discount Price (Rs.) (Optional)</label>
                <input type="number" step="0.01" name="discount_price" class="admin-control" placeholder="29900.00">
            </div>
            <div class="admin-form-group">
                <label>Main Product Image</label>
                <input type="file" name="main_image" class="admin-control" accept="image/*">
            </div>
        </div>

        <div class="admin-grid-2">
            <div class="admin-form-group">
                <label>Sport Type</label>
                <input type="text" name="sport_type" class="admin-control" placeholder="Running, Football, Fitness">
            </div>
            <div class="admin-form-group">
                <label>Material</label>
                <input type="text" name="material" class="admin-control" placeholder="Breathable Mesh, Polyester">
            </div>
        </div>

        <div class="admin-form-group">
            <label>Country of Manufacture</label>
            <input type="text" name="country_of_manufacture" class="admin-control" placeholder="Vietnam, Indonesia">
        </div>

        <div class="admin-form-group">
            <label>Short Overview Description *</label>
            <input type="text" name="short_description" class="admin-control" placeholder="High performance athletic gear built for speed..." required>
        </div>

        <div class="admin-form-group">
            <label>Full Product Description *</label>
            <textarea name="description" rows="4" class="admin-control" required></textarea>
        </div>

        <div class="admin-form-group">
            <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                <input type="checkbox" name="is_featured" value="1">
                <span>Mark as Featured Product on Home Page</span>
            </label>
        </div>

        <button type="submit" class="btn-admin-action" style="padding:0.8rem 2rem;">Create Product & Set Variants</button>
    </form>
</div>

<script>
const categoryDataMap = <?php echo json_encode($allCategories); ?>;
function updateSubcatOptions() {
    const catId = parseInt(document.getElementById('catSelect').value);
    const subSelect = document.getElementById('subSelect');
    subSelect.innerHTML = '<option value="">-- Select Subcategory --</option>';

    const foundCat = Object.values(categoryDataMap).find(c => parseInt(c.id) === catId);
    if (foundCat && foundCat.subcategories) {
        foundCat.subcategories.forEach(sub => {
            const opt = document.createElement('option');
            opt.value = sub.id;
            opt.textContent = sub.name;
            subSelect.appendChild(opt);
        });
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
