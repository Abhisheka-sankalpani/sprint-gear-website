<?php
// admin/products.php - Product Catalog Management
$adminTitle = "Products Management";
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Handle Delete or Deactivate Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $prodId = (int)$_POST['product_id'];
    if ($_POST['action'] === 'toggle_status') {
        $stmtStatus = $pdo->prepare("UPDATE products SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
        $stmtStatus->execute([$prodId]);
        setFlash('info', 'Product status toggled.');
    } elseif ($_POST['action'] === 'delete_product') {
        $stmtDel = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmtDel->execute([$prodId]);
        setFlash('success', 'Product deleted permanently.');
    }
    header("Location: products.php");
    exit;
}

// Fetch all products with category names
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name, sc.name as subcategory_name 
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN subcategories sc ON p.subcategory_id = sc.id
    ORDER BY p.id DESC
");
$products = $stmt->fetchAll();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fas fa-box text-red-500"></i> All Products (<?php echo count($products); ?>)</h2>
        <a href="add-product.php" class="btn-admin-action"><i class="fas fa-plus"></i> Add New Product</a>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Product Name</th>
                <th>Brand</th>
                <th>Category</th>
                <th>Gender</th>
                <th>Price</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <img src="../<?php echo htmlspecialchars($p['main_image']); ?>" alt="Prod" style="width:48px; height:48px; object-fit:cover; border-radius:4px;">
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($p['name']); ?></strong><br>
                        <small style="color:#94A3B8;">SKU: <?php echo htmlspecialchars($p['sku']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($p['brand']); ?></td>
                    <td><?php echo htmlspecialchars($p['category_name']); ?> / <?php echo htmlspecialchars($p['subcategory_name']); ?></td>
                    <td><?php echo htmlspecialchars($p['gender']); ?></td>
                    <td>
                        <?php if ($p['discount_price']): ?>
                            <strong style="color:#EF4444;"><?php echo formatPrice($p['discount_price']); ?></strong>
                            <small style="text-decoration:line-through; color:#94A3B8;"><?php echo formatPrice($p['price']); ?></small>
                        <?php else: ?>
                            <strong><?php echo formatPrice($p['price']); ?></strong>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="padding:0.2rem 0.5rem; border-radius:4px; font-weight:700; font-size:0.75rem; background:<?php echo $p['status'] === 'active' ? '#065F46' : '#991B1B'; ?>;">
                            <?php echo strtoupper($p['status']); ?>
                        </span>
                    </td>
                    <td style="display:flex; gap:0.4rem; align-items:center;">
                        <a href="variants.php?product_id=<?php echo $p['id']; ?>" class="btn-admin-action btn-admin-secondary btn-admin-sm" title="Variants & Stock"><i class="fas fa-tags"></i> Variants</a>
                        <a href="edit-product.php?id=<?php echo $p['id']; ?>" class="btn-admin-action btn-admin-sm"><i class="fas fa-edit"></i> Edit</a>
                        <form action="products.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                            <input type="hidden" name="action" value="delete_product">
                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="btn-admin-action btn-admin-sm" style="background:#991B1B;"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
