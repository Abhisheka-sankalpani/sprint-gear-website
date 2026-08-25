<?php
// includes/footer.php - SprintGear Footer Component
?>
    <!-- FOOTER -->
    <footer>
        <div class="footer-main">
            <!-- Brand Column -->
            <div class="footer-col">
                <a href="index.php" class="logo-container" style="margin-bottom: 20px; display: inline-flex;">
                    <div class="logo-icon">
                        <i class="fas fa-running"></i>
                    </div>
                    <div class="logo-text" style="color: #FFF;">SPRINT<span>GEAR</span></div>
                </a>
                <p>Sri Lanka's premier destination for high-performance athletic footwear, activewear, and professional sports equipment. Engineered to unleash your true potential.</p>
                <div class="social-icons" style="margin-top: 15px;">
                    <a href="#" style="margin-left: 0; margin-right: 12px; font-size: 1.1rem;"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" style="margin-right: 12px; font-size: 1.1rem;"><i class="fab fa-instagram"></i></a>
                    <a href="#" style="margin-right: 12px; font-size: 1.1rem;"><i class="fab fa-youtube"></i></a>
                    <a href="#" style="font-size: 1.1rem;"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">All Products</a></li>
                    <li><a href="products.php?featured=1">Featured Gear</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="admin/index.php">Admin Panel</a></li>
                </ul>
            </div>

            <!-- Customer Care -->
            <div class="footer-col">
                <h4>Customer Care</h4>
                <ul class="footer-links">
                    <li><a href="#">Shipping & Delivery</a></li>
                    <li><a href="#">Returns & Exchanges</a></li>
                    <li><a href="#">Size Guide & Fit</a></li>
                    <li><a href="#">Order Tracking</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="footer-col">
                <h4>Store Location</h4>
                <ul class="footer-contact">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>No. 142, Galle Road, Colombo 03, Sri Lanka</span>
                    </li>
                    <li>
                        <i class="fas fa-phone-alt"></i>
                        <span>+94 11 234 5678 / +94 77 123 4567</span>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <span>support@sprintgear.lk</span>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <span>Mon - Sat: 9:00 AM - 8:00 PM</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div>
                &copy; <?= date('Y') ?> <strong>SprintGear.lk</strong>. All Rights Reserved. Crafted for High Performance.
            </div>
            <div class="payment-badges">
                <i class="fab fa-cc-visa" title="Visa"></i>
                <i class="fab fa-cc-mastercard" title="MasterCard"></i>
                <i class="fab fa-cc-amex" title="American Express"></i>
                <i class="fas fa-money-bill-wave" title="Cash on Delivery"></i>
            </div>
        </div>
    </footer>

    <!-- Main JavaScript -->
    <script src="assets/js/main.js"></script>
</body>
</html>
