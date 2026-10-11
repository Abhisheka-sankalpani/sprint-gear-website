<?php
// admin/categories.php - Main Category Management
$adminTitle = "Categories Management";
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Process Add Category POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_category') {
        $name = sanitize($_POST['name'] ?? '');
        $icon = sanitize($_POST['icon'] ?? 'fa-running');

        $image = 'uploads/categories/cat_running_gear.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['image']['tmp_name'];
            $fileName = time() . '_cat_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['image']['name']);
            $targetPath = __DIR__ . '/../uploads/categories/' . $fileName;
            if (move_uploaded_file($fileTmp, $targetPath)) {
                $image = 'uploads/categories/' . $fileName;
            }
        }

        if (!empty($name)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            $stmtInsC = $pdo->prepare("INSERT INTO categories (name, slug, image, icon) VALUES (?, ?, ?, ?)");
            $stmtInsC->execute([$name, $slug, $image, $icon]);
            setFlash('success', 'New Category added successfully!');
        }
        header("Location: categories.php");
        exit;
    }

    if ($_POST['action'] === 'delete_category') {
        $catId = (int)$_POST['category_id'];
        $stmtDelC = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmtDelC->execute([$catId]);
        setFlash('info', 'Category deleted.');
        header("Location: categories.php");
        exit;
    }
}

$categories = $pdo->query("SELECT c.*, COUNT(sc.id) as subcat_count FROM categories c LEFT JOIN subcategories sc ON c.id = sc.category_id GROUP BY c.id ORDER BY c.id ASC")->fetchAll();
?>

<div class="admin-grid-2">
    <!-- ADD CATEGORY FORM -->
    <div class="admin-card">
        <h3 class="admin-card-title" style="margin-bottom:1.25rem;"><i class="fas fa-plus text-red-500"></i> Add Main Category</h3>
        <form action="categories.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_category">
            
            <div class="admin-form-group">
                <label>Category Name *</label>
                <input type="text" name="name" class="admin-control" placeholder="e.g. Tennis & Racket Sports" required>
            </div>

            <div class="admin-form-group">
                <label>FontAwesome Icon Class *</label>
                <input type="text" name="icon" class="admin-control" value="fa-table-tennis" required>
            </div>

            <div class="admin-form-group">
                <label>Category Image</label>
                <input type="file" name="image" class="admin-control" accept="image/*">
            </div>

            <button type="submit" class="btn-admin-action">Create Main Category</button>
        </form>
    </div>

    <!-- CATEGORIES LIST TABLE -->
    <div class="admin-card">
        <h3 class="admin-card-title" style="margin-bottom:1.25rem;"><i class="fas fa-layer-group text-red-500"></i> Categories List</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Category Name</th>
                    <th>Subcategories</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td>
                            <img src="../<?php echo htmlspecialchars($c['image']); ?>" alt="C" style="width:40px; height:40px; object-fit:cover; border-radius:4px;">
                        </td>
                        <td>
                            <i class="fas <?php echo htmlspecialchars($c['icon']); ?> text-red-500"></i>
                            <strong><?php echo htmlspecialchars($c['name']); ?></strong>
                        </td>
                        <td><span style="font-weight:700; color:#EF4444;"><?php echo $c['subcat_count']; ?> subcategories</span></td>
                        <td>
                            <form action="categories.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete category? All subcategories and products inside will be deleted.');">
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="category_id" value="<?php echo $c['id']; ?>">
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
