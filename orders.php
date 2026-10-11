<?php
// orders.php - Customer Order History & Tracking Page
$pageTitle = "My Orders";
require_once __DIR__ . '/includes/functions.php';

requireLogin();
$user = getLoggedInUser();
$pdo = getDBConnection();

// Fetch orders for logged-in user
$stmtOrders = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmtOrders->execute([$user['id']]);
$userOrders = $stmtOrders->fetchAll();

// If specific order details requested via GET
$selectedOrder = null;
$selectedOrderItems = [];
if (isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];
    $stmtO = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmtO->execute([$orderId, $user['id']]);
    $selectedOrder = $stmtO->fetch();

    if ($selectedOrder) {
        $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$orderId]);
        $selectedOrderItems = $stmtItems->fetchAll();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header" style="padding: 1.5rem 0; margin-bottom: 2rem;">
    <div class="container">
        <h1 class="page-title">MY ORDER HISTORY</h1>
        <ul class="breadcrumb">
            <li><a href="index.php">Home</a></li>
            <li><a href="profile.php">My Account</a></li>
            <li class="active">Orders</li>
        </ul>
    </div>
</div>

<div class="container" style="padding-bottom: 5rem;">
    <div class="profile-layout">
        <!-- SIDEBAR -->
        <div class="profile-sidebar">
            <ul class="account-nav">
                <li><a href="profile.php"><i class="fas fa-user-cog"></i> Profile & Address</a></li>
                <li><a href="orders.php" class="active"><i class="fas fa-box"></i> My Orders</a></li>
                <li><a href="wishlist.php"><i class="fas fa-heart"></i> My Wishlist</a></li>
                <li><a href="logout.php" class="text-red-500"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- MAIN ORDERS AREA -->
        <div class="profile-content">
            <?php if (!empty($userOrders)): ?>
                <div class="profile-card">
                    <h3 class="card-heading"><i class="fas fa-shopping-bag text-red-500"></i> Past Orders (<?php echo count($userOrders); ?>)</h3>
                    
                    <div class="table-responsive">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Total Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userOrders as $o): 
                                    $statusClass = 'status-pending';
                                    if ($o['order_status'] === 'Confirmed') $statusClass = 'status-confirmed';
                                    if ($o['order_status'] === 'Processing') $statusClass = 'status-processing';
                                    if ($o['order_status'] === 'Shipped') $statusClass = 'status-shipped';
                                    if ($o['order_status'] === 'Delivered') $statusClass = 'status-delivered';
                                    if ($o['order_status'] === 'Cancelled') $statusClass = 'status-cancelled';
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($o['created_at'])); ?></td>
                                        <td>
                                            <span class="pay-tag"><?php echo htmlspecialchars($o['payment_method']); ?></span>
                                            <small class="block text-muted"><?php echo htmlspecialchars($o['payment_status']); ?></small>
                                        </td>
                                        <td>
                                            <span class="order-status-badge <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($o['order_status']); ?>
                                            </span>
                                        </td>
                                        <td><strong class="text-secondary"><?php echo formatPrice($o['total_amount']); ?></strong></td>
                                        <td>
                                            <a href="orders.php?id=<?php echo $o['id']; ?>" class="btn-view-order"><i class="fas fa-eye"></i> Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ORDER DETAILS MODAL / CARD IF SELECTED -->
                <?php if ($selectedOrder): ?>
                    <div class="profile-card" id="orderDetailsSection">
                        <h3 class="card-heading"><i class="fas fa-receipt text-red-500"></i> Order Details: <?php echo htmlspecialchars($selectedOrder['order_number']); ?></h3>
                        
                        <div class="order-info-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem; background:#F8FAFC; padding:1.25rem; border-radius:8px; font-size:0.9rem;">
                            <div>
                                <p><strong>Recipient Name:</strong> <?php echo htmlspecialchars($selectedOrder['full_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($selectedOrder['email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($selectedOrder['phone']); ?></p>
                            </div>
                            <div>
                                <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($selectedOrder['shipping_address']); ?>, <?php echo htmlspecialchars($selectedOrder['city']); ?></p>
                                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($selectedOrder['payment_method']); ?> (<?php echo htmlspecialchars($selectedOrder['payment_status']); ?>)</p>
                                <p><strong>Current Status:</strong> <span class="order-status-badge status-confirmed"><?php echo htmlspecialchars($selectedOrder['order_status']); ?></span></p>
                            </div>
                        </div>

                        <h4 style="font-family:var(--font-heading); margin-bottom:0.75rem;">Purchased Items</h4>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Variant</th>
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
                                        <td><strong><?php echo formatPrice($item['subtotal']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open empty-icon"></i>
                    <h2>No Orders Found</h2>
                    <p>You haven't placed any orders with Sprint Gear yet.</p>
                    <a href="products.php" class="btn-hero" style="margin-top:1.5rem;">Start Shopping Now</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.orders-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem; }
.orders-table th, .orders-table td { padding: 0.9rem 0.75rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
.orders-table th { background: #F8FAFC; font-family: var(--font-heading); text-transform: uppercase; color: var(--secondary-dark); }

.order-status-badge { font-family: var(--font-heading); font-weight: 800; font-size: 0.7rem; padding: 0.2rem 0.6rem; border-radius: 20px; text-transform: uppercase; display: inline-block; }
.status-pending { background: #FEF3C7; color: #D97706; }
.status-confirmed { background: #DBEAFE; color: #1D4ED8; }
.status-processing { background: #E0E7FF; color: #4338CA; }
.status-shipped { background: #F3E8FF; color: #6B21A8; }
.status-delivered { background: #D1FAE5; color: #047857; }
.status-cancelled { background: #FEE2E2; color: #B91C1C; }

.pay-tag { font-weight: 700; font-size: 0.8rem; }
.btn-view-order { font-family: var(--font-heading); font-weight: 700; font-size: 0.8rem; color: var(--primary-red); }
</style>
<style>
/* Page Layout */
.page-header {
    background: #f8fafc;
    padding: 1.5rem 0;
    margin-bottom: 2rem;
    border-bottom: 1px solid #e2e8f0;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.5rem;
}

.breadcrumb {
    list-style: none;
    display: flex;
    gap: 0.5rem;
    padding: 0;
    margin: 0;
    font-size: 0.85rem;
    color: #64748b;
}

.breadcrumb li a {
    color: #64748b;
    text-decoration: none;
}

.breadcrumb li a:hover {
    color: #ef4444;
}

.breadcrumb li:not(:last-child)::after {
    content: "/";
    margin-left: 0.5rem;
}

.breadcrumb li.active {
    color: #ef4444;
    font-weight: 600;
}

/* Orders Page Layout */
.orders-layout,
.profile-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    align-items: start;
    padding-bottom: 4rem;
}

/* Sidebar Styling */
.profile-sidebar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.account-nav {
    list-style: none;
    padding: 0;
    margin: 0;
}

.account-nav li {
    margin-bottom: 0.4rem;
}

.account-nav a {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.92rem;
    color: #475569;
    text-decoration: none;
    transition: all 0.2s ease;
}

.account-nav a.active,
.account-nav a:hover {
    background: #fff1f2;
    color: #ef4444;
}

.account-nav a.logout-link,
.account-nav a.text-red-500 {
    color: #ef4444;
}

.account-nav a.logout-link:hover,
.account-nav a.text-red-500:hover {
    background: #fef2f2;
}

/* Empty State & Orders Card */
.empty-state,
.orders-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 3.5rem 2rem;
    text-align: center;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.empty-state i,
.empty-state .empty-icon {
    font-size: 3.5rem;
    color: #cbd5e1;
    margin-bottom: 1.25rem;
    display: inline-block;
}

.empty-state h2,
.empty-state h3 {
    font-size: 1.4rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #64748b;
    font-size: 0.95rem;
    max-width: 420px;
    margin: 0 auto 1.75rem;
}

/* Start Shopping Button */
.btn-hero,
.btn-start-shopping {
    display: inline-block;
    background: #ef4444;
    color: #ffffff;
    padding: 0.85rem 2rem;
    font-weight: 700;
    border-radius: 8px;
    text-decoration: none;
    transition: background 0.2s ease;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
}

.btn-hero:hover,
.btn-start-shopping:hover {
    background: #dc2626;
    color: #ffffff;
}

/* Orders Table (ඇණවුම් ඇති විට පෙන්වීමට) */
.order-table-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.order-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.order-table th {
    padding: 0.75rem 1rem;
    background: #f8fafc;
    color: #475569;
    font-size: 0.85rem;
    font-weight: 700;
    border-bottom: 1px solid #e2e8f0;
}

.order-table td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.9rem;
    color: #1e293b;
}

/* Mobile Responsive */
@media (max-width: 900px) {
    .orders-layout,
    .profile-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
