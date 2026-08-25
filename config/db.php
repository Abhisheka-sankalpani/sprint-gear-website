<?php
// config/db.php - SprintGear Database Connection & Auto-Initializer

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$db_host = '127.0.0.1';
$db_name = 'sprintgear';
$db_user = 'root';
$db_pass = '';

$pdo = null;

try {
    // Attempt MySQL connection
    $dsn = "mysql:host=$db_host;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Create DB if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db_name`");
    
    // Check if tables exist, if not run initialization
    $query = $pdo->query("SHOW TABLES LIKE 'products'");
    if ($query->rowCount() == 0) {
        $sql = file_get_contents(__DIR__ . '/../database.sql');
        $pdo->exec($sql);
    }
} catch (Exception $e) {
    // SQLite Fallback for standalone preview without active MySQL server
    try {
        $sqlite_file = __DIR__ . '/sprintgear_fallback.sqlite';
        $pdo = new PDO("sqlite:" . $sqlite_file, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Auto create tables for SQLite
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                image TEXT NOT NULL,
                icon TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS colors (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                hex_code TEXT NOT NULL,
                image_swatch TEXT
            );
            CREATE TABLE IF NOT EXISTS sizes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                size_code TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                brand TEXT NOT NULL,
                description TEXT,
                price REAL NOT NULL,
                sale_price REAL,
                rating REAL DEFAULT 5.0,
                reviews_count INTEGER DEFAULT 0,
                main_image TEXT NOT NULL,
                secondary_image TEXT,
                is_featured INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS product_variants (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                color_id INTEGER,
                size_id INTEGER,
                sku TEXT UNIQUE,
                stock_qty INTEGER DEFAULT 0,
                variant_image TEXT
            );
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                phone TEXT,
                role TEXT DEFAULT 'customer',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                customer_name TEXT NOT NULL,
                customer_email TEXT NOT NULL,
                phone TEXT NOT NULL,
                shipping_address TEXT NOT NULL,
                city TEXT NOT NULL,
                total_amount REAL NOT NULL,
                status TEXT DEFAULT 'pending',
                payment_method TEXT DEFAULT 'COD',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                product_title TEXT NOT NULL,
                color_name TEXT,
                size_code TEXT,
                quantity INTEGER NOT NULL,
                price REAL NOT NULL
            );
            CREATE TABLE IF NOT EXISTS cart (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id TEXT NOT NULL,
                product_id INTEGER NOT NULL,
                color_id INTEGER,
                size_id INTEGER,
                quantity INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS wishlist (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id TEXT NOT NULL,
                product_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed SQLite if empty
        $check = $pdo->query("SELECT COUNT(*) as cnt FROM products")->fetch();
        if ($check['cnt'] == 0) {
            $pdo->exec("
                INSERT INTO categories (id, name, slug, image, icon) VALUES
                (1, 'Running Gear', 'running-gear', 'images/cat_running.jpg', 'fa-running'),
                (2, 'Football', 'football', 'images/cat_football.jpg', 'fa-futbol'),
                (3, 'Volleyball', 'volleyball', 'images/cat_volleyball.jpg', 'fa-volleyball-ball'),
                (4, 'Basketball', 'basketball', 'images/cat_basketball.jpg', 'fa-basketball-ball'),
                (5, 'Training & Gym', 'training-gym', 'images/cat_gym.jpg', 'fa-dumbbell'),
                (6, 'Accessories & Fitness', 'accessories', 'images/cat_accessories.jpg', 'fa-heartbeat');

                INSERT INTO colors (id, name, hex_code, image_swatch) VALUES
                (1, 'Volt Green / Black', '#39FF14', 'images/nike_pegasus_40.png'),
                (2, 'Ocean Blue / Silver', '#0077BE', 'images/nike_pegasus_angle2.png'),
                (3, 'Stealth Black', '#1A1A1A', 'images/adidas_tshirt.png'),
                (4, 'Crimson Red', '#DC143C', NULL),
                (5, 'Pure White', '#FFFFFF', NULL);

                INSERT INTO sizes (id, size_code) VALUES
                (1, 'US 8'), (2, 'US 9'), (3, 'US 10'), (4, 'US 11'), (5, 'S'), (6, 'M'), (7, 'L'), (8, 'XL');

                INSERT INTO products (id, category_id, title, brand, description, price, sale_price, rating, reviews_count, main_image, secondary_image, is_featured) VALUES
                (1, 1, 'NIKE AIR ZOOM PEGASUS 40', 'Nike', 'Engineered for high performance and maximum comfort. Features responsive Zoom Air units, breathable mesh upper, and durable waffle outsole for optimal track traction.', 189.00, 149.00, 4.9, 128, 'images/nike_pegasus_40.png', 'images/nike_pegasus_angle2.png', 1),
                (2, 5, 'ADIDAS AEROREADY PERFORMANCE TEE', 'Adidas', 'Lightweight moisture-wicking training shirt designed to keep you cool and dry during intense workout sessions.', 45.00, 34.99, 4.8, 86, 'images/adidas_tshirt.png', 'images/adidas_tshirt.png', 1),
                (3, 2, 'PUMA ORBITA MATCH PRO FOOTBALL', 'Puma', 'FIFA Quality Pro certified match ball. High-frequency molded 12-panel construction for perfect aerodynamics.', 85.00, 69.00, 4.9, 64, 'images/puma_match_ball.png', 'images/puma_match_ball.png', 1),
                (4, 5, 'PRO POWER GYM & WEIGHTLIFTING GLOVES', 'SprintGear', 'Heavy-duty workout gloves with integrated wrist support wrap and cushioned palm padding.', 35.00, 24.50, 4.7, 94, 'images/gym_gloves.png', 'images/gym_gloves.png', 1),
                (5, 3, 'MIKASA V200W OFFICIAL VOLLEYBALL', 'Mikasa', 'FIVB Official Game Ball featuring balanced 18-panel aerodynamic design.', 95.00, 79.99, 5.0, 42, 'images/cat_volleyball.jpg', 'images/cat_volleyball.jpg', 1),
                (6, 4, 'NIKE KOBE 6 PROTRO BASKETBALL SHOES', 'Nike', 'Low-profile court shoe with Zoom Turbo response for explosive cuts.', 210.00, 185.00, 4.9, 150, 'images/cat_basketball.jpg', 'images/cat_basketball.jpg', 1);

                INSERT INTO product_variants (product_id, color_id, size_id, sku, stock_qty, variant_image) VALUES
                (1, 1, 2, 'PEG40-VOLT-9', 25, 'images/nike_pegasus_40.png'),
                (1, 2, 2, 'PEG40-BLUE-9', 15, 'images/nike_pegasus_angle2.png');
            ");
        }
    } catch (Exception $fallback_e) {
        die("Database Connection Error: " . $fallback_e->getMessage());
    }
}

// Session helper for Cart and Wishlist Session IDs
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

function getCartCount($pdo, $session_id) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE session_id = ?");
    $stmt->execute([$session_id]);
    $row = $stmt->fetch();
    return $row['total'] ? (int)$row['total'] : 0;
}

function getWishlistCount($pdo, $session_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM wishlist WHERE session_id = ?");
    $stmt->execute([$session_id]);
    $row = $stmt->fetch();
    return $row['total'] ? (int)$row['total'] : 0;
}