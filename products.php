<?php
// products.php - Product Catalog & Combined Multi-Filter Engine
$pageTitle = "Products Catalog";
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Extract & sanitize query parameters
$searchParam = sanitize($_GET['search'] ?? '');
$categoryParam = sanitize($_GET['category'] ?? '');
$subcategoryParam = sanitize($_GET['subcategory'] ?? '');
$genderParam = sanitize($_GET['gender'] ?? '');
$brandParams = (array)($_GET['brand'] ?? []);
$colorParams = (array)($_GET['color'] ?? []);
$sizeParams = (array)($_GET['size'] ?? []);
$minPriceParam = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$maxPriceParam = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$inStockOnly = !empty($_GET['in_stock']);
$sortParam = sanitize($_GET['sort'] ?? 'latest');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build Dynamic SQL Query with Prepared Statements
$whereConditions = ["p.status = 'active'"];
$params = [];

// 1. Search filter (Name, Brand, Category, Subcategory, Description)
if (!empty($searchParam)) {
    $whereConditions[] = "(p.name LIKE ? OR p.brand LIKE ? OR c.name LIKE ? OR sc.name LIKE ? OR p.description LIKE ?)";
    $searchTerm = '%' . $searchParam . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// 2. Category filter
if (!empty($categoryParam)) {
    $whereConditions[] = "c.name = ?";
    $params[] = $categoryParam;
}

// 3. Subcategory filter
if (!empty($subcategoryParam)) {
    $whereConditions[] = "sc.name = ?";
    $params[] = $subcategoryParam;
}

// 4. Gender filter
if (!empty($genderParam)) {
    $whereConditions[] = "p.gender = ?";
    $params[] = $genderParam;
}

// 5. Brand filter
if (!empty($brandParams)) {
    $brandPlaceholders = implode(',', array_fill(0, count($brandParams), '?'));
    $whereConditions[] = "p.brand IN ($brandPlaceholders)";
    foreach ($brandParams as $b) $params[] = $b;
}

// 6. Price Range filter
if ($minPriceParam !== null) {
    $whereConditions[] = "COALESCE(p.discount_price, p.price) >= ?";
    $params[] = $minPriceParam;
}
if ($maxPriceParam !== null) {
    $whereConditions[] = "COALESCE(p.discount_price, p.price) <= ?";
    $params[] = $maxPriceParam;
}

// 7. Color / Size / Stock filters via subqueries
if (!empty($colorParams)) {
    $colorPlaceholders = implode(',', array_fill(0, count($colorParams), '?'));
    $whereConditions[] = "p.id IN (SELECT pv.product_id FROM product_variants pv JOIN colors col ON pv.color_id = col.id WHERE col.name IN ($colorPlaceholders))";
    foreach ($colorParams as $col) $params[] = $col;
}

if (!empty($sizeParams)) {
    $sizePlaceholders = implode(',', array_fill(0, count($sizeParams), '?'));
    $whereConditions[] = "p.id IN (SELECT pv.product_id FROM product_variants pv JOIN sizes sz ON pv.size_id = sz.id WHERE sz.name IN ($sizePlaceholders))";
    foreach ($sizeParams as $sz) $params[] = $sz;
}

if ($inStockOnly) {
    $whereConditions[] = "p.id IN (SELECT pv.product_id FROM product_variants pv WHERE pv.stock > 0)";
}

$whereSQL = implode(" AND ", $whereConditions);

// Order By Clause
$orderBySQL = "p.created_at DESC";
if ($sortParam === 'price_asc') $orderBySQL = "COALESCE(p.discount_price, p.price) ASC";
if ($sortParam === 'price_desc') $orderBySQL = "COALESCE(p.discount_price, p.price) DESC";
if ($sortParam === 'name_asc') $orderBySQL = "p.name ASC";
if ($sortParam === 'name_desc') $orderBySQL = "p.name DESC";
if ($sortParam === 'popular') $orderBySQL = "p.rating DESC, p.reviews_count DESC";

// Get Total Count for Pagination
$countSQL = "SELECT COUNT(DISTINCT p.id) as total 
             FROM products p 
             JOIN categories c ON p.category_id = c.id 
             JOIN subcategories sc ON p.subcategory_id = sc.id 
             WHERE $whereSQL";
$stmtCount = $pdo->prepare($countSQL);
$stmtCount->execute($params);
$totalProducts = (int)$stmtCount->fetch()['total'];
$totalPages = ceil($totalProducts / $limit);

// Fetch Products for Current Page
$productsSQL = "SELECT DISTINCT p.*, c.name as category_name, sc.name as subcategory_name 
               FROM products p 
               JOIN categories c ON p.category_id = c.id 
               JOIN subcategories sc ON p.subcategory_id = sc.id 
               WHERE $whereSQL 
               ORDER BY $orderBySQL 
               LIMIT $limit OFFSET $offset";
$stmtProd = $pdo->prepare($productsSQL);
$stmtProd->execute($params);
$products = $stmtProd->fetchAll();

// Fetch Metadata for Filter Sidebar (Categories, Brands, Colors, Sizes)
$allCategories = getNavigationCategories();

$activeCategoryData = null;
if (!empty($categoryParam)) {
    $stmtCatInfo = $pdo->prepare("SELECT * FROM categories WHERE name = ? LIMIT 1");
    $stmtCatInfo->execute([$categoryParam]);
    $activeCategoryData = $stmtCatInfo->fetch();
}

$allBrands = $pdo->query("SELECT DISTINCT brand FROM products ORDER BY brand ASC")->fetchAll(PDO::FETCH_COLUMN);
$allColors = $pdo->query("SELECT * FROM colors ORDER BY name ASC")->fetchAll();
$allSizes = $pdo->query("SELECT * FROM sizes ORDER BY id ASC")->fetchAll();
?>

<!-- BREADCRUMB HEADER -->
<div class="page-header">
    <div class="container">
        <h1 class="page-title">
            <?php 
            if (!empty($searchParam)) echo "Search Results for \"" . htmlspecialchars($searchParam) . "\"";
            else if (!empty($subcategoryParam)) echo htmlspecialchars($subcategoryParam);
            else if (!empty($categoryParam)) echo htmlspecialchars($categoryParam);
            else echo "All Products Catalog";
            ?>
        </h1>
        <ul class="breadcrumb">
            <li><a href="index.php">Home</a></li>
            <li class="active">Products</li>
            <?php if (!empty($categoryParam)): ?><li><?php echo htmlspecialchars($categoryParam); ?></li><?php endif; ?>
            <?php if (!empty($subcategoryParam)): ?><li><?php echo htmlspecialchars($subcategoryParam); ?></li><?php endif; ?>
        </ul>
    </div>
</div>

<div class="products-page-container container">
    <!-- FILTER SIDEBAR -->
    <aside class="filter-sidebar">
        <form action="products.php" method="GET" id="filterForm">
            <!-- Preserve search if active -->
            <?php if (!empty($searchParam)): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchParam); ?>">
            <?php endif; ?>

            <div class="sidebar-header">
                <h3><i class="fas fa-filter text-red-500"></i> FILTERS</h3>
                <a href="products.php" class="btn-clear-filters">CLEAR ALL</a>
            </div>

            <!-- 1. Category Filter -->
            <div class="filter-group">
                <h4 class="filter-title">Category</h4>
                <div class="filter-options">
                    <label class="filter-radio">
                        <input type="radio" name="category" value="" <?php echo empty($categoryParam) ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span>All Categories</span>
                    </label>
                    <?php foreach ($allCategories as $cat): ?>
                        <label class="filter-radio">
                            <input type="radio" name="category" value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo $categoryParam === $cat['name'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span><?php echo htmlspecialchars($cat['name']); ?></span>
                        </label>

                        <!-- Dynamic Subcategories if Category Selected -->
                        <?php if ($categoryParam === $cat['name'] && !empty($cat['subcategories'])): ?>
                            <div class="subcategory-indent">
                                <?php foreach ($cat['subcategories'] as $sub): ?>
                                    <label class="filter-radio sub-radio">
                                        <input type="radio" name="subcategory" value="<?php echo htmlspecialchars($sub['name']); ?>" <?php echo $subcategoryParam === $sub['name'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                        <span><?php echo htmlspecialchars($sub['name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 2. Gender Filter -->
            <div class="filter-group">
                <h4 class="filter-title">Gender</h4>
                <div class="filter-options">
                    <?php foreach (['Male', 'Female', 'Kids', 'Unisex'] as $g): ?>
                        <label class="filter-radio">
                            <input type="radio" name="gender" value="<?php echo $g; ?>" <?php echo $genderParam === $g ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span><?php echo $g; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Brand Filter -->
            <div class="filter-group">
                <h4 class="filter-title">Brands</h4>
                <div class="filter-options">
                    <?php foreach ($allBrands as $b): ?>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="brand[]" value="<?php echo htmlspecialchars($b); ?>" <?php echo in_array($b, $brandParams) ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span><?php echo htmlspecialchars($b); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 4. Color Swatches Filter -->
            <div class="filter-group">
                <h4 class="filter-title">Colors</h4>
                <div class="color-filter-grid">
                    <?php foreach ($allColors as $c): ?>
                        <label class="color-checkbox-dot" title="<?php echo htmlspecialchars($c['name']); ?>">
                            <input type="checkbox" name="color[]" value="<?php echo htmlspecialchars($c['name']); ?>" <?php echo in_array($c['name'], $colorParams) ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span class="dot-swatch" style="background-color: <?php echo htmlspecialchars($c['hex_code']); ?>;"></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 5. Size Filter -->
            <div class="filter-group">
                <h4 class="filter-title">Sizes</h4>
                <div class="size-filter-grid">
                    <?php foreach ($allSizes as $s): ?>
                        <label class="size-pill-checkbox">
                            <input type="checkbox" name="size[]" value="<?php echo htmlspecialchars($s['name']); ?>" <?php echo in_array($s['name'], $sizeParams) ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span><?php echo htmlspecialchars($s['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 6. Price Filter -->
            <div class="filter-group">
                <h4 class="filter-title">Price Range (Rs.)</h4>
                <div class="price-inputs">
                    <input type="number" name="min_price" placeholder="Min" value="<?php echo $minPriceParam; ?>" class="price-input">
                    <span>-</span>
                    <input type="number" name="max_price" placeholder="Max" value="<?php echo $maxPriceParam; ?>" class="price-input">
                </div>
                <button type="submit" class="btn-filter-apply">Apply Price</button>
            </div>

            <!-- 7. Availability -->
            <div class="filter-group">
                <label class="filter-checkbox">
                    <input type="checkbox" name="in_stock" value="1" <?php echo $inStockOnly ? 'checked' : ''; ?> onchange="this.form.submit()">
                    <span class="font-bold text-secondary">In Stock Only</span>
                </label>
            </div>
        </form>
    </aside>

    <!-- MAIN PRODUCT CATALOG AREA -->
    <main class="products-main">
        <?php if (!empty($activeCategoryData)): ?>
            <div class="category-banner-card" style="position:relative; height:180px; border-radius:12px; overflow:hidden; margin-bottom:1.5rem; display:flex; align-items:center; padding:2rem; box-shadow:var(--shadow-md);">
                <img src="<?php echo htmlspecialchars($activeCategoryData['image']); ?>" alt="<?php echo htmlspecialchars($activeCategoryData['name']); ?>" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:0.85;">
                <div style="position:absolute; inset:0; background:linear-gradient(90deg, rgba(15,23,42,0.92) 0%, rgba(15,23,42,0.55) 55%, rgba(15,23,42,0.15) 100%);"></div>
                <div style="position:relative; z-index:2; color:white;">
                    <span style="display:inline-flex; align-items:center; gap:0.5rem; background:var(--primary-red); color:white; padding:0.3rem 0.85rem; border-radius:4px; font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:0.5rem;">
                        <i class="fas <?php echo htmlspecialchars($activeCategoryData['icon']); ?>"></i> Sports Category
                    </span>
                    <h2 style="font-family:var(--font-heading); font-size:2rem; font-weight:900; margin:0; text-transform:uppercase; text-shadow:0 2px 8px rgba(0,0,0,0.5); color:#FFFFFF;">
                        <?php echo htmlspecialchars($activeCategoryData['name']); ?>
                    </h2>
                </div>
            </div>
        <?php endif; ?>

        <!-- TOP TOOLBAR -->
        <div class="products-toolbar">
            <div class="toolbar-info">
                <span>Showing <strong><?php echo min($totalProducts, $offset + 1); ?> - <?php echo min($totalProducts, $offset + count($products)); ?></strong> of <strong><?php echo $totalProducts; ?></strong> products</span>
            </div>

            <!-- Sorting Selector -->
            <div class="toolbar-sort">
                <label for="sortSelect">Sort By:</label>
                <select id="sortSelect" onchange="applySorting(this.value)">
                    <option value="latest" <?php echo $sortParam === 'latest' ? 'selected' : ''; ?>>Latest Arrivals</option>
                    <option value="price_asc" <?php echo $sortParam === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo $sortParam === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="name_asc" <?php echo $sortParam === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                    <option value="name_desc" <?php echo $sortParam === 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                    <option value="popular" <?php echo $sortParam === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                </select>
            </div>
        </div>

        <!-- PRODUCTS GRID -->
        <?php if (!empty($products)): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <?php include __DIR__ . '/includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>

            <!-- PAGINATION -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="page-link <?php echo $page === $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search-minus empty-icon"></i>
                <h2>No Products Found</h2>
                <p>No products match your selected filter criteria. Try clearing some filters or searching for another term.</p>
                <a href="products.php" class="btn-hero" style="margin-top:1rem;">Clear All Filters</a>
            </div>
        <?php endif; ?>
    </main>
</div>

<style>
/* CATALOG STYLES */
.page-header { background: var(--secondary-dark); color: white; padding: 2.5rem 0; margin-bottom: 2rem; }
.page-title { font-family: var(--font-heading); font-size: 2rem; font-weight: 900; margin-bottom: 0.25rem; }
.breadcrumb { display: flex; gap: 0.5rem; font-size: 0.85rem; color: #94A3B8; }
.breadcrumb li.active { color: var(--primary-red); font-weight: 600; }
.breadcrumb li + li::before { content: '/'; margin-right: 0.5rem; color: #64748B; }

.products-page-container { display: grid; grid-template-columns: 280px 1fr; gap: 2rem; padding-bottom: 4rem; }

.filter-sidebar { background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; height: fit-content; }
.sidebar-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 2px solid var(--primary-red); margin-bottom: 1.25rem; }
.sidebar-header h3 { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; }
.btn-clear-filters { font-size: 0.75rem; font-weight: 800; color: var(--primary-red); }

.filter-group { margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.25rem; }
.filter-title { font-family: var(--font-heading); font-size: 0.9rem; font-weight: 800; color: var(--secondary-dark); margin-bottom: 0.75rem; text-transform: uppercase; }

.filter-options { display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.875rem; color: var(--text-muted); }
.filter-radio, .filter-checkbox { display: flex; align-items: center; gap: 0.6rem; cursor: pointer; }
.subcategory-indent { margin-left: 1.25rem; margin-top: 0.4rem; display: flex; flex-direction: column; gap: 0.4rem; border-left: 2px solid #E2E8F0; padding-left: 0.75rem; }

.color-filter-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.color-checkbox-dot input { display: none; }
.dot-swatch { width: 22px; height: 22px; border-radius: 50%; border: 2px solid #CBD5E1; display: inline-block; cursor: pointer; transition: var(--transition-fast); }
.color-checkbox-dot input:checked + .dot-swatch { border-color: var(--primary-red); transform: scale(1.25); box-shadow: 0 0 0 2px rgba(239,68,68,0.3); }

.size-filter-grid { display: flex; flex-wrap: wrap; gap: 6px; }
.size-pill-checkbox input { display: none; }
.size-pill-checkbox span { display: inline-block; padding: 0.3rem 0.65rem; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.8rem; font-weight: 700; cursor: pointer; }
.size-pill-checkbox input:checked + span { background: var(--secondary-dark); color: white; border-color: var(--secondary-dark); }

.price-inputs { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; }
.price-input { width: 100%; padding: 0.4rem 0.6rem; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.85rem; }
.btn-filter-apply { width: 100%; background: var(--secondary-dark); color: white; border: none; padding: 0.5rem; border-radius: 4px; font-weight: 700; font-size: 0.8rem; cursor: pointer; }

.products-toolbar { display: flex; justify-content: space-between; align-items: center; background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 1.5rem; }
.toolbar-sort select { padding: 0.4rem 0.8rem; border: 1px solid var(--border-color); border-radius: 6px; font-weight: 600; font-size: 0.85rem; }

.products-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }

.pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 3rem; }
.page-link { width: 40px; height: 40px; border-radius: 50%; border: 1px solid var(--border-color); background: white; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--secondary-dark); }
.page-link.active, .page-link:hover { background: var(--primary-red); color: white; border-color: var(--primary-red); }

.empty-state { text-align: center; padding: 4rem 2rem; background: white; border-radius: var(--radius-md); border: 1px solid var(--border-color); }
.empty-icon { font-size: 3.5rem; color: #CBD5E1; margin-bottom: 1rem; }

@media (max-width: 900px) {
    .products-page-container { grid-template-columns: 1fr; }
    .products-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<script>
function applySorting(sortValue) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('sort', sortValue);
    window.location.search = urlParams.toString();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
