<?php
// includes/product-card.php - Reusable Product Card Component
// Expects variable $product array to be passed in context

if (!isset($product) || empty($product)) {
    return;
}

$productId = $product['id'];
$productName = htmlspecialchars($product['name']);
$productBrand = htmlspecialchars($product['brand']);
$productImage = htmlspecialchars($product['main_image']);
$productPrice = (float)$product['price'];
$productDiscountPrice = !empty($product['discount_price']) ? (float)$product['discount_price'] : null;
$productRating = (float)($product['rating'] ?? 4.5);
$reviewsCount = (int)($product['reviews_count'] ?? 12);
$isWishlisted = isInWishlist($productId);

// Calculate discount percentage
$discountBadge = null;
if ($productDiscountPrice && $productDiscountPrice < $productPrice) {
    $savings = (($productPrice - $productDiscountPrice) / $productPrice) * 100;
    $discountBadge = '-' . round($savings) . '%';
}

// Fetch available colors for swatches
$pdo = getDBConnection();
$stmtColors = $pdo->prepare("
    SELECT DISTINCT c.name, c.hex_code 
    FROM product_variants pv 
    JOIN colors c ON pv.color_id = c.id 
    WHERE pv.product_id = ? 
    LIMIT 4
");
$stmtColors->execute([$productId]);
$swatchColors = $stmtColors->fetchAll();
?>

<div class="product-card" data-product-id="<?php echo $productId; ?>">
    <div class="product-card-badge-container">
        <?php if ($discountBadge): ?>
            <span class="product-badge badge-discount"><?php echo $discountBadge; ?></span>
        <?php endif; ?>
        <?php if (!empty($product['is_featured'])): ?>
            <span class="product-badge badge-featured">FEATURED</span>
        <?php endif; ?>
    </div>

    <!-- Wishlist Button -->
    <button type="button" 
            class="wishlist-toggle-btn <?php echo $isWishlisted ? 'active' : ''; ?>" 
            onclick="toggleWishlistAction(<?php echo $productId; ?>, this)"
            title="<?php echo $isWishlisted ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
        <i class="<?php echo $isWishlisted ? 'fas fa-heart' : 'far fa-heart'; ?>"></i>
    </button>

    <!-- Product Image -->
    <a href="product-details.php?id=<?php echo $productId; ?>" class="product-image-wrapper">
        <img src="<?php echo $productImage; ?>" alt="<?php echo $productName; ?>" class="product-card-img" loading="lazy">
    </a>

    <!-- Product Content -->
    <div class="product-card-body">
        <div class="product-brand"><?php echo $productBrand; ?></div>
        
        <h3 class="product-title">
            <a href="product-details.php?id=<?php echo $productId; ?>"><?php echo $productName; ?></a>
        </h3>

        <!-- Star Rating -->
        <div class="product-rating">
            <?php echo renderStarRating($productRating, $reviewsCount); ?>
        </div>

        <!-- Color Swatches -->
        <?php if (!empty($swatchColors)): ?>
            <div class="color-swatches">
                <?php foreach ($swatchColors as $c): ?>
                    <span class="color-swatch-dot" 
                          style="background-color: <?php echo htmlspecialchars($c['hex_code']); ?>;" 
                          title="<?php echo htmlspecialchars($c['name']); ?>"></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Price Section -->
        <div class="product-price-row">
            <?php if ($productDiscountPrice): ?>
                <span class="current-price"><?php echo formatPrice($productDiscountPrice); ?></span>
                <span class="original-price"><?php echo formatPrice($productPrice); ?></span>
            <?php else: ?>
                <span class="current-price"><?php echo formatPrice($productPrice); ?></span>
            <?php endif; ?>
        </div>

        <!-- Action Button -->
        <a href="product-details.php?id=<?php echo $productId; ?>" class="btn-card-action">
            <i class="fas fa-shopping-cart"></i> SELECT OPTIONS
        </a>
    </div>
</div>
