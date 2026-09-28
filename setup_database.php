<?php
// setup_database.php - Sprint Gear Database Setup & Data Seeder Script

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'sprint_gear';
$ports = ['3307', '3306'];

echo "<!DOCTYPE html><html><head><title>Sprint Gear Database Setup</title>";
echo "<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f172a; color: #f8fafc; padding: 2rem; }
.card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 2rem; max-width: 800px; margin: 0 auto; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
h1 { color: #ef4444; border-bottom: 2px solid #ef4444; padding-bottom: 0.5rem; margin-top: 0; }
.success { color: #10b981; font-weight: bold; }
.info { color: #3b82f6; }
.log { background: #090d16; padding: 1rem; border-radius: 8px; font-family: monospace; height: 300px; overflow-y: auto; color: #a7f3d0; margin: 1rem 0; font-size: 0.9rem; }
.btn { display: inline-block; background: #ef4444; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 1rem; }
.btn:hover { background: #dc2626; }
</style></head><body><div class='card'>";

echo "<h1>⚡ Sprint Gear Database Installer</h1>";
echo "<div class='log'>";

function logMsg($msg) {
    echo htmlspecialchars($msg) . "<br>";
    if (ob_get_level() > 0) ob_flush();
    flush();
}

$pdo = null;
$activePort = null;

foreach ($ports as $port) {
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $activePort = $port;
        logMsg("Connected to MySQL Server successfully on port $port.");
        break;
    } catch (PDOException $e) {
        // try next port
    }
}

if (!$pdo) {
    echo "</div>";
    echo "<h2 style='color:#ef4444;'>❌ Database Setup Failed</h2>";
    echo "<p style='color:#f87171;'>Error: Could not connect to MySQL server on ports 3307 or 3306.</p>";
    echo "</div></body></html>";
    exit;
}

try {
    // 2. Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    logMsg("Database `$dbname` created or verified.");

    // Select DB
    $pdo->exec("USE `$dbname`");

    // Disable foreign key checks while resetting/building
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Drop tables if re-running
    $tables = ['contact_messages', 'reviews', 'order_items', 'orders', 'wishlist_items', 'wishlists', 'cart_items', 'carts', 'product_variants', 'sizes', 'colors', 'product_images', 'products', 'subcategories', 'categories', 'addresses', 'users', 'newsletter_subscribers'];
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    logMsg("Cleaned existing tables.");

    // 3. Create Schema Tables

    // Contact Messages
    $pdo->exec("CREATE TABLE contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        subject VARCHAR(200),
        message TEXT NOT NULL,
        status ENUM('New', 'Read', 'Replied') DEFAULT 'New',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    logMsg("Created table: contact_messages");

    // Users
    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(30),
        role ENUM('admin', 'customer') DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    logMsg("Created table: users");

    // Addresses
    $pdo->exec("CREATE TABLE addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        address_line1 VARCHAR(255) NOT NULL,
        address_line2 VARCHAR(255),
        city VARCHAR(100) NOT NULL,
        district VARCHAR(100) NOT NULL,
        postal_code VARCHAR(20) NOT NULL,
        is_default TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    logMsg("Created table: addresses");

    // Categories
    $pdo->exec("CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(100) NOT NULL UNIQUE,
        image VARCHAR(255),
        icon VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    logMsg("Created table: categories");

    // Subcategories
    $pdo->exec("CREATE TABLE subcategories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    logMsg("Created table: subcategories");

    // Products
    $pdo->exec("CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        subcategory_id INT NOT NULL,
        brand VARCHAR(100) NOT NULL,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        gender ENUM('Male', 'Female', 'Kids', 'Unisex') NOT NULL DEFAULT 'Unisex',
        description TEXT,
        short_description TEXT,
        price DECIMAL(10,2) NOT NULL,
        discount_price DECIMAL(10,2) DEFAULT NULL,
        main_image VARCHAR(255) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        is_featured TINYINT(1) DEFAULT 0,
        sport_type VARCHAR(100),
        material VARCHAR(100),
        country_of_manufacture VARCHAR(100),
        sku VARCHAR(100) UNIQUE,
        rating DECIMAL(3,2) DEFAULT 4.50,
        reviews_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    logMsg("Created table: products");

    // Product Additional Images
    $pdo->exec("CREATE TABLE product_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_url VARCHAR(255) NOT NULL,
        display_order INT DEFAULT 0,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Colors
    $pdo->exec("CREATE TABLE colors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        hex_code VARCHAR(10) NOT NULL
    ) ENGINE=InnoDB");

    // Sizes
    $pdo->exec("CREATE TABLE sizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        category_type ENUM('Shoes', 'Apparel', 'Universal') DEFAULT 'Universal'
    ) ENGINE=InnoDB");

    // Product Variants
    $pdo->exec("CREATE TABLE product_variants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        color_id INT NOT NULL,
        size_id INT NOT NULL,
        sku VARCHAR(100) UNIQUE,
        stock INT NOT NULL DEFAULT 0,
        variant_price DECIMAL(10,2) DEFAULT NULL,
        variant_image VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE,
        FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    logMsg("Created table: product_variants");

    // Carts & Cart Items
    $pdo->exec("CREATE TABLE carts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        session_id VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE cart_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cart_id INT NOT NULL,
        product_id INT NOT NULL,
        variant_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Wishlists & Items
    $pdo->exec("CREATE TABLE wishlists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE wishlist_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        wishlist_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (wishlist_id) REFERENCES wishlists(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Orders & Order Items
    $pdo->exec("CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(50) NOT NULL UNIQUE,
        user_id INT DEFAULT NULL,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(150) NOT NULL,
        phone VARCHAR(30) NOT NULL,
        shipping_address TEXT NOT NULL,
        city VARCHAR(100) NOT NULL,
        district VARCHAR(100) NOT NULL,
        postal_code VARCHAR(20) NOT NULL,
        payment_method ENUM('COD', 'Card') NOT NULL DEFAULT 'COD',
        payment_status ENUM('Pending', 'Paid', 'Failed') NOT NULL DEFAULT 'Pending',
        order_status ENUM('Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Returned') NOT NULL DEFAULT 'Pending',
        subtotal DECIMAL(10,2) NOT NULL,
        shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 500.00,
        discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        variant_id INT NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        color_name VARCHAR(50) NOT NULL,
        size_name VARCHAR(50) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        quantity INT NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Reviews
    $pdo->exec("CREATE TABLE reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        user_name VARCHAR(100) NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Newsletter Subscribers
    $pdo->exec("CREATE TABLE newsletter_subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    logMsg("Schema tables created successfully.");

    // -------------------------------------------------------------
    // SEED INITIAL DATA
    // -------------------------------------------------------------

    // 1. Seed Users (Admin & Customer)
    $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
    $customerPass = password_hash('user123', PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Sprint Admin', 'admin@sprintgear.lk', $adminPass, '+94 77 123 4567', 'admin']);
    $adminId = $pdo->lastInsertId();

    $stmt->execute(['John Doe', 'john@example.com', $customerPass, '+94 71 987 6543', 'customer']);
    $customerId = $pdo->lastInsertId();

    // Default address for customer
    $stmtAddr = $pdo->prepare("INSERT INTO addresses (user_id, address_line1, address_line2, city, district, postal_code, is_default) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmtAddr->execute([$customerId, 'No. 45 Galle Road', 'Kollupitiya', 'Colombo', 'Colombo', '00300']);

    logMsg("Seeded default users (Admin: admin@sprintgear.lk / admin123, Customer: john@example.com / user123).");

    // 2. Seed Colors
    $colors = [
        ['Black', '#111827'],
        ['White', '#FFFFFF'],
        ['Red', '#EF4444'],
        ['Blue', '#2563EB'],
        ['Green', '#10B981'],
        ['Yellow', '#F59E0B'],
        ['Grey', '#6B7280'],
        ['Navy', '#1E3A8A'],
        ['Orange', '#F97316']
    ];
    $stmtColor = $pdo->prepare("INSERT INTO colors (name, hex_code) VALUES (?, ?)");
    $colorMap = [];
    $colorCodes = ['Black'=>'BLK', 'White'=>'WHT', 'Red'=>'RED', 'Blue'=>'BLU', 'Green'=>'GRN', 'Yellow'=>'YEL', 'Grey'=>'GRY', 'Navy'=>'NVY', 'Orange'=>'ORG'];

    foreach ($colors as $c) {
        $stmtColor->execute([$c[0], $c[1]]);
        $colorMap[$c[0]] = $pdo->lastInsertId();
    }

    // 3. Seed Sizes
    $sizes = [
        // Shoe Sizes
        ['38', 'Shoes'], ['39', 'Shoes'], ['40', 'Shoes'], ['41', 'Shoes'], ['42', 'Shoes'], ['43', 'Shoes'], ['44', 'Shoes'], ['45', 'Shoes'],
        // Apparel Sizes
        ['XS', 'Apparel'], ['S', 'Apparel'], ['M', 'Apparel'], ['L', 'Apparel'], ['XL', 'Apparel'], ['XXL', 'Apparel'],
        // Universal
        ['One Size', 'Universal'], ['Standard', 'Universal']
    ];
    $stmtSize = $pdo->prepare("INSERT INTO sizes (name, category_type) VALUES (?, ?)");
    $sizeMap = [];
    foreach ($sizes as $s) {
        $stmtSize->execute([$s[0], $s[1]]);
        $sizeMap[$s[0]] = $pdo->lastInsertId();
    }

    // 4. Seed Categories and Subcategories
    $categoryData = [
        'Running Gear' => [
            'image' => 'uploads/categories/cat_running_gear.jpg',
            'icon' => 'fa-running',
            'subs' => ['Shoes', 'Shorts', 'T-Shirts', 'Tops', 'Socks', 'Accessories']
        ],
        'Football' => [
            'image' => 'uploads/categories/cat_football.jpg',
            'icon' => 'fa-football-ball',
            'subs' => ['Football Shoes', 'Jerseys', 'Shorts', 'Training Wear', 'Footballs', 'Accessories']
        ],
        'Volleyball' => [
            'image' => 'uploads/categories/cat_volleyball.jpg',
            'icon' => 'fa-volleyball-ball',
            'subs' => ['Volleyball Shoes', 'Jerseys', 'Shorts', 'T-Shirts', 'Volleyballs', 'Accessories']
        ],
        'Basketball' => [
            'image' => 'uploads/categories/cat_basketball.jpg',
            'icon' => 'fa-basketball-ball',
            'subs' => ['Basketball Shoes', 'Jerseys', 'Shorts', 'T-Shirts', 'Basketballs', 'Accessories']
        ],
        'Training & Fitness' => [
            'image' => 'uploads/categories/cat_training_fitness.jpg',
            'icon' => 'fa-dumbbell',
            'subs' => ['Training Shoes', 'T-Shirts', 'Shorts', 'Track Pants', 'Training Accessories', 'Bags']
        ],
        'Accessories' => [
            'image' => 'uploads/categories/cat_accessories.jpg',
            'icon' => 'fa-gem',
            'subs' => ['Sports Bags', 'Socks', 'Caps', 'Water Bottles', 'Wristbands', 'Other Accessories']
        ]
    ];

    $stmtCat = $pdo->prepare("INSERT INTO categories (name, slug, image, icon) VALUES (?, ?, ?, ?)");
    $stmtSub = $pdo->prepare("INSERT INTO subcategories (category_id, name, slug) VALUES (?, ?, ?)");

    $catMap = [];
    $subMap = [];

    foreach ($categoryData as $catName => $cMeta) {
        $catSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $catName), '-'));
        $stmtCat->execute([$catName, $catSlug, $cMeta['image'], $cMeta['icon']]);
        $catId = $pdo->lastInsertId();
        $catMap[$catName] = $catId;

        foreach ($cMeta['subs'] as $subName) {
            $subSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $catName . '-' . $subName), '-'));
            $stmtSub->execute([$catId, $subName, $subSlug]);
            $subId = $pdo->lastInsertId();
            $subMap[$catName][$subName] = $subId;
        }
    }
    logMsg("Seeded 6 Categories and 36 Subcategories.");

    // 5. Seed Products (Mandatory: AT LEAST 5 Products per subcategory)
    $brands = ['Nike', 'Adidas', 'ASICS', 'Puma', 'Under Armour', 'Mizuno', 'Molten', 'Spalding', 'Wilson', 'Sprint Gear'];
    $genders = ['Male', 'Female', 'Kids', 'Unisex'];

    $stmtProd = $pdo->prepare("INSERT INTO products (category_id, subcategory_id, brand, name, slug, gender, description, short_description, price, discount_price, main_image, status, is_featured, sport_type, material, country_of_manufacture, sku, rating, reviews_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?, ?, ?)");

    $stmtVar = $pdo->prepare("INSERT INTO product_variants (product_id, color_id, size_id, sku, stock, variant_price, variant_image) VALUES (?, ?, ?, ?, ?, ?, ?)");

    $stmtRev = $pdo->prepare("INSERT INTO reviews (product_id, user_id, user_name, rating, comment) VALUES (?, ?, ?, ?, ?)");

    $totalProductsCount = 0;

    // Custom product names generator matrix per subcategory
    foreach ($categoryData as $catName => $cMeta) {
        $catId = $catMap[$catName];

        foreach ($cMeta['subs'] as $subName) {
            $subId = $subMap[$catName][$subName];

            // Generate 5 distinct realistic products for THIS subcategory
            for ($pIndex = 1; $pIndex <= 5; $pIndex++) {
                $totalProductsCount++;

                $brand = $brands[array_rand($brands)];
                $gender = $genders[array_rand($genders)];

                // Naming conventions based on requirements
                if ($catName === 'Running Gear' && $subName === 'Shoes' && $pIndex === 1) {
                    $prodName = "Nike Air Zoom Pegasus 40";
                    $brand = "Nike";
                    $gender = "Unisex";
                    $price = 32500.00;
                    $discountPrice = 29900.00;
                    $mainImage = "uploads/products/nike_pegasus_40_black.jpg";
                    $isFeatured = 1;
                } else if ($catName === 'Running Gear' && $subName === 'Shoes' && $pIndex === 2) {
                    $prodName = "Adidas Adizero Boston 12";
                    $brand = "Adidas";
                    $gender = "Male";
                    $price = 36000.00;
                    $discountPrice = 33500.00;
                    $mainImage = "uploads/categories/cat_running_gear.jpg";
                    $isFeatured = 1;
                } else if ($catName === 'Running Gear' && $subName === 'Shoes' && $pIndex === 3) {
                    $prodName = "ASICS Gel-Kayano 30";
                    $brand = "ASICS";
                    $gender = "Female";
                    $price = 38500.00;
                    $discountPrice = null;
                    $mainImage = "uploads/categories/cat_running_gear.jpg";
                    $isFeatured = 1;
                } else {
                    $prodName = "$brand $subName Pro $pIndex";
                    $price = rand(35, 380) * 100.00; // Rs 3,500 - 38,000
                    $hasDiscount = (rand(1, 100) <= 40);
                    $discountPrice = $hasDiscount ? round($price * 0.85, -2) : null;
                    $mainImage = $cMeta['image']; // Realistic photography category image
                    $isFeatured = (rand(1, 100) <= 25) ? 1 : 0;
                }

                $prodSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $prodName . '-' . $totalProductsCount), '-'));
                $sku = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $catName), 0, 2) . substr(preg_replace('/[^A-Za-z0-9]/', '', $subName), 0, 2) . "-" . str_pad($totalProductsCount, 4, '0', STR_PAD_LEFT));

                $shortDesc = "High-performance $subName engineered for maximum comfort, durability, and top athletic output.";
                $desc = "Experience ultimate athletic capability with the $prodName. Crafted with premium high-grade performance materials designed for professionals and active enthusiasts. Features moisture-wicking technology, high tensile strength stitching, ergonomic fit, and sleek modern aesthetic styling.";
                $material = ($subName === 'Shoes' || strpos($subName, 'Shoes') !== false) ? "Breathable Mesh & Synthetic Leather" : "Polyester Dri-FIT Blend";
                $country = (rand(0, 1) === 0) ? "Vietnam" : "Indonesia";
                $rating = rand(40, 50) / 10.0;
                $reviewsCount = rand(5, 48);

                $stmtProd->execute([
                    $catId, $subId, $brand, $prodName, $prodSlug, $gender,
                    $desc, $shortDesc, $price, $discountPrice, $mainImage,
                    $isFeatured, $catName, $material, $country, $sku, $rating, $reviewsCount
                ]);
                $productId = $pdo->lastInsertId();

                // Generate Variants for each product (Colors x Sizes)
                $isShoe = (strpos(strtolower($subName), 'shoe') !== false);
                $availableSizes = $isShoe ? ['38', '39', '40', '41', '42', '43'] : ['S', 'M', 'L', 'XL'];
                if ($subName === 'Accessories' || strpos($subName, 'Bags') !== false || strpos($subName, 'Bottle') !== false || strpos($subName, 'Balls') !== false || $subName === 'Footballs' || $subName === 'Volleyballs' || $subName === 'Basketballs') {
                    $availableSizes = ['Standard'];
                }

                $availableColors = ['Black', 'White', 'Blue', 'Red'];

                foreach ($availableColors as $cIdx => $cName) {
                    $colorId = $colorMap[$cName];
                    $cCode = isset($colorCodes[$cName]) ? $colorCodes[$cName] : 'CLR';

                    // Set variant specific image for Pegasus 40 demo
                    $variantImage = null;
                    if ($prodName === "Nike Air Zoom Pegasus 40") {
                        if ($cName === 'Black') $variantImage = "uploads/products/nike_pegasus_40_black.jpg";
                        if ($cName === 'White') $variantImage = "uploads/variants/nike_pegasus_40_white.jpg";
                        if ($cName === 'Blue') $variantImage = "uploads/variants/nike_pegasus_40_blue.jpg";
                    }

                    foreach ($availableSizes as $sName) {
                        if (!isset($sizeMap[$sName])) continue;
                        $sizeId = $sizeMap[$sName];

                        $varSku = $sku . "-" . $cCode . "-" . $sName;

                        // Give Black size 41 zero stock to test out-of-stock validation!
                        $stock = ($cName === 'Black' && $sName === '41') ? 0 : rand(4, 25);

                        $stmtVar->execute([
                            $productId, $colorId, $sizeId, $varSku, $stock, null, $variantImage
                        ]);
                    }
                }

                // Add sample review
                $stmtRev->execute([
                    $productId, $customerId, 'John Doe', rand(4, 5), "Excellent performance quality gear! Highly recommended product from Sprint Gear."
                ]);
            }
        }
    }

    logMsg("Seeded total of $totalProductsCount dynamic products across all 36 subcategories (5 products per subcategory satisfying requirement #33).");
    logMsg("Product variants, stock allocations, colors, sizes, and initial reviews seeded successfully!");

    echo "</div>";
    echo "<h2 class='success'>✅ Database Setup Completed Successfully!</h2>";
    echo "<p class='info'>The database <strong>$dbname</strong> is ready on port $activePort with full schema, categories, subcategories, products, variants, colors, sizes, and seed data.</p>";
    echo "<a href='index.php' class='btn'>Go to Sprint Gear Store Front →</a>";

} catch (PDOException $e) {
    echo "</div>";
    echo "<h2 style='color:#ef4444;'>❌ Database Setup Failed</h2>";
    echo "<p style='color:#f87171;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
