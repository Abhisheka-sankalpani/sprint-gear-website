<?php
// includes/header.php - Global Shared Header Component
require_once __DIR__ . '/functions.php';

$navCategories = getNavigationCategories();
$cartCount = getCartCount();
$wishlistCount = getWishlistCount();
$user = getLoggedInUser();
$currentSearch = sanitize($_GET['search'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " | Sprint Gear" : "Sprint Gear | Premium Sports & Activewear Store"; ?></title>
    
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Core Application CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- TOP BAR -->
    <div class="top-bar">
        <div class="container top-bar-content">
            <div class="top-info">
                <a href="tel:+94112345678"><i class="fas fa-phone-alt"></i> +94 11 234 5678</a>
                <span class="divider">|</span>
                <a href="mailto:info@sprintgear.lk"><i class="fas fa-envelope"></i> info@sprintgear.lk</a>
            </div>
            <div class="top-right">
                <div class="lang-selector">
                    <i class="fas fa-globe"></i> English / LKR
                </div>
                <span class="divider">|</span>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN HEADER -->
    <header class="main-header">
        <div class="container header-container">
            <!-- Mobile Menu Toggle Button -->
            <button class="mobile-toggle" id="mobileMenuBtn" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Brand Logo -->
            <a href="index.php" class="brand-logo">
                <div class="logo-icon"><i class="fas fa-running"></i></div>
                <div class="logo-text">
                    <span class="brand-name">SPRINT <span class="accent-red">GEAR</span></span>
                    <span class="brand-sub">SprintGear.lk</span>
                </div>
            </a>

            <!-- Global Search Bar -->
            <div class="header-search-container">
                <form action="products.php" method="GET" class="search-form">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="search-input" placeholder="Search by product, brand, running, football..." value="<?php echo $currentSearch; ?>" required>
                        <button type="submit" class="search-btn">SEARCH</button>
                    </div>
                </form>
            </div>

            <!-- Header Action Icons -->
            <div class="header-actions">
                <!-- User Profile / Auth Link -->
                <?php if ($user): ?>
                    <div class="user-dropdown">
                        <a href="profile.php" class="header-action-btn" title="My Account">
                            <i class="fas fa-user-circle"></i>
                            <span class="action-label"><?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?></span>
                        </a>
                        <div class="dropdown-menu">
                            <?php if ($user['role'] === 'admin'): ?>
                                <a href="admin/index.php" class="dropdown-item text-red-500"><i class="fas fa-user-shield"></i> Admin Panel</a>
                            <?php endif; ?>
                            <a href="profile.php" class="dropdown-item"><i class="fas fa-user-cog"></i> Profile & Address</a>
                            <a href="orders.php" class="dropdown-item"><i class="fas fa-box"></i> My Orders</a>
                            <a href="wishlist.php" class="dropdown-item"><i class="fas fa-heart"></i> My Wishlist</a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item text-red"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="header-action-btn" title="Login / Register">
                        <i class="far fa-user"></i>
                        <span class="action-label">Login</span>
                    </a>
                <?php endif; ?>

                <!-- Wishlist Icon -->
                <a href="wishlist.php" class="header-action-btn" title="Wishlist">
                    <div class="icon-badge-wrapper">
                        <i class="far fa-heart"></i>
                        <span class="badge badge-wishlist" id="headerWishlistBadge"><?php echo $wishlistCount; ?></span>
                    </div>
                    <span class="action-label">Wishlist</span>
                </a>

                <!-- Cart Icon -->
                <a href="cart.php" class="header-action-btn cart-btn-highlight" title="Shopping Cart">
                    <div class="icon-badge-wrapper">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="badge badge-cart" id="headerCartBadge"><?php echo $cartCount; ?></span>
                    </div>
                    <span class="action-label">Cart</span>
                </a>
            </div>
        </div>

        <!-- MAIN NAVIGATION BAR -->
        <nav class="navbar">
            <div class="container">
                <ul class="nav-menu" id="navMenu">
                    <li class="nav-item"><a href="index.php" class="nav-link active">HOME</a></li>
                    
                    <!-- PRODUCTS DROPDOWN -->
                    <li class="nav-item has-dropdown">
                        <a href="products.php" class="nav-link">
                            PRODUCTS <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </a>
                        <div class="mega-menu">
                            <div class="container mega-menu-grid">
                                <?php foreach ($navCategories as $cat): ?>
                                    <div class="mega-column">
                                        <a href="products.php?category=<?php echo urlencode($cat['name']); ?>" class="mega-title">
                                            <i class="fas <?php echo htmlspecialchars($cat['icon']); ?>"></i>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </a>
                                        <ul class="mega-links">
                                            <?php foreach ($cat['subcategories'] as $sub): ?>
                                                <li>
                                                    <a href="products.php?category=<?php echo urlencode($cat['name']); ?>&subcategory=<?php echo urlencode($sub['name']); ?>">
                                                        <?php echo htmlspecialchars($sub['name']); ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </li>

                    <li class="nav-item"><a href="about.php" class="nav-link">ABOUT US</a></li>
                    <li class="nav-item"><a href="contact.php" class="nav-link">CONTACT US</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Global Toast Alert Container -->
    <div id="toastContainer" class="toast-container"></div>
