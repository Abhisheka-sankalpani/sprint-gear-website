<?php
// includes/footer.php - Shared Footer & Community Subscription Component

// Process Newsletter AJAX or POST if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'subscribe_newsletter') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    if ($email) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
            $stmt->execute([$email]);
            setFlash('success', 'Thank you for joining the Sprint Gear community!');
        } catch (PDOException $e) {
            setFlash('info', 'You are already subscribed to our newsletter.');
        }
    } else {
        setFlash('error', 'Please enter a valid email address.');
    }
}
?>

    <!-- COMMUNITY SIGN-UP SECTION -->
    <section class="community-section">
        <div class="container community-content">
            <div class="community-text">
                <h2 class="community-title"><i class="fas fa-bolt text-red-500"></i> JOIN THE SPRINT GEAR COMMUNITY</h2>
                <p class="community-desc">Subscribe to receive exclusive athlete gear drops, early promotional offers, and high-performance training updates.</p>
            </div>
            <div class="community-form-wrapper">
                <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST" class="community-form">
                    <input type="hidden" name="action" value="subscribe_newsletter">
                    <div class="input-group">
                        <i class="far fa-envelope input-icon"></i>
                        <input type="email" name="email" class="community-input" placeholder="Enter your email address" required>
                        <button type="submit" class="community-btn">SUBSCRIBE</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="container footer-grid">
            <!-- Col 1: Brand Info -->
            <div class="footer-col brand-col">
                <a href="index.php" class="brand-logo footer-logo">
                    <div class="logo-icon"><i class="fas fa-running"></i></div>
                    <div class="logo-text">
                        <span class="brand-name">SPRINT <span class="accent-red">GEAR</span></span>
                        <span class="brand-sub">SprintGear.lk</span>
                    </div>
                </a>
                <p class="footer-about">
                    Sprint Gear is Sri Lanka's premier destination for authentic, high-performance athletic footwear, activewear, and sports equipment engineered to elevate your potential.
                </p>
                <div class="footer-socials">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- Col 2: Quick Links -->
            <div class="footer-col">
                <h4 class="footer-heading">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">All Products</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="wishlist.php">My Wishlist</a></li>
                </ul>
            </div>

            <!-- Col 3: Product Categories -->
            <div class="footer-col">
                <h4 class="footer-heading">Categories</h4>
                <ul class="footer-links">
                    <li><a href="products.php?category=Running+Gear">Running Gear</a></li>
                    <li><a href="products.php?category=Football">Football Cleats & Kits</a></li>
                    <li><a href="products.php?category=Volleyball">Volleyball Gear</a></li>
                    <li><a href="products.php?category=Basketball">Basketball Sneakers</a></li>
                    <li><a href="products.php?category=Training+%26+Fitness">Training & Gym</a></li>
                    <li><a href="products.php?category=Accessories">Sports Accessories</a></li>
                </ul>
            </div>

            <!-- Col 4: Customer Support & Contact -->
            <div class="footer-col">
                <h4 class="footer-heading">Customer Service</h4>
                <ul class="footer-links">
                    <li><a href="#" onclick="openSizeGuideModal('Shoes'); return false;">Size Guide</a></li>
                    <li><a href="contact.php#shipping">Shipping & Returns</a></li>
                    <li><a href="contact.php#privacy">Privacy Policy</a></li>
                    <li><a href="contact.php#terms">Terms & Conditions</a></li>
                </ul>
                <div class="footer-contact-info">
                    <p><i class="fas fa-map-marker-alt text-red-500"></i> No. 45 Galle Road, Colombo 03, Sri Lanka</p>
                    <p><i class="fas fa-phone-alt text-red-500"></i> +94 11 234 5678</p>
                    <p><i class="fas fa-envelope text-red-500"></i> info@sprintgear.lk</p>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container footer-bottom-content">
                <p>&copy; <?php echo date('Y'); ?> <strong>Sprint Gear (SprintGear.lk)</strong>. All Rights Reserved. Built with PHP & MySQL.</p>
                <div class="payment-methods">
                    <span class="payment-label">Accepted Payments:</span>
                    <i class="fab fa-cc-visa" title="Visa"></i>
                    <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                    <i class="fas fa-money-bill-wave" title="Cash On Delivery"></i>
                </div>
            </div>
        </div>
    </footer>

    <!-- Size Guide Modal Reusable Include -->
    <?php include_once __DIR__ . '/size-guide-modal.php'; ?>

    <!-- Main JavaScript Application File -->
    <script src="assets/js/main.js"></script>

    <?php if ($flashSuccess = getFlash('success')): ?>
        <script>document.addEventListener('DOMContentLoaded', () => showToast("<?php echo addslashes($flashSuccess); ?>", 'success'));</script>
    <?php endif; ?>
    <?php if ($flashError = getFlash('error')): ?>
        <script>document.addEventListener('DOMContentLoaded', () => showToast("<?php echo addslashes($flashError); ?>", 'error'));</script>
    <?php endif; ?>
    <?php if ($flashInfo = getFlash('info')): ?>
        <script>document.addEventListener('DOMContentLoaded', () => showToast("<?php echo addslashes($flashInfo); ?>", 'info'));</script>
    <?php endif; ?>

</body>
</html>
