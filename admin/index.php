<?php
// admin/index.php - Admin Main Overview & Analytics Dashboard
$adminTitle = "Dashboard Overview";
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Metrics calculations
$totalRevenue = (float)$pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'Paid' OR order_status = 'Delivered'")->fetchColumn();
$totalOrders = (int)$pdo->query("SELECT COUNT(id) FROM orders")->fetchColumn();
$totalProducts = (int)$pdo->query("SELECT COUNT(id) FROM products WHERE status = 'active'")->fetchColumn();
$lowStockCount = getLowStockCount(5);
$totalUsers = (int)$pdo->query("SELECT COUNT(id) FROM users WHERE role = 'customer'")->fetchColumn();
$totalMessages = (int)$pdo->query("SELECT COUNT(id) FROM contact_messages")->fetchColumn();

// Fetch Recent Orders
$stmtRecent = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 6");
$recentOrders = $stmtRecent->fetchAll();
?>

<!-- 1. STATS METRICS CARDS -->
<div class="admin-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-money-bill-wave"></i></div>
        <div>
            <div class="stat-value"><?php echo formatPrice($totalRevenue); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-shopping-bag"></i></div>
        <div>
            <div class="stat-value"><?php echo $totalOrders; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-box"></i></div>
        <div>
            <div class="stat-value"><?php echo $totalProducts; ?></div>
            <div class="stat-label">Active Products</div>
        </div>
    </div>

    <div class="stat-card">
        <a href="variants.php?filter=low_stock" style="text-decoration:none; color:inherit; display:flex; gap:1rem; align-items:center; width:100%;">
            <div class="stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div>
            <div>
                <div class="stat-value"><?php echo $lowStockCount; ?></div>
                <div class="stat-label">Low Stock Items</div>
            </div>
        </a>
    </div>

    <div class="stat-card">
        <div class="stat-icon gold"><i class="fas fa-users"></i></div>
        <div>
            <div class="stat-value"><?php echo $totalUsers; ?></div>
            <div class="stat-label">Customers</div>
        </div>
    </div>

    <div class="stat-card">
        <a href="contact-messages.php" style="text-decoration:none; color:inherit; display:flex; gap:1rem; align-items:center;">
            <div class="stat-icon red"><i class="fas fa-envelope"></i></div>
            <div>
                <div class="stat-value"><?php echo $totalMessages; ?></div>
                <div class="stat-label">Inquiries</div>
            </div>
        </a>
    </div>
</div>

<!-- 2. RECENT ORDERS CARD -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fas fa-clock text-red-500"></i> Recent Orders</h2>
        <a href="orders.php" class="btn-admin-action btn-admin-secondary btn-admin-sm">View All Orders</a>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Total</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentOrders as $o): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($o['order_number']); ?></strong></td>
                    <td>
                        <?php echo htmlspecialchars($o['full_name']); ?><br>
                        <small style="color:#94A3B8;"><?php echo htmlspecialchars($o['email']); ?></small>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($o['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($o['payment_method']); ?> (<?php echo htmlspecialchars($o['payment_status']); ?>)</td>
                    <td>
                        <span style="display:inline-block; padding:0.2rem 0.6rem; border-radius:12px; font-weight:800; font-size:0.7rem; background:#334155; color:white;">
                            <?php echo htmlspecialchars($o['order_status']); ?>
                        </span>
                    </td>
                    <td><strong style="color:#EF4444;"><?php echo formatPrice($o['total_amount']); ?></strong></td>
                    <td>
                        <a href="orders.php?id=<?php echo $o['id']; ?>" class="btn-admin-action btn-admin-sm">Manage</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
