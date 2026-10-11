<?php
// checkout.php - Customer Checkout & Order Processing Page
$pageTitle = "Checkout";
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/payhere.php';

$cartItems = getCartItems();
if (empty($cartItems)) {
    header("Location: cart.php");
    exit;
}

$user = getLoggedInUser();
$pdo = getDBConnection();

// Fetch saved addresses if logged in
$savedAddresses = [];
if ($user) {
    $stmtAddr = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC");
    $stmtAddr->execute([$user['id']]);
    $savedAddresses = $stmtAddr->fetchAll();
}

// Calculate totals
$subtotal = 0.00;
foreach ($cartItems as $item) {
    $price = !empty($item['discount_price']) ? (float)$item['discount_price'] : (float)$item['price'];
    if (!empty($item['variant_price'])) $price = (float)$item['variant_price'];
    $subtotal += ($price * (int)$item['quantity']);
}
$shippingFee = 500.00;
$grandTotal = $subtotal + $shippingFee;

// Process Order Placement
$errorMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = sanitize($_POST['phone'] ?? '');
    $addressLine1 = sanitize($_POST['address_line1'] ?? '');
    $addressLine2 = sanitize($_POST['address_line2'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $district = sanitize($_POST['district'] ?? '');
    $postalCode = sanitize($_POST['postal_code'] ?? '');
    $paymentMethod = sanitize($_POST['payment_method'] ?? 'COD');

    if (empty($fullName) || !$email || empty($phone) || empty($addressLine1) || empty($city) || empty($district) || empty($postalCode)) {
        $errorMsg = "Please complete all required shipping & contact fields.";
    } else {
        try {
            $pdo->beginTransaction();

            $orderNumber = "SG-" . date('Ymd') . "-" . strtoupper(bin2hex(random_bytes(2)));
            $fullShippingAddress = $addressLine1 . ($addressLine2 ? ", " . $addressLine2 : "");
            $paymentStatus = 'Pending';
            $orderStatus = ($paymentMethod === 'Card') ? 'Pending' : 'Confirmed';

            // 1. Insert Order
            $stmtOrder = $pdo->prepare("
                INSERT INTO orders (order_number, user_id, full_name, email, phone, shipping_address, city, district, postal_code, payment_method, payment_status, order_status, subtotal, shipping_fee, discount, total_amount)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?)
            ");
            $stmtOrder->execute([
                $orderNumber, $user ? $user['id'] : null, $fullName, $email, $phone,
                $fullShippingAddress, $city, $district, $postalCode,
                $paymentMethod, $paymentStatus, $orderStatus, $subtotal, $shippingFee, $grandTotal
            ]);
            $orderId = $pdo->lastInsertId();

            // 2. Insert Order Items & Decrement Stock
            $stmtItem = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, variant_id, product_name, color_name, size_name, price, quantity, subtotal)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtStockDec = $pdo->prepare("
                UPDATE product_variants SET stock = GREATEST(0, stock - ?) WHERE id = ?
            ");

            foreach ($cartItems as $cItem) {
                $itemPrice = !empty($cItem['discount_price']) ? (float)$cItem['discount_price'] : (float)$cItem['price'];
                if (!empty($cItem['variant_price'])) $itemPrice = (float)$cItem['variant_price'];
                $itemSubtotal = $itemPrice * (int)$cItem['quantity'];

                $stmtItem->execute([
                    $orderId, $cItem['product_id'], $cItem['variant_id'],
                    $cItem['product_name'], $cItem['color_name'], $cItem['size_name'],
                    $itemPrice, $cItem['quantity'], $itemSubtotal
                ]);

                $stmtStockDec->execute([(int)$cItem['quantity'], $cItem['variant_id']]);
            }

            // 3. Clear Active Cart Items
            $cartId = getActiveCartId();
            $stmtClearCart = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmtClearCart->execute([$cartId]);

            $pdo->commit();

            if ($paymentMethod === 'Card') {
                header("Location: payhere_process.php?order_id=" . $orderId);
                exit;
            }

            setFlash('success', "Order #$orderNumber placed successfully! Thank you for shopping with Sprint Gear.");
            header("Location: " . ($user ? "orders.php" : "index.php"));
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMsg = "Order placement failed: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Checkout Page Centered Wrapper & Layout */
.checkout-page-wrapper {
    width: 100% !important;
    background: #f8fafc !important;
    padding: 35px 20px 90px 20px !important;
    box-sizing: border-box !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
}

.checkout-content-container {
    width: 100% !important;
    max-width: 900px !important;
    margin-left: auto !important;
    margin-right: auto !important;
    box-sizing: border-box !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
}

/* Page Header */
.co-header {
    width: 100% !important;
    max-width: 900px !important;
    margin-left: auto !important;
    margin-right: auto !important;
    margin-bottom: 25px !important;
}
.co-title {
    font-size: 28px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 8px 0;
}
.co-crumbs {
    font-size: 14px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 8px;
}
.co-crumbs a {
    color: #64748b;
    text-decoration: none;
}
.co-crumbs span {
    color: #0f172a;
    font-weight: 600;
}

#checkoutForm {
    width: 100% !important;
    max-width: 900px !important;
    margin-left: auto !important;
    margin-right: auto !important;
}

/* Vertical Single-Column Layout */
.checkout-layout {
    display: flex !important;
    flex-direction: column !important;
    width: 100% !important;
    max-width: 900px !important;
    margin-left: auto !important;
    margin-right: auto !important;
    gap: 1.5rem !important;
}

.checkout-summary-col {
    width: 100% !important;
    max-width: 900px !important;
    margin-left: auto !important;
    margin-right: auto !important;
}

.checkout-fields {
    display: flex !important;
    flex-direction: column !important;
    gap: 1.5rem !important;
    width: 100% !important;
    max-width: 900px !important;
    margin-left: auto !important;
    margin-right: auto !important;
}

/* Cards */
.co-card {
    background: #ffffff !important;
    border-radius: 12px !important;
    border: 1px solid #e2e8f0 !important;
    padding: 24px !important;
    margin-bottom: 0 !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
    box-sizing: border-box !important;
    width: 100% !important;
    max-width: 900px !important;
    margin-left: auto !important;
    margin-right: auto !important;
}

.co-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 2px solid #f1f5f9;
    padding-bottom: 12px;
    margin-bottom: 20px;
}
.co-card-header i {
    font-size: 20px;
    color: #ef4444;
}
.co-card-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
}

/* Order Summary */
.co-top-summary {
    width: 100% !important;
}
.co-summary-table {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-height: 250px;
    overflow-y: auto;
    padding-right: 6px;
}
.co-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f8fafc;
}
.co-item-left {
    display: flex;
    align-items: center;
    gap: 15px;
}
.co-item-left img {
    width: 55px;
    height: 55px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
}
.co-item-name {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
}
.co-item-meta {
    font-size: 12px;
    color: #64748b;
    margin-top: 3px;
}
.co-item-price {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}

.co-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 18px 0;
}

.co-calc-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    background: #f8fafc;
    border-radius: 8px;
    padding: 16px 20px;
    border: 1px solid #edf2f7;
}
.co-calc-point {
    font-size: 14px;
    color: #475569;
}
.co-calc-point strong {
    color: #0f172a;
    font-size: 15px;
    margin-left: 6px;
}
.co-grand-point {
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
}
.co-grand-price {
    color: #ef4444;
    font-size: 22px;
    margin-left: 8px;
}

.co-field-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 15px;
    width: 100%;
}
.co-field-group label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}

.co-input {
    width: 100% !important;
    height: 46px !important;
    padding: 10px 14px !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    box-sizing: border-box !important;
    outline: none !important;
    background: #ffffff !important;
    color: #0f172a !important;
}
.co-input:focus {
    border-color: #ef4444 !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
}

.co-row-split {
    display: flex;
    gap: 15px;
    width: 100%;
}
.co-row-split > div {
    flex: 1;
}

/* Payment Options */
.co-radio-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 12px;
    cursor: pointer;
    background: #ffffff;
    box-sizing: border-box;
}
.co-radio-card input {
    margin-top: 4px;
    accent-color: #ef4444;
}
.co-radio-card strong {
    display: block;
    font-size: 15px;
    color: #0f172a;
    margin-bottom: 3px;
}
.co-radio-card p {
    margin: 0;
    font-size: 13px;
    color: #64748b;
}

.co-btn-place-order {
    width: 100% !important;
    margin-top: 15px;
    padding: 16px;
    background: #ef4444;
    color: #ffffff;
    font-weight: 800;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: background 0.2s;
}
.co-btn-place-order:hover {
    background: #dc2626;
}

/* Alerts */
.co-alert {
    width: 100% !important;
    max-width: 900px !important;
    margin-left: auto !important;
    margin-right: auto !important;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    box-sizing: border-box !important;
}
.co-alert-err { background: #fef2f2; border: 1px solid #f87171; color: #991b1b; }
.co-alert-warn { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; }

/* Responsive */
@media (max-width: 600px) {
    .co-row-split {
        flex-direction: column;
        gap: 0;
    }
    .co-calc-bar {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="checkout-page-wrapper">
    <div class="checkout-content-container">

        <!-- Title -->
        <div class="co-header">
            <h1 class="co-title">CHECKOUT</h1>
            <div class="co-crumbs">
                <a href="index.php">Home</a> <i class="fas fa-chevron-right" style="font-size: 10px;"></i> 
                <a href="cart.php">Shopping Cart</a> <i class="fas fa-chevron-right" style="font-size: 10px;"></i> 
                <span>Checkout</span>
            </div>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'cancelled'): ?>
            <div class="co-alert co-alert-warn">
                <i class="fas fa-exclamation-circle"></i> Payment was cancelled or not completed. Please try again.
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="co-alert co-alert-err">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <form action="checkout.php" method="POST" id="checkoutForm">
            <input type="hidden" name="action" value="place_order">

            <div class="checkout-layout">

                <!-- 1. ORDER SUMMARY -->
                <div class="checkout-summary-col">
                    <div class="co-card co-top-summary">
                        <div class="co-card-header">
                            <i class="fas fa-shopping-bag"></i>
                            <h3>ORDER SUMMARY</h3>
                        </div>

                        <div class="co-summary-table">
                            <?php foreach ($cartItems as $cItem): 
                                $itemPrice = !empty($cItem['discount_price']) ? (float)$cItem['discount_price'] : (float)$cItem['price'];
                                if (!empty($cItem['variant_price'])) $itemPrice = (float)$cItem['variant_price'];
                                $itemImg = !empty($cItem['variant_image']) ? $cItem['variant_image'] : $cItem['main_image'];
                            ?>
                                <div class="co-item-row">
                                    <div class="co-item-left">
                                        <img src="<?php echo htmlspecialchars($itemImg); ?>" alt="Item">
                                        <div>
                                            <div class="co-item-name"><?php echo htmlspecialchars($cItem['product_name']); ?></div>
                                            <div class="co-item-meta"><?php echo htmlspecialchars($cItem['color_name']); ?> | Size: <?php echo htmlspecialchars($cItem['size_name']); ?> &times; <?php echo $cItem['quantity']; ?></div>
                                        </div>
                                    </div>
                                    <div class="co-item-price">
                                        <?php echo formatPrice($itemPrice * $cItem['quantity']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="co-divider"></div>

                        <div class="co-calc-bar">
                            <div class="co-calc-point">
                                Items Subtotal: <strong><?php echo formatPrice($subtotal); ?></strong>
                            </div>
                            <div class="co-calc-point">
                                Islandwide Shipping: <strong><?php echo formatPrice($shippingFee); ?></strong>
                            </div>
                            <div class="co-grand-point">
                                Total Amount Payable: <span class="co-grand-price"><?php echo formatPrice($grandTotal); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CHECKOUT SECTIONS -->
                <div class="checkout-fields">

                    <!-- 2. Contact Information -->
                    <div class="co-card">
                        <div class="co-card-header">
                            <i class="fas fa-address-card"></i>
                            <h3>1. Contact Information</h3>
                        </div>

                        <div class="co-field-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" class="co-input" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required placeholder="John Doe">
                        </div>

                        <div class="co-row-split">
                            <div class="co-field-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" class="co-input" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required placeholder="johndoe@gmail.com">
                            </div>
                            <div class="co-field-group">
                                <label>Phone Number *</label>
                                <input type="tel" name="phone" class="co-input" placeholder="0771234567" required>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Delivery Shipping Address -->
                    <div class="co-card">
                        <div class="co-card-header">
                            <i class="fas fa-map-marker-alt"></i>
                            <h3>2. Delivery Shipping Address</h3>
                        </div>

                        <?php if (!empty($savedAddresses)): ?>
                            <div class="co-field-group">
                                <label>Use Saved Address:</label>
                                <select class="co-input" onchange="fillSavedAddress(this)">
                                    <option value="">-- Select Saved Address --</option>
                                    <?php foreach ($savedAddresses as $addr): ?>
                                        <option value='<?php echo json_encode($addr); ?>'>
                                            <?php echo htmlspecialchars($addr['address_line1'] . ', ' . $addr['city']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="co-field-group">
                            <label>Address Line 1 *</label>
                            <input type="text" id="addr1" name="address_line1" class="co-input" placeholder="House No, Street name" required>
                        </div>

                        <div class="co-field-group">
                            <label>Address Line 2 (Optional)</label>
                            <input type="text" id="addr2" name="address_line2" class="co-input" placeholder="Apartment, suite, landmark">
                        </div>

                        <div class="co-row-split">
                            <div class="co-field-group">
                                <label>City *</label>
                                <input type="text" id="addrCity" name="city" class="co-input" placeholder="Colombo" required>
                            </div>
                            <div class="co-field-group">
                                <label>District *</label>
                                <select id="addrDistrict" name="district" class="co-input" required>
                                    <option value="Colombo">Colombo</option>
                                    <option value="Gampaha">Gampaha</option>
                                    <option value="Kalutara">Kalutara</option>
                                    <option value="Kandy">Kandy</option>
                                    <option value="Galle">Galle</option>
                                    <option value="Matara">Matara</option>
                                    <option value="Jaffna">Jaffna</option>
                                    <option value="Kurunegala">Kurunegala</option>
                                    <option value="Other">Other District</option>
                                </select>
                            </div>
                        </div>

                        <div class="co-field-group">
                            <label>Postal Code *</label>
                            <input type="text" id="addrPostal" name="postal_code" class="co-input" placeholder="00300" required>
                        </div>
                    </div>

                    <!-- 4. Payment Method -->
                    <div class="co-card">
                        <div class="co-card-header">
                            <i class="fas fa-credit-card"></i>
                            <h3>3. Payment Method</h3>
                        </div>

                        <label class="co-radio-card">
                            <input type="radio" name="payment_method" value="COD" checked onclick="togglePaymentView('COD')">
                            <div>
                                <strong><i class="fas fa-money-bill-wave" style="color: #10b981;"></i> Cash on Delivery (COD)</strong>
                                <p>Pay with cash upon delivery to your doorstep.</p>
                            </div>
                        </label>

                        <label class="co-radio-card">
                            <input type="radio" name="payment_method" value="Card" onclick="togglePaymentView('Card')">
                            <div>
                                <strong><i class="fab fa-cc-visa" style="color: #2563eb;"></i> PayHere Online Gateway</strong>
                                <p>Pay securely via Visa, Mastercard, AMEX, or Mobile Wallets.</p>
                            </div>
                        </label>

                        <div id="cardPaymentFields" style="display:none; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 14px; margin-bottom: 15px;">
                            <div style="font-size: 13px; color: #059669; font-weight: 600; margin-bottom: 6px;">
                                <i class="fas fa-shield-alt"></i> Secured by 256-Bit SSL via PayHere Sandbox.
                            </div>
                            <div style="font-size: 18px; color: #475569; display: flex; gap: 10px; align-items: center;">
                                <i class="fab fa-cc-visa"></i>
                                <i class="fab fa-cc-mastercard"></i>
                                <i class="fab fa-cc-amex"></i>
                                <span style="font-size: 11px; font-weight: 700; background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">eZ Cash / Genie / Frimi / Koko</span>
                            </div>
                        </div>

                        <button type="submit" class="co-btn-place-order">
                            CONFIRM & PLACE ORDER <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>

<script>
function togglePaymentView(method) {
    const cardFields = document.getElementById('cardPaymentFields');
    if (cardFields) cardFields.style.display = (method === 'Card') ? 'block' : 'none';
}

function fillSavedAddress(selectEl) {
    if (!selectEl.value) return;
    const addr = JSON.parse(selectEl.value);
    document.getElementById('addr1').value = addr.address_line1 || '';
    document.getElementById('addr2').value = addr.address_line2 || '';
    document.getElementById('addrCity').value = addr.city || '';
    document.getElementById('addrDistrict').value = addr.district || 'Colombo';
    document.getElementById('addrPostal').value = addr.postal_code || '';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>