<?php
// cart.php - Shopping Cart Management Page
$pageTitle = "Shopping Cart";
require_once __DIR__ . '/includes/functions.php';

// Handle AJAX or POST Cart Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

    if ($action === 'update_qty') {
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        $res = updateCartQuantity($itemId, $quantity);
        setFlash($res['success'] ? 'success' : 'error', $res['message']);
    } elseif ($action === 'remove_item') {
        $res = removeFromCart($itemId);
        setFlash('info', $res['message']);
    }
    header("Location: cart.php");
    exit;
}

$cartItems = getCartItems();

// Calculate totals
$subtotal = 0.00;
foreach ($cartItems as $item) {
    $price = !empty($item['discount_price']) ? (float)$item['discount_price'] : (float)$item['price'];
    if (!empty($item['variant_price'])) $price = (float)$item['variant_price'];
    $subtotal += ($price * (int)$item['quantity']);
}

$shippingFee = ($subtotal > 0) ? 500.00 : 0.00;
$grandTotal = $subtotal + $shippingFee;

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header" style="padding: 1.5rem 0; margin-bottom: 2rem;">
    <div class="container">
        <h1 class="page-title">SHOPPING CART</h1>
        <ul class="breadcrumb">
            <li><a href="index.php">Home</a></li>
            <li class="active">Shopping Cart</li>
        </ul>
    </div>
</div>

<div class="container" style="padding-bottom: 5rem;">
    <?php if (!empty($cartItems)): ?>
        <div class="cart-layout">
            <!-- CART ITEMS TABLE -->
            <div class="cart-table-wrapper">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Color & Size</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): 
                            $unitPrice = !empty($item['discount_price']) ? (float)$item['discount_price'] : (float)$item['price'];
                            if (!empty($item['variant_price'])) $unitPrice = (float)$item['variant_price'];
                            $itemSubtotal = $unitPrice * (int)$item['quantity'];
                            $itemImg = !empty($item['variant_image']) ? $item['variant_image'] : $item['main_image'];
                        ?>
                            <tr>
                                <td class="product-cell">
                                    <img src="<?php echo htmlspecialchars($itemImg); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="cart-item-img">
                                    <div class="cart-item-info">
                                        <span class="item-brand"><?php echo htmlspecialchars($item['brand']); ?></span>
                                        <a href="product-details.php?id=<?php echo $item['product_id']; ?>" class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></a>
                                    </div>
                                </td>

                                <td>
                                    <div class="variant-meta">
                                        <span class="meta-pill"><span class="swatch-mini" style="background-color:<?php echo htmlspecialchars($item['hex_code']); ?>;"></span> <?php echo htmlspecialchars($item['color_name']); ?></span>
                                        <span class="meta-pill">Size: <?php echo htmlspecialchars($item['size_name']); ?></span>
                                    </div>
                                </td>

                                <td class="price-cell"><?php echo formatPrice($unitPrice); ?></td>

                                <td>
                                    <form action="cart.php" method="POST" class="qty-form">
                                        <input type="hidden" name="action" value="update_qty">
                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                        <div class="cart-qty-picker">
                                            <button type="submit" onclick="this.form.quantity.value = Math.max(1, parseInt(this.form.quantity.value)-1);" class="cart-qty-btn">-</button>
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" onchange="this.form.submit()" class="cart-qty-input">
                                            <button type="submit" onclick="this.form.quantity.value = Math.min(<?php echo $item['stock']; ?>, parseInt(this.form.quantity.value)+1);" class="cart-qty-btn">+</button>
                                        </div>
                                    </form>
                                    <span class="stock-hint">Max stock: <?php echo $item['stock']; ?></span>
                                </td>

                                <td class="subtotal-cell"><?php echo formatPrice($itemSubtotal); ?></td>

                                <td>
                                    <form action="cart.php" method="POST">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                        <button type="submit" class="btn-remove-item" title="Remove Product"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="cart-table-footer">
                    <a href="products.php" class="btn-continue-shopping"><i class="fas fa-arrow-left"></i> CONTINUE SHOPPING</a>
                </div>
            </div>

            <!-- ORDER SUMMARY SIDEBAR -->
            <div class="cart-summary-card">
                <h3 class="summary-title">ORDER SUMMARY</h3>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong class="text-secondary"><?php echo formatPrice($subtotal); ?></strong>
                </div>
                <div class="summary-row">
                    <span>Standard Shipping Fee</span>
                    <strong><?php echo formatPrice($shippingFee); ?></strong>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-row total-row">
                    <span>Grand Total</span>
                    <strong class="total-amount"><?php echo formatPrice($grandTotal); ?></strong>
                </div>

                <a href="checkout.php" class="btn-checkout-proceed">
                    PROCEED TO CHECKOUT <i class="fas fa-arrow-right"></i>
                </a>

                <div class="secure-checkout-notice">
                    <i class="fas fa-lock text-green-400"></i> Secure 256-bit Encrypted Checkout
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-shopping-bag empty-icon"></i>
            <h2>Your Shopping Cart is Empty</h2>
            <p>Looks like you haven't added any sports gear to your cart yet.</p>
            <a href="products.php" class="btn-hero" style="margin-top:1.5rem;">Explore Products Catalog</a>
        </div>
    <?php endif; ?>
</div>

<style>
.cart-layout { display: grid; grid-template-columns: 1fr 340px; gap: 2rem; }
.cart-table-wrapper { background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; }
.cart-table { width: 100%; border-collapse: collapse; text-align: left; }
.cart-table th, .cart-table td { padding: 1.25rem 1rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
.cart-table th { background: #F8FAFC; font-family: var(--font-heading); font-size: 0.85rem; text-transform: uppercase; color: var(--secondary-dark); }

.product-cell { display: flex; align-items: center; gap: 1rem; }
.cart-item-img { width: 70px; height: 70px; border-radius: 6px; object-fit: cover; border: 1px solid var(--border-color); }
.cart-item-info { display: flex; flex-direction: column; }
.item-brand { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
.item-name { font-family: var(--font-heading); font-weight: 700; font-size: 0.95rem; color: var(--secondary-dark); }
.item-name:hover { color: var(--primary-red); }

.variant-meta { display: flex; flex-direction: column; gap: 0.25rem; }
.meta-pill { font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.4rem; }
.swatch-mini { width: 10px; height: 10px; border-radius: 50%; border: 1px solid #CBD5E1; display: inline-block; }

.price-cell, .subtotal-cell { font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: var(--secondary-dark); }

.cart-qty-picker { display: flex; border: 1px solid var(--border-color); border-radius: 4px; overflow: hidden; width: fit-content; }
.cart-qty-btn { background: #F1F5F9; border: none; width: 28px; height: 32px; font-weight: 800; cursor: pointer; }
.cart-qty-input { width: 36px; height: 32px; border: none; text-align: center; font-weight: 700; outline: none; font-size: 0.85rem; }
.stock-hint { font-size: 0.7rem; color: var(--text-light); display: block; margin-top: 2px; }

.btn-remove-item { background: none; border: none; color: #94A3B8; font-size: 1.1rem; cursor: pointer; }
.btn-remove-item:hover { color: var(--primary-red); }

.cart-table-footer { padding: 1.25rem; background: #F8FAFC; border-top: 1px solid var(--border-color); }
.btn-continue-shopping { font-family: var(--font-heading); font-weight: 800; font-size: 0.85rem; color: var(--secondary-dark); }
.btn-continue-shopping:hover { color: var(--primary-red); }

.cart-summary-card { background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; height: fit-content; box-shadow: var(--shadow-sm); }
.summary-title { font-family: var(--font-heading); font-size: 1.2rem; font-weight: 900; border-bottom: 2px solid var(--primary-red); padding-bottom: 0.5rem; margin-bottom: 1.25rem; }
.summary-row { display: flex; justify-content: space-between; font-size: 0.95rem; margin-bottom: 0.75rem; }
.summary-divider { height: 1px; background: var(--border-color); margin: 1rem 0; }
.total-row { font-size: 1.15rem; font-family: var(--font-heading); }
.total-amount { color: var(--primary-red); font-weight: 900; font-size: 1.3rem; }

.btn-checkout-proceed { display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; background: var(--primary-red); color: white; border: none; padding: 0.9rem 0; border-radius: var(--radius-sm); font-family: var(--font-heading); font-weight: 800; font-size: 0.9rem; letter-spacing: 0.5px; margin-top: 1.5rem; }
.btn-checkout-proceed:hover { background: var(--primary-red-hover); }

.secure-checkout-notice { font-size: 0.8rem; color: var(--text-muted); text-align: center; margin-top: 1rem; }

@media (max-width: 900px) {
    .cart-layout { grid-template-columns: 1fr; }
    .cart-table-wrapper { overflow-x: auto; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
