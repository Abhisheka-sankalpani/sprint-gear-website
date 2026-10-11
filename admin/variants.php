<?php
// admin/variants.php - Product Variant Stock & Variant Image Manager
$adminTitle = "Product Variants & Stock Management";
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();
$selectedProductId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$isLowStockFilter = isset($_GET['filter']) && $_GET['filter'] === 'low_stock';

// Fetch list of all active products for selector
$allProducts = $pdo->query("SELECT id, name, brand, sku FROM products ORDER BY name ASC")->fetchAll();
if ($selectedProductId === 0 && !empty($allProducts) && !$isLowStockFilter) {
    $selectedProductId = $allProducts[0]['id'];
}

$colors = $pdo->query("SELECT * FROM colors ORDER BY name ASC")->fetchAll();
$sizes = $pdo->query("SELECT * FROM sizes ORDER BY id ASC")->fetchAll();

// Handle Add or Update Variant POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $redirectUrl = isset($_POST['redirect_filter']) && $_POST['redirect_filter'] === 'low_stock' 
        ? "variants.php?filter=low_stock" 
        : "variants.php?product_id=" . ((int)($_POST['product_id'] ?? $selectedProductId));

    if ($action === 'add_variant') {
        $prodId = (int)$_POST['product_id'];
        $colorId = (int)$_POST['color_id'];
        $sizeId = (int)$_POST['size_id'];
        $stock = (int)$_POST['stock'];
        $sku = sanitize($_POST['sku'] ?? '');

        // Handle Variant Image Upload
        $variantImage = null;
        if (isset($_FILES['variant_image']) && $_FILES['variant_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['variant_image']['tmp_name'];
            $fileName = time() . '_v_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['variant_image']['name']);
            $targetPath = __DIR__ . '/../uploads/variants/' . $fileName;
            if (move_uploaded_file($fileTmp, $targetPath)) {
                $variantImage = 'uploads/variants/' . $fileName;
            }
        }

        if ($prodId > 0 && $colorId > 0 && $sizeId > 0) {
            if (empty($sku)) {
                $sku = "VAR-" . $prodId . "-" . $colorId . "-" . $sizeId . "-" . time();
            }

            try {
                $stmtInsV = $pdo->prepare("
                    INSERT INTO product_variants (product_id, color_id, size_id, sku, stock, variant_image)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmtInsV->execute([$prodId, $colorId, $sizeId, $sku, $stock, $variantImage]);
                setFlash('success', 'New variant created successfully!');
            } catch (Exception $e) {
                setFlash('error', 'Error creating variant: Duplicate SKU or combination.');
            }
        }
        header("Location: variants.php?product_id=$prodId");
        exit;
    }

    if ($action === 'update_stock') {
        $variantId = (int)$_POST['variant_id'];
        $newStock = max(0, (int)$_POST['stock']);
        $stmtUpdStock = $pdo->prepare("UPDATE product_variants SET stock = ? WHERE id = ?");
        $stmtUpdStock->execute([$newStock, $variantId]);
        setFlash('info', 'Variant stock updated successfully.');
        header("Location: " . $redirectUrl);
        exit;
    }

    if ($action === 'delete_variant') {
        $variantId = (int)$_POST['variant_id'];
        $stmtDelV = $pdo->prepare("DELETE FROM product_variants WHERE id = ?");
        $stmtDelV->execute([$variantId]);
        setFlash('info', 'Variant removed.');
        header("Location: " . $redirectUrl);
        exit;
    }
}

// Fetch low stock variants if filter requested
$lowStockVariants = [];
if ($isLowStockFilter) {
    $stmtLow = $pdo->query("
        SELECT pv.*, p.id as product_id, p.name as product_name, p.brand, c.name as color_name, c.hex_code, s.name as size_name
        FROM product_variants pv
        JOIN products p ON pv.product_id = p.id
        JOIN colors c ON pv.color_id = c.id
        JOIN sizes s ON pv.size_id = s.id
        WHERE pv.stock <= 5
        ORDER BY pv.stock ASC, p.name ASC
    ");
    $lowStockVariants = $stmtLow->fetchAll();
}

// Fetch variants for selected product
$variants = [];
$selectedProduct = null;
if ($selectedProductId > 0) {
    $stmtP = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmtP->execute([$selectedProductId]);
    $selectedProduct = $stmtP->fetch();

    $stmtV = $pdo->prepare("
        SELECT pv.*, c.name as color_name, c.hex_code, s.name as size_name
        FROM product_variants pv
        JOIN colors c ON pv.color_id = c.id
        JOIN sizes s ON pv.size_id = s.id
        WHERE pv.product_id = ?
        ORDER BY c.name ASC, s.id ASC
    ");
    $stmtV->execute([$selectedProductId]);
    $variants = $stmtV->fetchAll();
}

$lowStockCountNav = getLowStockCount(5);
?>

<!-- PRODUCT SELECTOR / FILTER TOPBAR -->
<div class="admin-card" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
    <div>
        <label style="font-weight:700; color:#94A3B8; margin-right:1rem;">Select Product:</label>
        <select class="admin-control" style="max-width:350px; display:inline-block;" onchange="window.location.href='variants.php?product_id='+this.value">
            <?php foreach ($allProducts as $p): ?>
                <option value="<?php echo $p['id']; ?>" <?php echo (!$isLowStockFilter && $selectedProductId === (int)$p['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($p['brand'] . ' - ' . $p['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <a href="variants.php?filter=low_stock" class="btn-admin-action <?php echo $isLowStockFilter ? '' : 'btn-admin-secondary'; ?>" style="background:<?php echo $isLowStockFilter ? '#F97316' : ''; ?>;">
            <i class="fas fa-exclamation-triangle"></i> View Low Stock Items (<?php echo $lowStockCountNav; ?>)
        </a>
    </div>
</div>

<?php if ($isLowStockFilter): ?>
    <!-- LOW STOCK VARIANTS TABLE VIEW -->
    <div class="admin-card">
        <h3 class="admin-card-title" style="margin-bottom:1.25rem; color:#F97316;">
            <i class="fas fa-exclamation-triangle"></i> Low Stock & Out of Stock Items (Quantity ≤ 5)
        </h3>

        <?php if (empty($lowStockVariants)): ?>
            <p style="color:#10B981; font-weight:600;"><i class="fas fa-check-circle"></i> All products have sufficient stock! No items are currently low in stock.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Color</th>
                        <th>Size</th>
                        <th>SKU</th>
                        <th>Stock Count</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStockVariants as $lv): ?>
                        <tr>
                            <td>
                                <strong><a href="variants.php?product_id=<?php echo $lv['product_id']; ?>" style="color:#F8FAFC; text-decoration:underline;"><?php echo htmlspecialchars($lv['brand'] . ' - ' . $lv['product_name']); ?></a></strong>
                            </td>
                            <td>
                                <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:<?php echo htmlspecialchars($lv['hex_code']); ?>; margin-right:4px;"></span>
                                <?php echo htmlspecialchars($lv['color_name']); ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($lv['size_name']); ?></strong></td>
                            <td><small style="color:#94A3B8;"><?php echo htmlspecialchars($lv['sku']); ?></small></td>
                            <td>
                                <form action="variants.php" method="POST" style="display:flex; gap:0.3rem;">
                                    <input type="hidden" name="action" value="update_stock">
                                    <input type="hidden" name="redirect_filter" value="low_stock">
                                    <input type="hidden" name="variant_id" value="<?php echo $lv['id']; ?>">
                                    <input type="number" name="stock" value="<?php echo $lv['stock']; ?>" min="0" style="width:65px; padding:0.2rem;" class="admin-control">
                                    <button type="submit" class="btn-admin-action btn-admin-sm" title="Save Stock"><i class="fas fa-save"></i></button>
                                </form>
                            </td>
                            <td>
                                <?php if ($lv['stock'] == 0): ?>
                                    <span style="padding:0.2rem 0.5rem; border-radius:4px; font-weight:700; font-size:0.75rem; background:#991B1B; color:white;">OUT OF STOCK</span>
                                <?php else: ?>
                                    <span style="padding:0.2rem 0.5rem; border-radius:4px; font-weight:700; font-size:0.75rem; background:#C2410C; color:white;">LOW STOCK (<?php echo $lv['stock']; ?> left)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="variants.php?product_id=<?php echo $lv['product_id']; ?>" class="btn-admin-action btn-admin-secondary btn-admin-sm">Manage Product</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($selectedProduct): ?>
    <div class="admin-grid-2">
        <!-- ADD VARIANT FORM -->
        <div class="admin-card">
            <h3 class="admin-card-title" style="margin-bottom:1.25rem;"><i class="fas fa-plus text-red-500"></i> Add Variant Combination</h3>
            <form action="variants.php?product_id=<?php echo $selectedProductId; ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_variant">
                <input type="hidden" name="product_id" value="<?php echo $selectedProductId; ?>">

                <div class="admin-form-group">
                    <label>Color *</label>
                    <select name="color_id" class="admin-control" required>
                        <option value="">-- Select Color --</option>
                        <?php foreach ($colors as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label>Size *</label>
                    <select name="size_id" class="admin-control" required>
                        <option value="">-- Select Size --</option>
                        <?php foreach ($sizes as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?> (<?php echo $s['category_type']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label>Stock Quantity *</label>
                    <input type="number" name="stock" class="admin-control" value="10" min="0" required>
                </div>

                <div class="admin-form-group">
                    <label>Custom Variant SKU (Optional)</label>
                    <input type="text" name="sku" class="admin-control" placeholder="Leave empty for auto-generated SKU">
                </div>

                <div class="admin-form-group">
                    <label>Color Specific Variant Image (Optional)</label>
                    <input type="file" name="variant_image" class="admin-control" accept="image/*">
                </div>

                <button type="submit" class="btn-admin-action">Add Product Variant</button>
            </form>
        </div>

        <!-- EXISTING VARIANTS LIST TABLE -->
        <div class="admin-card">
            <h3 class="admin-card-title" style="margin-bottom:1.25rem;"><i class="fas fa-list text-red-500"></i> Active Variants (<?php echo count($variants); ?>)</h3>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Variant Image</th>
                        <th>Color</th>
                        <th>Size</th>
                        <th>SKU</th>
                        <th>Stock Count</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variants as $v): ?>
                        <tr>
                            <td>
                                <?php if ($v['variant_image']): ?>
                                    <img src="../<?php echo htmlspecialchars($v['variant_image']); ?>" alt="V" style="width:36px; height:36px; object-fit:cover; border-radius:4px;">
                                <?php else: ?>
                                    <span style="font-size:0.75rem; color:#94A3B8;">Base Img</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:<?php echo htmlspecialchars($v['hex_code']); ?>; margin-right:4px;"></span>
                                <?php echo htmlspecialchars($v['color_name']); ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($v['size_name']); ?></strong></td>
                            <td><small style="color:#94A3B8;"><?php echo htmlspecialchars($v['sku']); ?></small></td>
                            <td>
                                <form action="variants.php?product_id=<?php echo $selectedProductId; ?>" method="POST" style="display:flex; gap:0.3rem;">
                                    <input type="hidden" name="action" value="update_stock">
                                    <input type="hidden" name="variant_id" value="<?php echo $v['id']; ?>">
                                    <input type="number" name="stock" value="<?php echo $v['stock']; ?>" min="0" style="width:60px; padding:0.2rem;" class="admin-control">
                                    <button type="submit" class="btn-admin-action btn-admin-sm"><i class="fas fa-save"></i></button>
                                </form>
                            </td>
                            <td>
                                <?php if ($v['stock'] == 0): ?>
                                    <span style="padding:0.2rem 0.4rem; border-radius:4px; font-weight:700; font-size:0.7rem; background:#991B1B; color:white;">OUT OF STOCK</span>
                                <?php elseif ($v['stock'] <= 5): ?>
                                    <span style="padding:0.2rem 0.4rem; border-radius:4px; font-weight:700; font-size:0.7rem; background:#C2410C; color:white;">LOW STOCK</span>
                                <?php else: ?>
                                    <span style="padding:0.2rem 0.4rem; border-radius:4px; font-weight:700; font-size:0.7rem; background:#065F46; color:white;">IN STOCK</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form action="variants.php?product_id=<?php echo $selectedProductId; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Delete this variant?');">
                                    <input type="hidden" name="action" value="delete_variant">
                                    <input type="hidden" name="variant_id" value="<?php echo $v['id']; ?>">
                                    <button type="submit" class="btn-admin-action btn-admin-sm" style="background:#991B1B;"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
