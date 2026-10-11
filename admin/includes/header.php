<?php
// admin/includes/header.php - Admin Panel Shared Header & Sidebar Navigation
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin('../login.php');

$adminUser = getLoggedInUser();
$currentFile = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Sprint Gear</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <!-- SIDEBAR NAVIGATION -->
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <a href="../index.php" style="display:flex; align-items:center; gap:0.5rem; text-decoration:none;">
                    <div style="background:#EF4444; color:white; width:34px; height:34px; border-radius:6px; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-running"></i>
                    </div>
                    <span style="font-family:'Outfit'; font-size:1.2rem; font-weight:900; color:white;">SPRINT <span style="color:#EF4444;">ADMIN</span></span>
                </a>
            </div>

<?php
$adminPdo = getDBConnection();
$unreadContactCount = 0;
try {
    $unreadStmt = $adminPdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'New'");
    $unreadContactCount = (int)$unreadStmt->fetchColumn();
} catch (Exception $e) {}
?>
            <ul class="admin-nav">
                <li><a href="index.php" class="<?php echo $currentFile === 'index.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="products.php" class="<?php echo in_array($currentFile, ['products.php', 'add-product.php', 'edit-product.php']) ? 'active' : ''; ?>"><i class="fas fa-box"></i> Products Catalog</a></li>
                <li><a href="variants.php" class="<?php echo $currentFile === 'variants.php' ? 'active' : ''; ?>"><i class="fas fa-tags"></i> Product Variants & Stock</a></li>
                <li><a href="categories.php" class="<?php echo $currentFile === 'categories.php' ? 'active' : ''; ?>"><i class="fas fa-layer-group"></i> Categories</a></li>
                <li><a href="subcategories.php" class="<?php echo $currentFile === 'subcategories.php' ? 'active' : ''; ?>"><i class="fas fa-sitemap"></i> Subcategories</a></li>
                <li><a href="orders.php" class="<?php echo $currentFile === 'orders.php' ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Orders Management</a></li>
                <li>
                    <a href="contact-messages.php" class="<?php echo $currentFile === 'contact-messages.php' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope"></i> Contact Messages
                        <?php if ($unreadContactCount > 0): ?>
                            <span style="background:#EF4444; color:white; font-size:0.75rem; font-weight:800; padding:2px 7px; border-radius:10px; margin-left:auto;"><?php echo $unreadContactCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="users.php" class="<?php echo $currentFile === 'users.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Users Management</a></li>
            </ul>

            <div style="margin-top:auto; padding-top:1.5rem; border-top:1px solid #334155;">
                <p style="font-size:0.8rem; color:#94A3B8; margin-bottom:0.5rem;">Logged in as: <strong><?php echo htmlspecialchars($adminUser['name']); ?></strong></p>
                <a href="../logout.php" class="btn-admin-action btn-admin-secondary" style="width:100%; justify-content:center;"><i class="fas fa-sign-out-alt"></i> Store Logout</a>
            </div>
        </aside>

        <!-- MAIN ADMIN CONTAINER -->
        <main class="admin-main">
            <div class="admin-header">
                <h1><?php echo isset($adminTitle) ? htmlspecialchars($adminTitle) : 'Admin Dashboard Overview'; ?></h1>
                <div>
                    <a href="../index.php" target="_blank" class="btn-admin-action btn-admin-secondary"><i class="fas fa-external-link-alt"></i> View Storefront</a>
                </div>
            </div>
