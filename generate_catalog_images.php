<?php
// generate_catalog_images.php - Generate 180 Unique Product Images & Update Database
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();

$products = $pdo->query("
    SELECT p.id, p.name, p.brand, c.name as category, s.name as subcategory 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    JOIN subcategories s ON p.subcategory_id = s.id 
    ORDER BY p.id ASC
")->fetchAll();

$outputDir = __DIR__ . '/uploads/products';
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// Brand color themes
$brandColors = [
    'Nike' => ['primary' => '#111827', 'accent' => '#EF4444', 'secondary' => '#3B82F6'],
    'Adidas' => ['primary' => '#0F172A', 'accent' => '#10B981', 'secondary' => '#F59E0B'],
    'Puma' => ['primary' => '#1E1B4B', 'accent' => '#EC4899', 'secondary' => '#06B6D4'],
    'ASICS' => ['primary' => '#030712', 'accent' => '#3B82F6', 'secondary' => '#10B981'],
    'Under Armour' => ['primary' => '#18181B', 'accent' => '#F97316', 'secondary' => '#8B5CF6'],
    'Mizuno' => ['primary' => '#0F2942', 'accent' => '#06B6D4', 'secondary' => '#EAB308'],
    'Wilson' => ['primary' => '#450A0A', 'accent' => '#EF4444', 'secondary' => '#F97316'],
    'Spalding' => ['primary' => '#431407', 'accent' => '#F97316', 'secondary' => '#EAB308'],
    'Molten' => ['primary' => '#1E3A8A', 'accent' => '#3B82F6', 'secondary' => '#EF4444'],
    'Sprint Gear' => ['primary' => '#111827', 'accent' => '#EF4444', 'secondary' => '#F59E0B']
];

// Color variations per product ID index (1..5)
$colorPalettes = [
    1 => ['main' => '#EF4444', 'secondary' => '#1E293B', 'light' => '#FCA5A5'], // Red
    2 => ['main' => '#2563EB', 'secondary' => '#0F172A', 'light' => '#93C5FD'], // Blue
    3 => ['main' => '#10B981', 'secondary' => '#064E3B', 'light' => '#6EE7B7'], // Green
    4 => ['main' => '#F59E0B', 'secondary' => '#78350F', 'light' => '#FDE68A'], // Yellow/Gold
    5 => ['main' => '#8B5CF6', 'secondary' => '#4C1D95', 'light' => '#C4B5FD'], // Purple
];

echo "Generating 180 Unique Product Images...\n";
echo "===============================================================\n";

$updatedCount = 0;

foreach ($products as $p) {
    $id = $p['id'];
    $name = $p['name'];
    $brand = $p['brand'] ?: 'Sprint Gear';
    $cat = $p['category'];
    $sub = $p['subcategory'];
    
    // Variant index (1 to 5)
    $variantIdx = (($id - 1) % 5) + 1;
    $palette = $colorPalettes[$variantIdx];
    $brandTheme = $brandColors[$brand] ?? $brandColors['Sprint Gear'];

    $mainColor = $palette['main'];
    $secColor = $palette['secondary'];
    $accentColor = $brandTheme['accent'];
    
    $filename = "product_" . $id . "_" . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $name)) . ".svg";
    $filepath = $outputDir . '/' . $filename;
    $dbPath = "uploads/products/" . $filename;

    // Build unique SVG graphic depending on subcategory type
    $svg = buildProductSvg($id, $name, $brand, $cat, $sub, $mainColor, $secColor, $accentColor, $variantIdx);

    file_put_contents($filepath, $svg);

    // Update DB
    $stmtUpd = $pdo->prepare("UPDATE products SET main_image = ? WHERE id = ?");
    $stmtUpd->execute([$dbPath, $id]);
    $updatedCount++;

    if ($id <= 10 || $id % 25 === 0 || $id === 180) {
        echo "Product #{$id}: {$name} -> {$dbPath}\n";
    }
}

echo "===============================================================\n";
echo "Successfully generated {$updatedCount} unique product images and updated database!\n";

// Function to generate high quality SVG studio product illustrations
function buildProductSvg($id, $name, $brand, $cat, $sub, $mainColor, $secColor, $accentColor, $variantIdx) {
    $cleanName = htmlspecialchars($name);
    $cleanBrand = strtoupper(htmlspecialchars($brand));
    $cleanSub = strtoupper(htmlspecialchars($sub));

    // Common SVG Header & Studio Backdrop
    $svg = '<?xml version="1.0" encoding="UTF-8"?>';
    $svg .= '<svg width="600" height="600" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<defs>';
    
    // Gradients
    $svg .= '<radialGradient id="bgGrad" cx="50%" cy="40%" r="60%">';
    $svg .= '<stop offset="0%" stop-color="#FFFFFF"/>';
    $svg .= '<stop offset="70%" stop-color="#F1F5F9"/>';
    $svg .= '<stop offset="100%" stop-color="#E2E8F0"/>';
    $svg .= '</radialGradient>';

    $svg .= '<radialGradient id="shadowGrad" cx="50%" cy="50%" r="50%">';
    $svg .= '<stop offset="0%" stop-color="rgba(15, 23, 42, 0.22)"/>';
    $svg .= '<stop offset="60%" stop-color="rgba(15, 23, 42, 0.08)"/>';
    $svg .= '<stop offset="100%" stop-color="rgba(15, 23, 42, 0)"/>';
    $svg .= '</radialGradient>';

    $svg .= '<linearGradient id="prodGrad" x1="0%" y1="0%" x2="100%" y2="100%">';
    $svg .= '<stop offset="0%" stop-color="' . $mainColor . '"/>';
    $svg .= '<stop offset="100%" stop-color="' . $secColor . '"/>';
    $svg .= '</linearGradient>';

    $svg .= '<linearGradient id="accentGrad" x1="0%" y1="0%" x2="100%" y2="0%">';
    $svg .= '<stop offset="0%" stop-color="' . $accentColor . '"/>';
    $svg .= '<stop offset="100%" stop-color="#FFFFFF"/>';
    $svg .= '</linearGradient>';

    $svg .= '</defs>';

    // Background Studio Canvas
    $svg .= '<rect width="600" height="600" fill="url(#bgGrad)"/>';

    // Studio Floor Grid Lines (Subtle)
    $svg .= '<line x1="0" y1="460" x2="600" y2="460" stroke="#CBD5E1" stroke-width="1" opacity="0.4"/>';

    // Shadow underneath product
    $svg .= '<ellipse cx="300" cy="460" rx="190" ry="24" fill="url(#shadowGrad)"/>';

    // Product Graphic Illustration based on Subcategory
    $svg .= '<g transform="translate(0, 0)">';

    if (strpos($sub, 'Shoes') !== false) {
        if ($sub === 'Football Shoes') {
            // Football Cleats
            $svg .= '<path d="M 120 380 Q 200 400 450 360 Q 480 340 460 300 C 440 250 380 240 330 260 L 260 270 Q 200 240 160 260 C 130 280 110 330 120 380 Z" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="4"/>';
            // Cleats studs
            $svg .= '<polygon points="160,385 175,415 190,385" fill="#EF4444"/>';
            $svg .= '<polygon points="230,385 245,415 260,385" fill="#EF4444"/>';
            $svg .= '<polygon points="370,375 385,405 400,375" fill="#EF4444"/>';
            $svg .= '<polygon points="430,365 445,395 460,365" fill="#EF4444"/>';
            // Swoosh / Stripe accent
            $svg .= '<path d="M 220 300 Q 300 270 420 310 Q 340 340 220 300 Z" fill="' . $accentColor . '"/>';
            // Laces
            $svg .= '<line x1="280" y1="270" x2="310" y2="295" stroke="#FFFFFF" stroke-width="5" stroke-linecap="round"/>';
            $svg .= '<line x1="300" y1="265" x2="330" y2="290" stroke="#FFFFFF" stroke-width="5" stroke-linecap="round"/>';
            $svg .= '<line x1="320" y1="260" x2="350" y2="285" stroke="#FFFFFF" stroke-width="5" stroke-linecap="round"/>';
        } elseif ($sub === 'Basketball Shoes') {
            // High top Basketball Sneakers
            $svg .= '<path d="M 130 380 Q 210 395 450 370 C 480 340 470 240 420 200 C 380 170 340 240 290 255 L 210 260 Q 150 260 130 320 Z" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="4"/>';
            // Padded ankle collar
            $svg .= '<path d="M 370 210 Q 420 180 440 220 C 410 250 370 230 370 210 Z" fill="' . $accentColor . '"/>';
            // Thick Basketball sole
            $svg .= '<path d="M 125 370 L 455 360 L 450 390 L 130 395 Z" fill="#FFFFFF" stroke="#0F172A" stroke-width="3"/>';
            // Heel accent
            $svg .= '<path d="M 390 260 Q 450 280 430 360 L 390 360 Z" fill="rgba(255,255,255,0.2)"/>';
        } else {
            // Running / Training / Volleyball Shoes
            $svg .= '<path d="M 120 370 Q 220 390 460 350 C 490 320 460 260 410 240 C 350 240 310 265 240 270 Q 160 260 130 310 Z" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="4"/>';
            // Thick running sole
            $svg .= '<path d="M 115 365 Q 220 390 465 345 L 460 380 Q 220 410 120 385 Z" fill="#FFFFFF" stroke="#0F172A" stroke-width="3"/>';
            $svg .= '<path d="M 120 380 Q 220 405 460 375 L 455 385 Q 220 415 125 390 Z" fill="' . $accentColor . '"/>';
            // Dynamic side stripes
            $svg .= '<path d="M 230 300 Q 320 280 410 315 Q 310 335 240 325 Z" fill="' . $accentColor . '"/>';
            // Laces
            $svg .= '<line x1="270" y1="275" x2="300" y2="295" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round"/>';
            $svg .= '<line x1="290" y1="270" x2="320" y2="290" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round"/>';
            $svg .= '<line x1="310" y1="265" x2="340" y2="285" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round"/>';
        }
    } elseif (strpos($sub, 'Shorts') !== false) {
        // Athletic Shorts
        $svg .= '<path d="M 180 180 L 420 180 L 450 360 L 320 360 L 300 240 L 280 360 L 150 360 Z" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="4"/>';
        // Elastic waistband
        $svg .= '<rect x="175" y="170" width="250" height="30" rx="6" fill="' . $accentColor . '" stroke="#0F172A" stroke-width="3"/>';
        // Drawstrings
        $svg .= '<path d="M 290 195 Q 285 230 275 250" stroke="#FFFFFF" stroke-width="4" fill="none" stroke-linecap="round"/>';
        $svg .= '<path d="M 310 195 Q 315 230 325 250" stroke="#FFFFFF" stroke-width="4" fill="none" stroke-linecap="round"/>';
        // Side piping stripes
        $svg .= '<line x1="185" y1="200" x2="160" y2="355" stroke="#FFFFFF" stroke-width="6"/>';
        $svg .= '<line x1="415" y1="200" x2="440" y2="355" stroke="#FFFFFF" stroke-width="6"/>';
    } elseif (strpos($sub, 'Jerseys') !== false || strpos($sub, 'T-Shirts') !== false || strpos($sub, 'Tops') !== false || strpos($sub, 'Training Wear') !== false) {
        // Sports Jersey / T-Shirt
        $svg .= '<path d="M 220 160 Q 300 190 380 160 L 480 210 L 430 280 L 390 250 L 390 420 L 210 420 L 210 250 L 170 280 L 120 210 Z" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="4"/>';
        // Collar
        $svg .= '<path d="M 220 160 Q 300 200 380 160 Q 300 175 220 160 Z" fill="' . $accentColor . '" stroke="#0F172A" stroke-width="2"/>';
        // Sleeve trim
        $svg .= '<line x1="120" y1="210" x2="170" y2="280" stroke="' . $accentColor . '" stroke-width="8"/>';
        $svg .= '<line x1="480" y1="210" x2="430" y2="280" stroke="' . $accentColor . '" stroke-width="8"/>';
        // Athletic Chest Stripe / Number
        if (strpos($sub, 'Jerseys') !== false) {
            $svg .= '<text x="300" y="320" font-family="sans-serif" font-size="70" font-weight="900" fill="#FFFFFF" text-anchor="middle" opacity="0.9">' . sprintf("%02d", ($id % 99) + 1) . '</text>';
        } else {
            $svg .= '<rect x="230" y="270" width="140" height="12" rx="6" fill="' . $accentColor . '"/>';
            $svg .= '<rect x="250" y="295" width="100" height="8" rx="4" fill="#FFFFFF" opacity="0.8"/>';
        }
    } elseif (strpos($sub, 'Track Pants') !== false) {
        // Track Pants / Joggers
        $svg .= '<path d="M 200 160 L 400 160 L 380 430 L 320 430 L 300 260 L 280 430 L 220 430 Z" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="4"/>';
        // Elastic waistband
        $svg .= '<rect x="195" y="150" width="210" height="25" rx="5" fill="' . $accentColor . '" stroke="#0F172A" stroke-width="2"/>';
        // Side stripes
        $svg .= '<line x1="205" y1="175" x2="225" y2="425" stroke="#FFFFFF" stroke-width="5"/>';
        $svg .= '<line x1="395" y1="175" x2="375" y2="425" stroke="#FFFFFF" stroke-width="5"/>';
        // Ankle cuffs
        $svg .= '<rect x="218" y="425" width="105" height="15" rx="3" fill="#0F172A"/>';
    } elseif (strpos($sub, 'Footballs') !== false) {
        // Soccer Ball
        $svg .= '<circle cx="300" cy="300" r="130" fill="#FFFFFF" stroke="#0F172A" stroke-width="5"/>';
        // Center Pentagon
        $svg .= '<polygon points="300,240 345,275 330,330 270,330 255,275" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="3"/>';
        // Surrounding lines
        $svg .= '<line x1="300" y1="240" x2="300" y2="175" stroke="#0F172A" stroke-width="3"/>';
        $svg .= '<line x1="345" y1="275" x2="410" y2="255" stroke="#0F172A" stroke-width="3"/>';
        $svg .= '<line x1="330" y1="330" x2="385" y2="380" stroke="#0F172A" stroke-width="3"/>';
        $svg .= '<line x1="270" y1="330" x2="215" y2="380" stroke="#0F172A" stroke-width="3"/>';
        $svg .= '<line x1="255" y1="275" x2="190" y2="255" stroke="#0F172A" stroke-width="3"/>';
        // Colored seam accents
        $svg .= '<circle cx="300" cy="300" r="130" fill="none" stroke="' . $accentColor . '" stroke-width="6" stroke-dasharray="20 40"/>';
    } elseif (strpos($sub, 'Basketballs') !== false) {
        // Basketball
        $svg .= '<circle cx="300" cy="300" r="135" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="5"/>';
        // Basketball Ribbing Lines
        $svg .= '<line x1="165" y1="300" x2="435" y2="300" stroke="#0F172A" stroke-width="5"/>';
        $svg .= '<line x1="300" y1="165" x2="300" y2="435" stroke="#0F172A" stroke-width="5"/>';
        $svg .= '<path d="M 210 190 C 260 240 260 360 210 410" fill="none" stroke="#0F172A" stroke-width="5"/>';
        $svg .= '<path d="M 390 190 C 340 240 340 360 390 410" fill="none" stroke="#0F172A" stroke-width="5"/>';
    } elseif (strpos($sub, 'Volleyballs') !== false) {
        // Volleyball
        $svg .= '<circle cx="300" cy="300" r="130" fill="#FFFFFF" stroke="#0F172A" stroke-width="5"/>';
        // Tricolor Wave Panels
        $svg .= '<path d="M 180 230 C 240 180 360 180 420 230 C 360 280 240 280 180 230 Z" fill="' . $mainColor . '" stroke="#0F172A" stroke-width="3"/>';
        $svg .= '<path d="M 180 370 C 240 320 360 320 420 370 C 360 420 240 420 180 370 Z" fill="' . $accentColor . '" stroke="#0F172A" stroke-width="3"/>';
        $svg .= '<path d="M 230 180 C 180 240 180 360 230 420 C 280 360 280 240 230 180 Z" fill="#3B82F6" opacity="0.7" stroke="#0F172A" stroke-width="3"/>';
    } elseif (strpos($sub, 'Bags') !== false) {
        // Gym Duffel Bag / Sports Bag
        $svg .= '<rect x="150" y="240" width="300" height="170" rx="40" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="4"/>';
        // Side Pockets
        $svg .= '<path d="M 150 240 L 150 410 Q 120 370 120 325 Q 120 280 150 240 Z" fill="' . $accentColor . '" stroke="#0F172A" stroke-width="3"/>';
        $svg .= '<path d="M 450 240 L 450 410 Q 480 370 480 325 Q 480 280 450 240 Z" fill="' . $accentColor . '" stroke="#0F172A" stroke-width="3"/>';
        // Handles
        $svg .= '<path d="M 220 240 C 220 160 380 160 380 240" fill="none" stroke="#0F172A" stroke-width="8" stroke-linecap="round"/>';
        $svg .= '<path d="M 220 240 C 220 160 380 160 380 240" fill="none" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round"/>';
        // Zipper line
        $svg .= '<line x1="160" y1="270" x2="440" y2="270" stroke="#0F172A" stroke-width="4" stroke-dasharray="8 4"/>';
    } elseif (strpos($sub, 'Water Bottles') !== false) {
        // Sports Water Bottle
        $svg .= '<rect x="230" y="190" width="140" height="230" rx="25" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="4"/>';
        // Bottle neck & cap
        $svg .= '<rect x="260" y="140" width="80" height="50" rx="8" fill="' . $accentColor . '" stroke="#0F172A" stroke-width="3"/>';
        $svg .= '<rect x="280" y="115" width="40" height="25" rx="5" fill="#0F172A"/>';
        // Rubber grip band
        $svg .= '<rect x="230" y="270" width="140" height="50" fill="#0F172A" opacity="0.85"/>';
        // Measurement scale ticks
        $svg .= '<line x1="340" y1="210" x2="355" y2="210" stroke="#FFFFFF" stroke-width="3"/>';
        $svg .= '<line x1="340" y1="235" x2="355" y2="235" stroke="#FFFFFF" stroke-width="3"/>';
        $svg .= '<line x1="340" y1="340" x2="355" y2="340" stroke="#FFFFFF" stroke-width="3"/>';
        $svg .= '<line x1="340" y1="365" x2="355" y2="365" stroke="#FFFFFF" stroke-width="3"/>';
    } elseif (strpos($sub, 'Caps') !== false) {
        // Baseball / Sports Cap
        $svg .= '<path d="M 180 320 C 180 200 420 200 420 320 Z" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="4"/>';
        // Curved Visor
        $svg .= '<path d="M 160 320 Q 300 350 480 325 C 450 360 190 360 160 320 Z" fill="' . $accentColor . '" stroke="#0F172A" stroke-width="3"/>';
        // Button on top
        $svg .= '<circle cx="300" cy="205" r="10" fill="#0F172A"/>';
        // Crown panels stitching
        $svg .= '<line x1="300" y1="205" x2="300" y2="320" stroke="rgba(255,255,255,0.4)" stroke-width="2"/>';
        $svg .= '<line x1="300" y1="205" x2="210" y2="300" stroke="rgba(255,255,255,0.4)" stroke-width="2"/>';
        $svg .= '<line x1="300" y1="205" x2="390" y2="300" stroke="rgba(255,255,255,0.4)" stroke-width="2"/>';
    } elseif (strpos($sub, 'Socks') !== false) {
        // Pair of Sports Socks
        $svg .= '<path d="M 210 160 L 270 160 L 270 330 Q 330 340 350 380 L 290 410 Q 210 370 210 330 Z" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="4"/>';
        $svg .= '<path d="M 310 160 L 370 160 L 370 330 Q 430 340 450 380 L 390 410 Q 310 370 310 330 Z" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="4"/>';
        // Sock cuffs
        $svg .= '<rect x="210" y="160" width="60" height="25" fill="' . $accentColor . '" stroke="#0F172A" stroke-width="2"/>';
        $svg .= '<rect x="310" y="160" width="60" height="25" fill="' . $accentColor . '" stroke="#0F172A" stroke-width="2"/>';
        // Heel & Toe pad
        $svg .= '<path d="M 350 380 L 290 410 Q 320 420 350 380 Z" fill="#0F172A"/>';
        $svg .= '<path d="M 450 380 L 390 410 Q 420 420 450 380 Z" fill="#0F172A"/>';
    } elseif (strpos($sub, 'Wristbands') !== false) {
        // Sweatband Wristbands
        $svg .= '<rect x="160" y="240" width="120" height="140" rx="20" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="4"/>';
        $svg .= '<rect x="320" y="240" width="120" height="140" rx="20" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="4"/>';
        // Embroidered accent logo
        $svg .= '<circle cx="220" cy="310" r="22" fill="' . $accentColor . '" stroke="#FFFFFF" stroke-width="3"/>';
        $svg .= '<circle cx="380" cy="310" r="22" fill="' . $accentColor . '" stroke="#FFFFFF" stroke-width="3"/>';
    } else {
        // General Sports Equipment / Accessories (Shin guards / Dumbbells / Training Gear)
        $svg .= '<rect x="180" y="200" width="240" height="200" rx="30" fill="url(#prodGrad)" stroke="#0F172A" stroke-width="4"/>';
        $svg .= '<circle cx="300" cy="300" r="60" fill="' . $accentColor . '" stroke="#0F172A" stroke-width="3"/>';
        $svg .= '<path d="M 270 300 L 330 300 M 300 270 L 300 330" stroke="#FFFFFF" stroke-width="8" stroke-linecap="round"/>';
    }

    $svg .= '</g>';

    // Top Brand Badge Pill
    $svg .= '<rect x="40" y="40" width="160" height="42" rx="8" fill="#0F172A"/>';
    $svg .= '<text x="120" y="67" font-family="system-ui, sans-serif" font-size="16" font-weight="900" fill="' . $accentColor . '" text-anchor="middle" letter-spacing="1">' . $cleanBrand . '</text>';

    // Variant Tag Badge (Top Right)
    $svg .= '<rect x="450" y="40" width="110" height="34" rx="17" fill="' . $mainColor . '"/>';
    $svg .= '<text x="505" y="62" font-family="system-ui, sans-serif" font-size="13" font-weight="800" fill="#FFFFFF" text-anchor="middle">STYLE #' . $variantIdx . '</text>';

    // Bottom Watermark Title
    $svg .= '<rect x="40" y="515" width="520" height="48" rx="8" fill="#FFFFFF" stroke="#E2E8F0" stroke-width="2"/>';
    $svg .= '<text x="60" y="545" font-family="system-ui, sans-serif" font-size="16" font-weight="800" fill="#0F172A">' . substr($cleanName, 0, 42) . '</text>';
    $svg .= '<text x="540" y="545" font-family="system-ui, sans-serif" font-size="13" font-weight="700" fill="#64748B" text-anchor="end">' . $cleanSub . '</text>';

    $svg .= '</svg>';

    return $svg;
}
