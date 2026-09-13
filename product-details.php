<?php
// product-details.php - Dedicated Product Details & Variant Shopping Page
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch main product details
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug, sc.name as subcategory_name, sc.slug as subcategory_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN subcategories sc ON p.subcategory_id = sc.id
    WHERE p.id = ? AND p.status = 'active'
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit;
}

$pageTitle = $product['name'] . " | Sprint Gear";

// Fetch additional product images
$stmtImgs = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY display_order ASC");
$stmtImgs->execute([$productId]);
$additionalImages = $stmtImgs->fetchAll(PDO::FETCH_COLUMN);
$allGalleryImages = array_merge([$product['main_image']], $additionalImages);

// Fetch all Product Variants with Color and Size metadata
$stmtVars = $pdo->prepare("
    SELECT pv.*, c.name as color_name, c.hex_code, s.name as size_name, s.category_type as size_category
    FROM product_variants pv
    JOIN colors c ON pv.color_id = c.id
    JOIN sizes s ON pv.size_id = s.id
    WHERE pv.product_id = ?
    ORDER BY c.name ASC, s.id ASC
");
$stmtVars->execute([$productId]);
$variants = $stmtVars->fetchAll();

// Group distinct colors and sizes for selector UI
$distinctColors = [];
$distinctSizes = [];
foreach ($variants as $v) {
    if (!isset($distinctColors[$v['color_id']])) {
        $distinctColors[$v['color_id']] = [
            'id' => $v['color_id'],
            'name' => $v['color_name'],
            'hex' => $v['hex_code']
        ];
    }
    if (!isset($distinctSizes[$v['size_id']])) {
        $distinctSizes[$v['size_id']] = [
            'id' => $v['size_id'],
            'name' => $v['size_name'],
            'type' => $v['size_category']
        ];
    }
}

// Process Review Submission
$reviewSuccess = false;
$reviewError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $user = getLoggedInUser();
    if (!$user) {
        $reviewError = "Please log in to submit a review.";
    } else {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
        $comment = sanitize($_POST['comment'] ?? '');

        if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
            $stmtRevIns = $pdo->prepare("INSERT INTO reviews (product_id, user_id, user_name, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $stmtRevIns->execute([$productId, $user['id'], $user['name'], $rating, $comment]);

            // Update average rating and reviews count
            $stmtAvg = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total_revs FROM reviews WHERE product_id = ?");
            $stmtAvg->execute([$productId]);
            $stats = $stmtAvg->fetch();

            $stmtUpdProd = $pdo->prepare("UPDATE products SET rating = ?, reviews_count = ? WHERE id = ?");
            $stmtUpdProd->execute([$stats['avg_rating'], $stats['total_revs'], $productId]);

            setFlash('success', 'Thank you! Your review has been submitted.');
            header("Location: product-details.php?id=$productId#reviews");
            exit;
        } else {
            $reviewError = "Please fill in all review fields.";
        }
    }
}

// Fetch Reviews for this product
$stmtRevs = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC");
$stmtRevs->execute([$productId]);
$reviews = $stmtRevs->fetchAll();

// Fetch Related Products (same category or subcategory, excluding current)
$stmtRelated = $pdo->prepare("
    SELECT * FROM products 
    WHERE category_id = ? AND id != ? AND status = 'active' 
    ORDER BY RAND() LIMIT 4
");
$stmtRelated->execute([$product['category_id'], $productId]);
$relatedProducts = $stmtRelated->fetchAll();

// Fetch Latest Products
$stmtLatest = $pdo->query("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 4");
$latestProducts = $stmtLatest->fetchAll();

// Process Add to Cart POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $variantId = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($variantId > 0) {
        $res = addToCart($productId, $variantId, $quantity);
        if ($res['success']) {
            setFlash('success', $res['message']);
            if (isset($_POST['is_buy_now']) && $_POST['is_buy_now'] == '1') {
                header("Location: checkout.php");
                exit;
            }
        } else {
            setFlash('error', $res['message']);
        }
    } else {
        setFlash('error', 'Please select a valid color and size combination.');
    }
    header("Location: product-details.php?id=$productId");
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- BREADCRUMB NAV -->
<div class="page-header" style="padding: 1.5rem 0; margin-bottom: 2rem;">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="index.php">Home</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="products.php?category=<?php echo urlencode($product['category_name']); ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
            <li class="active"><?php echo htmlspecialchars($product['name']); ?></li>
        </ul>
    </div>
</div>

<div class="product-details-container container">
    <!-- LEFT SIDE: IMAGE GALLERY -->
    <div class="product-gallery">
        <div class="main-image-box">
            <img id="mainProductImage" src="<?php echo htmlspecialchars($product['main_image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="main-img">
        </div>

        <!-- Gallery Thumbnails & Variant Images -->
        <div class="gallery-thumbs">
            <?php foreach ($allGalleryImages as $idx => $imgUrl): ?>
                <button type="button" class="gallery-thumb <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="changeGalleryImage('<?php echo htmlspecialchars($imgUrl); ?>', this)">
                    <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Thumbnail">
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- RIGHT SIDE: PRODUCT FORM & SELECTION -->
    <div class="product-info-panel">
        <span class="product-brand-tag"><?php echo htmlspecialchars($product['brand']); ?></span>
        <h1 class="product-title-large"><?php echo htmlspecialchars($product['name']); ?></h1>

        <!-- Rating & Stock Badge -->
        <div class="product-meta-row">
            <div class="product-rating">
                <?php echo renderStarRating($product['rating'], $product['reviews_count']); ?>
                <a href="#reviews" class="review-link" onclick="switchTab('reviews', document.querySelector('.tab-link[onclick*=\"reviews\"]'))">Read Customer Reviews</a>
            </div>
            <span id="variantStockBadge" class="stock-badge badge-in">Select Options</span>
        </div>

        <!-- Pricing Row -->
        <div class="product-price-section">
            <?php if (!empty($product['discount_price'])): ?>
                <span class="price-discounted"><?php echo formatPrice($product['discount_price']); ?></span>
                <span class="price-original"><?php echo formatPrice($product['price']); ?></span>
                <span class="discount-pill">
                    Save <?php echo round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>%
                </span>
            <?php else: ?>
                <span class="price-discounted"><?php echo formatPrice($product['price']); ?></span>
            <?php endif; ?>
        </div>

        <!-- Short Description -->
        <p class="product-short-desc"><?php echo htmlspecialchars($product['short_description']); ?></p>

        <!-- ADD TO CART & VARIANT FORM -->
        <form action="product-details.php?id=<?php echo $productId; ?>" method="POST" id="addToCartForm">
            <input type="hidden" name="action" value="add_to_cart">
            <input type="hidden" name="variant_id" id="selectedVariantId" value="">

            <!-- COLOR SELECTION SWATCHES -->
            <div class="variant-picker-group">
                <label class="picker-label">Color: <span id="selectedColorName" class="font-bold text-secondary"></span></label>
                <div class="color-swatch-picker">
                    <?php 
                    $firstCol = true;
                    foreach ($distinctColors as $c): 
                    ?>
                        <label class="color-swatch-option" title="<?php echo htmlspecialchars($c['name']); ?>">
                            <input type="radio" name="selected_color" value="<?php echo $c['id']; ?>" <?php echo $firstCol ? 'checked' : ''; ?>>
                            <span class="swatch-circle" style="background-color: <?php echo htmlspecialchars($c['hex']); ?>;"></span>
                        </label>
                    <?php 
                        $firstCol = false;
                    endforeach; 
                    ?>
                </div>
            </div>

            <!-- SIZE SELECTION BUTTONS -->
            <div class="variant-picker-group">
                <div class="picker-label-row">
                    <label class="picker-label">Size:</label>
                    <button type="button" class="btn-size-guide-trigger" onclick="openSizeGuideModal('<?php echo htmlspecialchars($product['subcategory_name']); ?>')">
                        <i class="fas fa-ruler"></i> SIZE GUIDE
                    </button>
                </div>
                <div class="size-picker">
                    <?php foreach ($distinctSizes as $s): ?>
                        <label class="size-option">
                            <input type="radio" name="selected_size" value="<?php echo $s['id']; ?>">
                            <span class="size-box"><?php echo htmlspecialchars($s['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- QUANTITY & CTA BUTTONS -->
            <div class="purchase-actions-row">
                <div class="quantity-control">
                    <button type="button" class="qty-btn" onclick="adjustQuantity(-1)">-</button>
                    <input type="number" name="quantity" id="productQuantity" value="1" min="1" max="50" class="qty-input">
                    <button type="button" class="qty-btn" onclick="adjustQuantity(1)">+</button>
                </div>

                <button type="submit" id="addToCartBtn" class="btn-add-cart">
                    <i class="fas fa-shopping-bag"></i> ADD TO CART
                </button>
                
                <button type="submit" id="buyNowBtn" onclick="document.getElementById('isBuyNow').value='1';" class="btn-buy-now">
                    BUY NOW
                </button>
                <input type="hidden" name="is_buy_now" id="isBuyNow" value="0">

                <button type="button" class="btn-wishlist-detail <?php echo isInWishlist($productId) ? 'active' : ''; ?>" onclick="toggleWishlistAction(<?php echo $productId; ?>, this)">
                    <i class="<?php echo isInWishlist($productId) ? 'fas fa-heart' : 'far fa-heart'; ?>"></i>
                </button>
            </div>
        </form>

        <!-- SHIPPING SUMMARY BADGES -->
        <div class="shipping-perks-box">
            <div class="perk-item">
                <i class="fas fa-shipping-fast text-red-500"></i>
                <div>
                    <strong>Islandwide Fast Delivery</strong>
                    <p>Delivered within 2 - 4 business days</p>
                </div>
            </div>
            <div class="perk-item">
                <i class="fas fa-undo-alt text-red-500"></i>
                <div>
                    <strong>Easy 14-Day Returns</strong>
                    <p>Hassle-free size exchange policy</p>
                </div>
            </div>
            <div class="perk-item">
                <i class="fas fa-shield-alt text-red-500"></i>
                <div>
                    <strong>100% Authentic Guarantee</strong>
                    <p>Genuine brand performance activewear</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DETAILED TABS SECTION -->
<section class="product-tabs-section container">
    <div class="product-detail-tabs">
        <button class="tab-link active" onclick="switchTab('description', this)">Description</button>
        <button class="tab-link" onclick="switchTab('additional', this)">Additional Information</button>
        <button class="tab-link" onclick="switchTab('reviews', this)">Customer Reviews (<?php echo count($reviews); ?>)</button>
        <button class="tab-link" onclick="switchTab('shipping', this)">Shipping & Returns</button>
    </div>

    <!-- 1. Description Tab -->
    <div id="tab-description" class="tab-content active">
        <h3 class="tab-heading">Product Overview</h3>
        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        <h4 style="margin-top:1.5rem; font-family:var(--font-heading);">Key Features & Benefits</h4>
        <ul style="list-style:disc; padding-left:1.5rem; margin-top:0.5rem; color:var(--text-muted);">
            <li>Engineered specifically for high-intensity <?php echo htmlspecialchars($product['category_name']); ?> performance.</li>
            <li>Premium <?php echo htmlspecialchars($product['material']); ?> material ensures breathability and durability.</li>
            <li>Ergonomic athletic fit designed to prevent chafing during long workouts.</li>
            <li>Manufactured under strict sports performance standards in <?php echo htmlspecialchars($product['country_of_manufacture']); ?>.</li>
        </ul>
    </div>

    <!-- 2. Additional Information Tab -->
    <div id="tab-additional" class="tab-content" style="display:none;">
        <table class="info-table">
            <tr><th>Brand</th><td><?php echo htmlspecialchars($product['brand']); ?></td></tr>
            <tr><th>Gender</th><td><?php echo htmlspecialchars($product['gender']); ?></td></tr>
            <tr><th>Category</th><td><?php echo htmlspecialchars($product['category_name']); ?></td></tr>
            <tr><th>Subcategory</th><td><?php echo htmlspecialchars($product['subcategory_name']); ?></td></tr>
            <tr><th>Material</th><td><?php echo htmlspecialchars($product['material']); ?></td></tr>
            <tr><th>Sport Type</th><td><?php echo htmlspecialchars($product['sport_type']); ?></td></tr>
            <tr><th>Country of Manufacture</th><td><?php echo htmlspecialchars($product['country_of_manufacture']); ?></td></tr>
            <tr><th>Base SKU</th><td><?php echo htmlspecialchars($product['sku']); ?></td></tr>
        </table>
    </div>

    <!-- 3. Reviews Tab -->
    <div id="tab-reviews" class="tab-content" style="display:none;">
        <div class="reviews-layout">
            <div class="reviews-summary-card">
                <div class="big-score"><?php echo number_format($product['rating'], 1); ?></div>
                <div><?php echo renderStarRating($product['rating']); ?></div>
                <p>Based on <?php echo count($reviews); ?> customer reviews</p>
            </div>

            <!-- Write Review Form -->
            <div class="write-review-card">
                <h4>Write a Product Review</h4>
                <?php if (isLoggedIn()): ?>
                    <?php if ($reviewError): ?>
                        <div class="alert-error"><?php echo htmlspecialchars($reviewError); ?></div>
                    <?php endif; ?>
                    <form action="product-details.php?id=<?php echo $productId; ?>" method="POST">
                        <input type="hidden" name="action" value="submit_review">
                        <div class="form-group">
                            <label>Rating:</label>
                            <select name="rating" class="form-control" required>
                                <option value="5">★★★★★ (5/5) Excellent</option>
                                <option value="4">★★★★☆ (4/5) Very Good</option>
                                <option value="3">★★★☆☆ (3/5) Average</option>
                                <option value="2">★★☆☆☆ (2/5) Poor</option>
                                <option value="1">★☆☆☆☆ (1/5) Terrible</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Your Review Comment:</label>
                            <textarea name="comment" rows="3" class="form-control" placeholder="Share details about fit, comfort, and athletic performance..." required></textarea>
                        </div>
                        <button type="submit" class="btn-submit-review">Submit Review</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Please <a href="login.php" class="text-red-500 font-bold">log in</a> to write a review for this product.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Customer Reviews List -->
        <div class="reviews-list">
            <?php foreach ($reviews as $rev): ?>
                <div class="review-item">
                    <div class="review-meta">
                        <strong><?php echo htmlspecialchars($rev['user_name']); ?></strong>
                        <span class="review-date"><?php echo date('F j, Y', strtotime($rev['created_at'])); ?></span>
                    </div>
                    <div class="review-stars"><?php echo renderStarRating($rev['rating']); ?></div>
                    <p class="review-text"><?php echo htmlspecialchars($rev['comment']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 4. Shipping & Returns Tab -->
    <div id="tab-shipping" class="tab-content" style="display:none;">
        <h3>Shipping & Delivery Policy</h3>
        <p>Sprint Gear delivers orders islandwide across Sri Lanka using trusted express courier partners.</p>
        <ul style="list-style:disc; padding-left:1.5rem; margin:1rem 0; color:var(--text-muted);">
            <li><strong>Colombo & Suburbs:</strong> Standard delivery within 1 - 2 business days (Rs. 350.00).</li>
            <li><strong>Outstation Districts:</strong> Standard delivery within 2 - 4 business days (Rs. 500.00).</li>
            <li><strong>Cash on Delivery (COD):</strong> Available for all Islandwide locations.</li>
        </ul>
        <h3 style="margin-top:1.5rem;">Return & Exchange Policy</h3>
        <p>If your shoes or apparel size does not fit perfectly, Sprint Gear offers a 14-day hassle-free exchange policy provided items are unworn and retain original tags.</p>
    </div>
</section>

<!-- RELATED PRODUCTS SECTION -->
<section class="products-section bg-light" style="padding-top:3rem;">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">YOU MAY ALSO LIKE</h2>
        </div>
        <div class="products-horizontal-row">
            <?php foreach ($relatedProducts as $product): ?>
                <?php include __DIR__ . '/includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Pass JSON Variants Data to JavaScript Engine -->
<script>
window.productVariantsData = <?php echo json_encode($variants); ?>;
</script>
<script src="assets/js/product-details.js"></script>

<style>
.product-details-container { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-bottom: 4rem; }

.product-gallery { display: flex; flex-direction: column; gap: 1rem; }
.main-image-box { background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; position: relative; padding-top: 100%; }
.main-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.gallery-thumbs { display: flex; gap: 0.75rem; overflow-x: auto; }
.gallery-thumb { width: 70px; height: 70px; border: 2px solid var(--border-color); border-radius: var(--radius-sm); overflow: hidden; background: white; cursor: pointer; padding: 0; }
.gallery-thumb.active { border-color: var(--primary-red); }

.product-brand-tag { font-family: var(--font-heading); font-weight: 800; font-size: 0.85rem; color: var(--primary-red); text-transform: uppercase; letter-spacing: 1px; }
.product-title-large { font-family: var(--font-heading); font-size: 2.2rem; font-weight: 900; color: var(--secondary-dark); line-height: 1.2; margin: 0.25rem 0 0.75rem; }

.product-meta-row { display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); margin-bottom: 1.25rem; }
.review-link { font-size: 0.8rem; color: var(--text-muted); text-decoration: underline; margin-left: 0.5rem; }

.stock-badge { font-family: var(--font-heading); font-weight: 800; font-size: 0.75rem; padding: 0.3rem 0.75rem; border-radius: 20px; }
.badge-in { background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; }
.badge-out { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }

.product-price-section { display: flex; align-items: baseline; gap: 1rem; margin-bottom: 1.25rem; }
.price-discounted { font-family: var(--font-heading); font-size: 2rem; font-weight: 900; color: var(--secondary-dark); }
.price-original { font-size: 1.2rem; color: var(--text-light); text-decoration: line-through; }
.discount-pill { background: var(--primary-red); color: white; font-family: var(--font-heading); font-weight: 800; font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 4px; }

.product-short-desc { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem; line-height: 1.6; }

.variant-picker-group { margin-bottom: 1.5rem; }
.picker-label-row { display: flex; justify-content: space-between; align-items: center; }
.picker-label { font-family: var(--font-heading); font-weight: 800; font-size: 0.9rem; text-transform: uppercase; color: var(--secondary-dark); }

.color-swatch-picker { display: flex; gap: 10px; margin-top: 0.5rem; }
.color-swatch-option input { display: none; }
.swatch-circle { width: 32px; height: 32px; border-radius: 50%; border: 2px solid #CBD5E1; display: inline-block; cursor: pointer; transition: var(--transition-fast); }
.color-swatch-option input:checked + .swatch-circle { border-color: var(--primary-red); transform: scale(1.15); box-shadow: 0 0 0 3px rgba(239,68,68,0.25); }

.btn-size-guide-trigger { background: none; border: none; font-family: var(--font-heading); font-weight: 700; font-size: 0.8rem; color: var(--primary-red); cursor: pointer; }

.size-picker { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 0.5rem; }
.size-option input { display: none; }
.size-box { display: inline-block; min-width: 48px; padding: 0.6rem 0.9rem; text-align: center; border: 1.5px solid var(--border-color); border-radius: var(--radius-sm); font-family: var(--font-heading); font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: var(--transition-fast); }
.size-option input:checked + .size-box { background: var(--secondary-dark); color: white; border-color: var(--secondary-dark); }
.size-option.disabled { opacity: 0.35; cursor: not-allowed; text-decoration: line-through; }
.size-option.disabled input { pointer-events: none; }

.purchase-actions-row { display: flex; gap: 0.75rem; align-items: center; margin-top: 2rem; }
.quantity-control { display: flex; border: 1px solid var(--border-color); border-radius: var(--radius-sm); overflow: hidden; }
.qty-btn { background: #F1F5F9; border: none; width: 36px; height: 44px; font-weight: 800; cursor: pointer; }
.qty-input { width: 44px; height: 44px; border: none; text-align: center; font-weight: 700; outline: none; }

.btn-add-cart { flex: 2; background: var(--primary-red); color: white; border: none; height: 44px; border-radius: var(--radius-sm); font-family: var(--font-heading); font-weight: 800; font-size: 0.9rem; letter-spacing: 0.5px; cursor: pointer; }
.btn-add-cart:hover { background: var(--primary-red-hover); }
.btn-add-cart:disabled { background: #CBD5E1; cursor: not-allowed; }

.btn-buy-now { flex: 1.5; background: var(--secondary-dark); color: white; border: none; height: 44px; border-radius: var(--radius-sm); font-family: var(--font-heading); font-weight: 800; font-size: 0.9rem; cursor: pointer; }
.btn-wishlist-detail { width: 44px; height: 44px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: white; color: var(--text-muted); font-size: 1.2rem; cursor: pointer; }
.btn-wishlist-detail.active { color: var(--primary-red); background: #FEF2F2; border-color: var(--primary-red); }

.shipping-perks-box { margin-top: 2rem; background: #F8FAFC; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; }
.perk-item { display: flex; gap: 1rem; align-items: center; font-size: 0.85rem; }
.perk-item i { font-size: 1.4rem; }

/* TABS STYLES */
.product-tabs-section { margin-bottom: 4rem; }
.product-detail-tabs { display: flex; gap: 1rem; border-bottom: 2px solid var(--border-color); margin-bottom: 2rem; }
.tab-link { background: none; border: none; padding: 0.75rem 1.5rem; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: var(--text-muted); cursor: pointer; }
.tab-link.active { color: var(--primary-red); border-bottom: 3px solid var(--primary-red); margin-bottom: -2px; }

.tab-content { background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 2rem; }
.tab-heading { font-family: var(--font-heading); font-size: 1.4rem; font-weight: 800; margin-bottom: 1rem; }

.info-table { width: 100%; border-collapse: collapse; }
.info-table th, .info-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color); text-align: left; }
.info-table th { width: 220px; font-family: var(--font-heading); font-weight: 700; color: var(--secondary-dark); background: #F8FAFC; }

.reviews-layout { display: grid; grid-template-columns: 240px 1fr; gap: 2rem; margin-bottom: 2rem; }
.reviews-summary-card { background: #F8FAFC; border-radius: var(--radius-md); padding: 1.5rem; text-align: center; }
.big-score { font-family: var(--font-heading); font-size: 3.5rem; font-weight: 900; color: var(--secondary-dark); line-height: 1; margin-bottom: 0.5rem; }

.write-review-card { background: #F8FAFC; border-radius: var(--radius-md); padding: 1.5rem; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.3rem; }
.form-control { width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 6px; }
.btn-submit-review { background: var(--primary-red); color: white; border: none; padding: 0.6rem 1.5rem; font-family: var(--font-heading); font-weight: 800; border-radius: 4px; cursor: pointer; }

.reviews-list { display: flex; flex-direction: column; gap: 1rem; }
.review-item { border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; }
.review-meta { display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.3rem; }
.review-date { color: var(--text-light); font-size: 0.8rem; }

@media (max-width: 900px) {
    .product-details-container { grid-template-columns: 1fr; }
    .reviews-layout { grid-template-columns: 1fr; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
