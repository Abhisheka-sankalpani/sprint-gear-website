<?php
// includes/functions.php - Global Helper Functions & Cart/Wishlist Business Logic

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

// Format currency in Sri Lankan Rupees (Rs. XX,XXX)
function formatPrice($amount) {
    if ($amount === null || $amount === '') return '';
    return 'Rs. ' . number_format((float)$amount, 2);
}

// Sanitize string input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

// Flash Message Helpers
function setFlash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

function getFlash($type) {
    if (isset($_SESSION['flash_' . $type])) {
        $msg = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $msg;
    }
    return null;
}

// Render star rating HTML
function renderStarRating($rating, $reviewsCount = null) {
    $rating = (float)$rating;
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);

    $html = '<div class="star-rating" title="Rating: ' . number_format($rating, 1) . ' / 5">';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fas fa-star text-yellow-400"></i>';
    }
    if ($halfStar) {
        $html .= '<i class="fas fa-star-half-alt text-yellow-400"></i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="far fa-star text-gray-300"></i>';
    }
    if ($reviewsCount !== null) {
        $html .= ' <span class="rating-count">(' . (int)$reviewsCount . ')</span>';
    }
    $html .= '</div>';
    return $html;
}

// Fetch all main categories with their subcategories for global navigation
function getNavigationCategories() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT c.*, s.id as sub_id, s.name as sub_name, s.slug as sub_slug 
                        FROM categories c 
                        LEFT JOIN subcategories s ON c.id = s.category_id 
                        ORDER BY c.id ASC, s.name ASC");
    $rows = $stmt->fetchAll();

    $categories = [];
    foreach ($rows as $row) {
        $catId = $row['id'];
        if (!isset($categories[$catId])) {
            $categories[$catId] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'image' => $row['image'],
                'icon' => $row['icon'],
                'subcategories' => []
            ];
        }
        if (!empty($row['sub_id'])) {
            $categories[$catId]['subcategories'][] = [
                'id' => $row['sub_id'],
                'name' => $row['sub_name'],
                'slug' => $row['sub_slug']
            ];
        }
    }
    return $categories;
}

// Get or Create Active Cart ID
function getActiveCartId() {
    $pdo = getDBConnection();
    $user = getLoggedInUser();
    $sessionId = $_SESSION['guest_session_id'] ?? null;

    if ($user) {
        // Logged-in user cart
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $cart = $stmt->fetch();
        if ($cart) {
            return $cart['id'];
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO carts (user_id) VALUES (?)");
            $stmtInsert->execute([$user['id']]);
            return $pdo->lastInsertId();
        }
    } else {
        // Guest cart by session_id
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE session_id = ? AND user_id IS NULL LIMIT 1");
        $stmt->execute([$sessionId]);
        $cart = $stmt->fetch();
        if ($cart) {
            return $cart['id'];
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO carts (session_id) VALUES (?)");
            $stmtInsert->execute([$sessionId]);
            return $pdo->lastInsertId();
        }
    }
}

// Get total count of items in active cart
function getCartCount() {
    $pdo = getDBConnection();
    $cartId = getActiveCartId();
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cartId]);
    $res = $stmt->fetch();
    return (int)($res['total'] ?? 0);
}

// Get detailed list of cart items
function getCartItems() {
    $pdo = getDBConnection();
    $cartId = getActiveCartId();
    $stmt = $pdo->prepare("
        SELECT ci.id as item_id, ci.quantity, 
               p.id as product_id, p.name as product_name, p.brand, p.price, p.discount_price, p.main_image,
               pv.id as variant_id, pv.stock, pv.variant_price, pv.variant_image,
               c.name as color_name, c.hex_code,
               s.name as size_name
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        JOIN product_variants pv ON ci.variant_id = pv.id
        JOIN colors c ON pv.color_id = c.id
        JOIN sizes s ON pv.size_id = s.id
        WHERE ci.cart_id = ?
        ORDER BY ci.created_at DESC
    ");
    $stmt->execute([$cartId]);
    return $stmt->fetchAll();
}

// Add Item to Cart
function addToCart($productId, $variantId, $quantity = 1) {
    $pdo = getDBConnection();
    $cartId = getActiveCartId();

    // Check variant stock
    $stmtStock = $pdo->prepare("SELECT stock FROM product_variants WHERE id = ? AND product_id = ?");
    $stmtStock->execute([$variantId, $productId]);
    $variant = $stmtStock->fetch();

    if (!$variant) {
        return ['success' => false, 'message' => 'Product variant not found.'];
    }

    $availableStock = (int)$variant['stock'];
    if ($availableStock <= 0) {
        return ['success' => false, 'message' => 'Selected variant is currently out of stock.'];
    }

    // Check existing cart item
    $stmtExist = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND variant_id = ?");
    $stmtExist->execute([$cartId, $productId, $variantId]);
    $existingItem = $stmtExist->fetch();

    if ($existingItem) {
        $newQty = $existingItem['quantity'] + $quantity;
        if ($newQty > $availableStock) {
            return ['success' => false, 'message' => "Cannot add. Maximum available stock is $availableStock."];
        }
        $stmtUpdate = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmtUpdate->execute([$newQty, $existingItem['id']]);
    } else {
        if ($quantity > $availableStock) {
            return ['success' => false, 'message' => "Cannot add. Maximum available stock is $availableStock."];
        }
        $stmtInsert = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)");
        $stmtInsert->execute([$cartId, $productId, $variantId, $quantity]);
    }

    return ['success' => true, 'message' => 'Product added to shopping cart!', 'cart_count' => getCartCount()];
}

// Update Cart Quantity
function updateCartQuantity($itemId, $quantity) {
    $pdo = getDBConnection();
    $cartId = getActiveCartId();

    $stmtItem = $pdo->prepare("SELECT ci.id, ci.variant_id, pv.stock FROM cart_items ci JOIN product_variants pv ON ci.variant_id = pv.id WHERE ci.id = ? AND ci.cart_id = ?");
    $stmtItem->execute([$itemId, $cartId]);
    $item = $stmtItem->fetch();

    if (!$item) {
        return ['success' => false, 'message' => 'Cart item not found.'];
    }

    if ($quantity <= 0) {
        $stmtDel = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
        $stmtDel->execute([$itemId]);
        return ['success' => true, 'message' => 'Item removed from cart.', 'cart_count' => getCartCount()];
    }

    if ($quantity > $item['stock']) {
        return ['success' => false, 'message' => "Requested quantity exceeds available stock (" . $item['stock'] . ")."];
    }

    $stmtUpd = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $stmtUpd->execute([$quantity, $itemId]);
    return ['success' => true, 'message' => 'Cart updated.', 'cart_count' => getCartCount()];
}

// Remove from Cart
function removeFromCart($itemId) {
    $pdo = getDBConnection();
    $cartId = getActiveCartId();
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
    $stmt->execute([$itemId, $cartId]);
    return ['success' => true, 'message' => 'Item removed.', 'cart_count' => getCartCount()];
}

// Wishlist Helpers
function getWishlistCount() {
    $user = getLoggedInUser();
    if (!$user) return 0;

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT COUNT(wi.id) as total 
        FROM wishlist_items wi 
        JOIN wishlists w ON wi.wishlist_id = w.id 
        WHERE w.user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $res = $stmt->fetch();
    return (int)($res['total'] ?? 0);
}

function isInWishlist($productId) {
    $user = getLoggedInUser();
    if (!$user) return false;

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT wi.id 
        FROM wishlist_items wi 
        JOIN wishlists w ON wi.wishlist_id = w.id 
        WHERE w.user_id = ? AND wi.product_id = ?
    ");
    $stmt->execute([$user['id'], $productId]);
    return (bool)$stmt->fetch();
}

function toggleWishlist($productId) {
    $user = getLoggedInUser();
    if (!$user) {
        return ['success' => false, 'require_login' => true, 'message' => 'Please log in to add items to your wishlist.'];
    }

    $pdo = getDBConnection();
    // Get or create wishlist for user
    $stmtW = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ?");
    $stmtW->execute([$user['id']]);
    $w = $stmtW->fetch();
    if (!$w) {
        $stmtCreate = $pdo->prepare("INSERT INTO wishlists (user_id) VALUES (?)");
        $stmtCreate->execute([$user['id']]);
        $wishlistId = $pdo->lastInsertId();
    } else {
        $wishlistId = $w['id'];
    }

    // Check if in wishlist
    $stmtCheck = $pdo->prepare("SELECT id FROM wishlist_items WHERE wishlist_id = ? AND product_id = ?");
    $stmtCheck->execute([$wishlistId, $productId]);
    $item = $stmtCheck->fetch();

    if ($item) {
        $stmtDel = $pdo->prepare("DELETE FROM wishlist_items WHERE id = ?");
        $stmtDel->execute([$item['id']]);
        return ['success' => true, 'action' => 'removed', 'message' => 'Product removed from wishlist.', 'count' => getWishlistCount()];
    } else {
        $stmtIns = $pdo->prepare("INSERT INTO wishlist_items (wishlist_id, product_id) VALUES (?, ?)");
        $stmtIns->execute([$wishlistId, $productId]);
        return ['success' => true, 'action' => 'added', 'message' => 'Product added to wishlist!', 'count' => getWishlistCount()];
    }
}
