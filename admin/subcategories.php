<?php
// admin/subcategories.php - Subcategory Management & Category Linking
$adminTitle = "Subcategories Management";
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Process Add/Delete Subcategory POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_subcategory') {
        $catId = (int)($_POST['category_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');

        if ($catId > 0 && !empty($name)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            $stmtInsSub = $pdo->prepare("INSERT INTO subcategories (category_id, name, slug) VALUES (?, ?, ?)");
            $stmtInsSub->execute([$catId, $name, $slug]);
            setFlash('success', 'Subcategory created!');
        }
        header("Location: subcategories.php");
        exit;
    }

    if ($_POST['action'] === 'delete_subcategory') {
        $subId = (int)$_POST['subcategory_id'];
        $stmtDelSub = $pdo->prepare("DELETE FROM subcategories WHERE id = ?");
        $stmtDelSub->execute([$subId]);
        setFlash('info', 'Subcategory deleted.');
        header("Location: subcategories.php");
        exit;
    }
}

$allCategories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$subcategories = $pdo->query("
    SELECT sc.*, c.name as category_name, COUNT(p.id) as product_count
    FROM subcategories sc
    JOIN categories c ON sc.category_id = c.id
    LEFT JOIN products p ON sc.id = p.subcategory_id
    GROUP BY sc.id
    ORDER BY c.name ASC, sc.name ASC
")->fetchAll();
?>

<div class="admin-grid-2">
    <!-- ADD SUBCATEGORY FORM -->
    <div class="admin-card">
        <h3 class="admin-card-title" style="margin-bottom:1.25rem;"><i class="fas fa-plus text-red-500"></i> Add Subcategory</h3>
        <form action="subcategories.php" method="POST">
            <input type="hidden" name="action" value="add_subcategory">
            
            <div class="admin-form-group">
                <label>Main Category *</label>
                <select name="category_id" class="admin-control" required>
                    <option value="">-- Select Parent Category --</option>
                    <?php foreach ($allCategories as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-form-group">
                <label>Subcategory Name *</label>
                <input type="text" name="name" class="admin-control" placeholder="e.g. Cleats, Shin Guards, Hydration" required>
            </div>

            <button type="submit" class="btn-admin-action">Create Subcategory</button>
        </form>
    </div>

    <!-- SUBCATEGORIES LIST TABLE -->
    <div class="admin-card">
        <h3 class="admin-card-title" style="margin-bottom:1.25rem;"><i class="fas fa-sitemap text-red-500"></i> All Subcategories (<?php echo count($subcategories); ?>)</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Subcategory</th>
                    <th>Parent Category</th>
                    <th>Products</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subcategories as $sc): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($sc['name']); ?></strong></td>
                        <td><span style="color:#94A3B8;"><?php echo htmlspecialchars($sc['category_name']); ?></span></td>
                        <td><span style="font-weight:700; color:#10B981;"><?php echo $sc['product_count']; ?> products</span></td>
                        <td>
                            <form action="subcategories.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete subcategory?');">
                                <input type="hidden" name="action" value="delete_subcategory">
                                <input type="hidden" name="subcategory_id" value="<?php echo $sc['id']; ?>">
                                <button type="submit" class="btn-admin-action btn-admin-sm" style="background:#991B1B;"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
