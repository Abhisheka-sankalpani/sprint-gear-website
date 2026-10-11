<?php
// about.php - About Sprint Gear Page
$pageTitle = "About Us";
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header" style="padding: 2.5rem 0; margin-bottom: 2rem;">
    <div class="container">
        <h1 class="page-title">ABOUT SPRINT GEAR</h1>
        <ul class="breadcrumb">
            <li><a href="index.php">Home</a></li>
            <li class="active">About Us</li>
        </ul>
    </div>
</div>

<div class="container" style="padding-bottom: 5rem;">
    <div class="about-hero-card">
        <div class="about-text-content">
            <span class="hero-badge">OUR MISSION</span>
            <h2>FUELING ATHLETIC PASSION & CHAMPIONSHIP PERFORMANCE</h2>
            <p>Founded in Sri Lanka, <strong>Sprint Gear (SprintGear.lk)</strong> was built with a single focused vision: to empower athletes, fitness enthusiasts, and sports lovers with world-class activewear and high-grade sports equipment.</p>
            <p>Whether you're training for your next marathon, competing on the football pitch, spiking on the volleyball court, or dominating the basketball arena, Sprint Gear brings you authentic gear from top global sports brands like Nike, Adidas, ASICS, Puma, Under Armour, and Mizuno.</p>
        </div>
        <div class="about-image-wrapper">
            <img src="uploads/categories/cat_running_gear.jpg" alt="Sprint Gear Athletic Performance">
        </div>
    </div>

    <div class="values-grid">
        <div class="value-card">
            <i class="fas fa-certificate text-red-500"></i>
            <h3>100% Authentic Gear</h3>
            <p>We source only original, performance-verified sportswear directly from authorized global manufacturers.</p>
        </div>
        <div class="value-card">
            <i class="fas fa-shipping-fast text-red-500"></i>
            <h3>Fast Express Shipping</h3>
            <p>Islandwide Sri Lankan courier delivery ensuring your match-day kit arrives right on schedule.</p>
        </div>
        <div class="value-card">
            <i class="fas fa-user-shield text-red-500"></i>
            <h3>Customer First Fit Guarantee</h3>
            <p>Enjoy hassle-free 14-day size exchanges so your activewear fits comfortably every time.</p>
        </div>
    </div>
</div>

<style>
.about-hero-card { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; background: white; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 3rem; margin-bottom: 3rem; align-items: center; }
.about-text-content h2 { font-family: var(--font-heading); font-size: 2rem; font-weight: 900; color: var(--secondary-dark); margin: 0.75rem 0 1rem; }
.about-text-content p { color: var(--text-muted); line-height: 1.7; margin-bottom: 1rem; }
.about-image-wrapper img { width: 100%; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); }

.values-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
.value-card { background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 2rem; text-align: center; }
.value-card i { font-size: 2.5rem; margin-bottom: 1rem; }
.value-card h3 { font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--secondary-dark); }
.value-card p { color: var(--text-muted); font-size: 0.9rem; }

@media (max-width: 900px) {
    .about-hero-card { grid-template-columns: 1fr; }
    .values-grid { grid-template-columns: 1fr; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
