<?php
// index.php - Sprint Gear Home Page
$pageTitle = "Sprint Gear | Run Faster. Play Harder. Gear Up";
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Fetch 6 main categories for Shop By Category
$stmtCats = $pdo->query("SELECT * FROM categories ORDER BY id ASC LIMIT 6");
$homeCategories = $stmtCats->fetchAll();

// Fetch Featured Products (One horizontal row on desktop)
$stmtFeatured = $pdo->query("SELECT * FROM products WHERE status = 'active' AND is_featured = 1 ORDER BY id DESC LIMIT 4");
$featuredProducts = $stmtFeatured->fetchAll();

// Fetch Latest Products (One horizontal row on desktop, auto queried by created_at)
$stmtLatest = $pdo->query("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 4");
$latestProducts = $stmtLatest->fetchAll();
?>

<!-- 1. HERO SECTION -->
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="container hero-container">
        <div class="hero-content">
            <span class="hero-badge"><i class="fas fa-bolt"></i> Official Athletic Store</span>
            <h1 class="hero-title">
                RUN FASTER.<br>
                PLAY HARDER.<br>
                <span>GEAR UP WITH SPRINT GEAR</span>
            </h1>
            <p class="hero-desc">High quality sports gear for every athlete. Push your limits with the best.</p>
            <a href="products.php" class="btn-hero">
                SHOP NOW <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- 2. SHOP BY CATEGORY SECTION -->
<section class="category-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">SHOP BY CATEGORY</h2>
            <a href="products.php" class="view-all-link">VIEW ALL CATEGORIES <i class="fas fa-chevron-right"></i></a>
        </div>

        <div class="category-row-desktop">
            <?php foreach ($homeCategories as $cat): ?>
                <a href="products.php?category=<?php echo urlencode($cat['name']); ?>" class="category-card">
                    <img src="<?php echo htmlspecialchars($cat['image']); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>" class="category-card-img" loading="lazy">
                    <div class="category-card-overlay"></div>
                    <div class="category-card-body">
                        <div class="category-icon"><i class="fas <?php echo htmlspecialchars($cat['icon']); ?>"></i></div>
                        <h3 class="category-name"><?php echo htmlspecialchars($cat['name']); ?></h3>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 3. FEATURED PRODUCTS SECTION -->
<section class="products-section bg-light">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">FEATURED PRODUCTS</h2>
                <p class="text-muted" style="margin-top:0.25rem;">Handpicked high-performance gear engineered for elite performance.</p>
            </div>
            <a href="products.php?featured=1" class="view-all-link">SEE ALL FEATURED <i class="fas fa-chevron-right"></i></a>
        </div>

        <div class="products-horizontal-row">
            <?php foreach ($featuredProducts as $product): ?>
                <?php include __DIR__ . '/includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 4. LATEST ARRIVALS PRODUCTS SECTION -->
<section class="products-section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">LATEST ARRIVALS</h2>
                <p class="text-muted" style="margin-top:0.25rem;">Newly released activewear and equipment fresh from top brands.</p>
            </div>
            <a href="products.php?sort=latest" class="view-all-link">EXPLORE LATEST <i class="fas fa-chevron-right"></i></a>
        </div>

        <div class="products-horizontal-row">
            <?php foreach ($latestProducts as $product): ?>
                <?php include __DIR__ . '/includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
