<?php
// wishlist.php - User Saved Wishlist Page & AJAX Handler
require_once __DIR__ . '/includes/functions.php';

// Handle AJAX Request from front-end heart icons
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_wishlist') {
    header('Content-Type: application/json');
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $res = toggleWishlist($productId);
    echo json_encode($res);
    exit;
}

$pageTitle = "My Wishlist";
requireLogin();
$user = getLoggedInUser();
$pdo = getDBConnection();

// Fetch saved wishlist items for logged-in user
$stmtWish = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM wishlist_items wi
    JOIN wishlists w ON wi.wishlist_id = w.id
    JOIN products p ON wi.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = ? AND p.status = 'active'
    ORDER BY wi.created_at DESC
");
$stmtWish->execute([$user['id']]);
$wishlistProducts = $stmtWish->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header" style="padding: 1.5rem 0; margin-bottom: 2rem;">
    <div class="container">
        <h1 class="page-title">MY WISHLIST</h1>
        <ul class="breadcrumb">
            <li><a href="index.php">Home</a></li>
            <li class="active">Wishlist</li>
        </ul>
    </div>
</div>

<div class="container" style="padding-bottom: 5rem;">
    <?php if (!empty($wishlistProducts)): ?>
        <div class="products-grid">
            <?php foreach ($wishlistProducts as $product): ?>
                <?php include __DIR__ . '/includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="far fa-heart empty-icon"></i>
            <h2>Your Wishlist is Empty</h2>
            <p>Save products you love by clicking the heart icon on any product card.</p>
            <a href="products.php" class="btn-hero" style="margin-top:1.5rem;">Explore Catalog</a>
        </div>
    <?php endif; ?>
</div>

<style>
.products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; }
@media (max-width: 1024px) { .products-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) { .products-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .products-grid { grid-template-columns: repeat(1, 1fr); } }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>