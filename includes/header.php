<?php
// includes/header.php - SprintGear Header Component
require_once __DIR__ . '/../config/db.php';

$cart_count = getCartCount($pdo, $_SESSION['session_id']);
$wishlist_count = getWishlistCount($pdo, $_SESSION['session_id']);
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprintGear.lk - High Performance Sports Gear</title>
    <meta name="description" content="SprintGear Sri Lanka - High quality activewear, running shoes, football, volleyball & fitness equipment. Push your limits with the best.">
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Main Style CSS -->
  <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- TOP BAR -->
    <div class="top-bar">
        <div class="top-bar-left">
            <span><i class="fas fa-phone-alt"></i> +94 11 234 5678</span>
            <span><i class="fas fa-envelope"></i> info@sprintgear.lk</span>
            <span><i class="fas fa-truck"></i> Islandwide Delivery Available</span>
        </div>
        <div class="top-bar-right">
            <select class="language-selector">
                <option value="en">English (US)</option>
                <option value="si">සිංහල (Sinhala)</option>
                <option value="ta">தமிழ் (Tamil)</option>
            </select>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
                <a href="#"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>
    </div>

    <!-- MAIN HEADER -->
    <header class="main-header">
        <!-- Logo -->
        <a href="index.php" class="logo-container">
            <div class="logo-icon">
                <i class="fas fa-running"></i>
            </div>
            <div class="logo-text">SPRINT<span>GEAR</span></div>
        </a>

        <!-- නව කළු පැහැති SEARCH BAR එක (මැදට) -->
        <div class="header-search">
            <form action="search.php" method="GET" class="header-search-form">
                <input type="text" placeholder="Search for sports gear..." name="search" class="header-search-input">
                <button type="submit" class="header-search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>

        <!-- Main Navigation -->
        <nav class="main-nav">
            <ul>
                <li><a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">HOME</a></li>
                <li class="nav-item-dropdown">
                    <a href="products.php" class="dropdown-toggle <?= $current_page == 'products.php' ? 'active' : '' ?>">
                        PRODUCTS <i class="fas fa-chevron-down" style="font-size: 0.75rem;"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="products.php?category=running-gear"><i class="fas fa-running"></i> Running Gear</a>
                        <a href="products.php?category=football"><i class="fas fa-futbol"></i> Football</a>
                        <a href="products.php?category=volleyball"><i class="fas fa-volleyball-ball"></i> Volleyball</a>
                        <a href="products.php?category=basketball"><i class="fas fa-basketball-ball"></i> Basketball</a>
                        <a href="products.php?category=training-gym"><i class="fas fa-dumbbell"></i> Training & Gym</a>
                        <a href="products.php?category=accessories"><i class="fas fa-heartbeat"></i> Accessories</a>
                    </div>
                </li>
                <li><a href="about.php" class="<?= $current_page == 'about.php' ? 'active' : '' ?>">ABOUT US</a></li>
                <li><a href="contact.php" class="<?= $current_page == 'contact.php' ? 'active' : '' ?>">CONTACT US</a></li>
            </ul>
        </nav>

        <!-- Right Action Icons -->
        <div class="header-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="account.php" class="action-icon" title="My Account">
                    <i class="fas fa-user-circle"></i>
                </a>
            <?php else: ?>
                <a href="login.php" class="action-icon" title="Sign In">
                    <i class="far fa-user"></i>
                </a>
            <?php endif; ?>

            <a href="wishlist.php" class="action-icon" title="Wishlist">
                <i class="far fa-heart"></i>
                <span class="badge" id="wishlist-count-badge"><?= $wishlist_count ?></span>
            </a>

            <a href="cart.php" class="action-icon" title="Shopping Cart">
                <i class="fas fa-shopping-bag"></i>
                <span class="badge" id="cart-count-badge"><?= $cart_count ?></span>
            </a>
        </div>
    </header>