<?php
// generate_category_images.php - Verify & Update Category Real Images in Database
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();

$categoryImages = [
    'Running Gear' => 'uploads/categories/cat_running_gear.jpg',
    'Football' => 'uploads/categories/cat_football.jpg',
    'Volleyball' => 'uploads/categories/cat_volleyball.jpg',
    'Basketball' => 'uploads/categories/cat_basketball.jpg',
    'Training & Fitness' => 'uploads/categories/cat_training_fitness.jpg',
    'Accessories' => 'uploads/categories/cat_accessories.jpg'
];

$outputDir = __DIR__ . '/uploads/categories';
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

echo "Verifying & Updating Real Category Images...\n";
echo "===============================================================\n";

$stmtUpdate = $pdo->prepare("UPDATE categories SET image = ? WHERE name = ?");
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

$updatedCount = 0;

foreach ($categories as $cat) {
    $name = $cat['name'];
    $imagePath = $categoryImages[$name] ?? null;

    if (!$imagePath) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $name));
        $imagePath = "uploads/categories/cat_{$slug}.jpg";
    }

    $fullPath = __DIR__ . '/' . $imagePath;
    $exists = file_exists($fullPath) ? 'YES' : 'NO';

    $stmtUpdate->execute([$imagePath, $name]);
    $updatedCount++;

    echo "Category #{$cat['id']}: {$name}\n";
    echo "  -> DB Path: {$imagePath}\n";
    echo "  -> Real Image File Exists: {$exists}\n\n";
}

echo "===============================================================\n";
echo "Successfully verified and updated {$updatedCount} categories in the database!\n";
