<?php
// index.php - SprintGear Front Page
require_once __DIR__ . '/includes/header.php';

// Fetch Categories from DB
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC LIMIT 6");
$categories = $categories_stmt->fetchAll();

// Fetch Featured Products from DB with color swatches
$products_stmt = $pdo->query("SELECT * FROM products WHERE is_featured = 1 ORDER BY id ASC");
$featured_products = $products_stmt->fetchAll();
?>

<!-- HERO SECTION -->
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <span class="hero-tag"><i class="fas fa-bolt"></i> Performance Redefined</span>
        <h1 class="hero-title">
            Run Faster. Play Harder.<br>
            <span>GEAR UP WITH SPRINT GEAR</span>
        </h1>
        <p class="hero-description">
            High quality sports gear for every athlete. Push your limits with the best. Engineered for track, court, and field performance.
        </p>
        <a href="products.php" class="btn-shop-now">
            SHOP NOW <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</section>

<!-- SHOP BY CATEGORY SECTION -->
<section class="section-container">
    <div class="section-header">
        <span class="section-subtitle">Gear By Sport</span>
        <h2 class="section-title">SHOP BY CATEGORY</h2>
    </div>

    <div class="categories-grid" style="display: flex; gap: 15px; overflow-x: auto; padding-bottom: 15px;">
        <?php foreach ($categories as $cat): ?>
            <a href="products.php?category=<?= htmlspecialchars($cat['slug']) ?>" class="category-card" style="flex: 1; min-width: 160px;">
                <img src="<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                <div class="category-overlay">
                    <div class="category-icon">
                        <i class="fas <?= htmlspecialchars($cat['icon']) ?>"></i>
                    </div>
                    <h3 class="category-name"><?= htmlspecialchars($cat['name']) ?></h3>
                    <div class="category-action">
                        Explore <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- FEATURED PRODUCTS SECTION -->
<section class="section-container" style="background-color: #FAFAFC; border-top: 1px solid #EFEFEF; border-bottom: 1px solid #EFEFEF;">
    <div class="section-header">
        <span class="section-subtitle">Top Rated Gear</span>
        <h2 class="section-title">FEATURED PRODUCTS</h2>
    </div>

    <!-- Products 6ම එකම පේළියකට ගැනීම -->
    <div class="products-grid" style="display: flex; flex-wrap: nowrap; overflow-x: auto; gap: 20px; padding-bottom: 20px;">
        <?php foreach ($featured_products as $product): ?>
            <?php
            // Fetch color swatches for this product
            $swatch_stmt = $pdo->prepare("
                SELECT DISTINCT c.id, c.name, c.hex_code, pv.variant_image 
                FROM product_variants pv 
                JOIN colors c ON pv.color_id = c.id 
                WHERE pv.product_id = ?
            ");
            $swatch_stmt->execute([$product['id']]);
            $swatches = $swatch_stmt->fetchAll();
            ?>
            
            <!-- Product කාඩ් එකක පළල සකස් කිරීම -->
            <div class="product-card" style="flex: 0 0 auto; width: 250px;">
                <?php if (!empty($product['sale_price'])): ?>
                    <span class="product-badge">SALE</span>
                <?php endif; ?>

                <button class="product-wishlist-btn" data-product-id="<?= $product['id'] ?>" title="Add to Wishlist">
                    <i class="far fa-heart"></i>
                </button>

                <a href="product-detail.php?id=<?= $product['id'] ?>" class="product-image-wrapper">
                    <img src="<?= htmlspecialchars($product['main_image']) ?>" alt="<?= htmlspecialchars($product['title']) ?>">
                </a>

                <div class="product-details">
                    <span class="product-brand"><?= htmlspecialchars($product['brand']) ?></span>
                    <h3 class="product-title">
                        <a href="product-detail.php?id=<?= $product['id'] ?>">
                            <?= htmlspecialchars($product['title']) ?>
                        </a>
                    </h3>

                    <div class="product-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                        <span><?= number_format($product['rating'], 1) ?></span>
                        <span class="rating-count">(<?= $product['reviews_count'] ?>)</span>
                    </div>

                    <!-- Color Swatches -->
                    <div class="color-swatches">
                        <span class="swatch-item active" style="background-color: #39FF14;" data-img="<?= htmlspecialchars($product['main_image']) ?>" title="Volt Green"></span>
                        <?php if (!empty($product['secondary_image'])): ?>
                            <span class="swatch-item" style="background-color: #0077BE;" data-img="<?= htmlspecialchars($product['secondary_image']) ?>" title="Ocean Blue"></span>
                        <?php endif; ?>
                        <span class="swatch-item" style="background-color: #111;" data-img="<?= htmlspecialchars($product['main_image']) ?>" title="Stealth Black"></span>
                    </div>

                    <div class="product-price-row">
                        <div class="price-box">
                            <span class="current-price">$<?= number_format($product['sale_price'] ?? $product['price'], 2) ?></span>
                            <?php if (!empty($product['sale_price'])): ?>
                                <span class="old-price">$<?= number_format($product['price'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                        <button class="btn-add-cart" data-product-id="<?= $product['id'] ?>" title="Add to Cart">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- COMMUNITY SIGN-UP SECTION -->
<div class="community-section">
    <div class="community-text">
        <h2>JOIN THE SPRINT GEAR CLUB</h2>
        <p>Subscribe for exclusive gear drops, athlete stories, and get <strong>10% OFF</strong> your first order.</p>
    </div>
    <form class="subscribe-form">
        <input type="email" class="subscribe-input" placeholder="Enter your email address" required>
        <button type="submit" class="btn-subscribe">JOIN NOW</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>