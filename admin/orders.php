<?php
// admin/orders.php - Order Management & Status Workflow Panel
$adminTitle = "Orders Management";
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Update Order Status POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
    $orderId = (int)$_POST['order_id'];
    $newStatus = sanitize($_POST['order_status']);
    $paymentStatus = sanitize($_POST['payment_status']);

    $stmtUpdStatus = $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
    $stmtUpdStatus->execute([$newStatus, $paymentStatus, $orderId]);

    setFlash('success', "Order status updated to $newStatus!");
    header("Location: orders.php" . (isset($_GET['id']) ? "?id=$orderId" : ""));
    exit;
}

// Fetch all orders
$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();

$selectedOrder = null;
$selectedOrderItems = [];
if (isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];
    $stmtO = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmtO->execute([$orderId]);
    $selectedOrder = $stmtO->fetch();

    if ($selectedOrder) {
        $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$orderId]);
        $selectedOrderItems = $stmtItems->fetchAll();
    }
}
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fas fa-shopping-bag text-red-500"></i> Customer Orders (<?php echo count($orders); ?>)</h2>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer Info</th>
                <th>Date</th>
                <th>Payment</th>
                <th>Order Status</th>
                <th>Total</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                    <td>
                        <strong><?php echo htmlspecialchars($o['full_name']); ?></strong><br>
                        <small style="color:#94A3B8;"><?php echo htmlspecialchars($o['email']); ?> | <?php echo htmlspecialchars($o['phone']); ?></small>
                    </td>
                    <td><?php echo date('M d, Y H:i', strtotime($o['created_at'])); ?></td>
                    <td>
                        <?php echo htmlspecialchars($o['payment_method']); ?><br>
                        <small style="color:<?php echo $o['payment_status'] === 'Paid' ? '#10B981' : '#F59E0B'; ?>; font-weight:700;">
                            <?php echo htmlspecialchars($o['payment_status']); ?>
                        </small>
                    </td>
                    <td>
                        <form action="orders.php" method="POST" style="display:flex; gap:0.3rem;">
                            <input type="hidden" name="action" value="update_order_status">
                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                            <select name="order_status" class="admin-control" style="padding:0.2rem 0.4rem; font-size:0.8rem; width:120px;" onchange="this.form.submit()">
                                <?php foreach (['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Returned'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo $o['order_status'] === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="payment_status" value="<?php echo $o['payment_status']; ?>">
                        </form>
                    </td>
                    <td><strong style="color:#EF4444;"><?php echo formatPrice($o['total_amount']); ?></strong></td>
                    <td>
                        <a href="orders.php?id=<?php echo $o['id']; ?>" class="btn-admin-action btn-admin-sm"><i class="fas fa-eye"></i> Details</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ORDER DETAILS VIEW CARD -->
<?php if ($selectedOrder): ?>
    <div class="admin-card" id="orderDetailsCard">
        <h3 class="admin-card-title" style="margin-bottom:1.25rem;"><i class="fas fa-receipt text-red-500"></i> Detailed View: Order <?php echo htmlspecialchars($selectedOrder['order_number']); ?></h3>
        
        <div class="admin-grid-2" style="margin-bottom:1.5rem; background:#0F172A; padding:1.25rem; border-radius:8px;">
            <div>
                <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($selectedOrder['full_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($selectedOrder['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($selectedOrder['phone']); ?></p>
            </div>
            <div>
                <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($selectedOrder['shipping_address']); ?>, <?php echo htmlspecialchars($selectedOrder['city']); ?>, <?php echo htmlspecialchars($selectedOrder['district']); ?> (<?php echo htmlspecialchars($selectedOrder['postal_code']); ?>)</p>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($selectedOrder['payment_method']); ?> (<?php echo htmlspecialchars($selectedOrder['payment_status']); ?>)</p>
            </div>
        </div>

        <h4 style="font-family:'Outfit'; margin-bottom:0.75rem;">Purchased Items</h4>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Color & Size</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($selectedOrderItems as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['color_name']); ?> | Size: <?php echo htmlspecialchars($item['size_name']); ?></td>
                        <td><?php echo formatPrice($item['price']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><strong style="color:#EF4444;"><?php echo formatPrice($item['subtotal']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
