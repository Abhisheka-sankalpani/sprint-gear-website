<?php
// payhere_return.php - Customer Return Landing Page After PayHere Payment
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/payhere.php';

$orderParam = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';

if (!empty($orderParam)) {
    $pdo = getDBConnection();

    // ID එකෙන් හෝ Order Number එකෙන් order එක සෙවීම
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? OR order_number = ?");
    $stmt->execute([$orderParam, $orderParam]);
    $order = $stmt->fetch();

    if ($order) {
        // Payment status එක 'Paid' සහ Order status එක 'Confirmed' කිරීම
        $stmtUpdate = $pdo->prepare("UPDATE orders SET payment_status = 'Paid', order_status = 'Confirmed' WHERE id = ?");
        $stmtUpdate->execute([$order['id']]);

        setFlash('success', "Payment successful! Order #" . htmlspecialchars($order['order_number']) . " has been placed. Thank you for your purchase!");
        
        if ($user) {
            header("Location: orders.php?id=" . $order['id']);
        } else {
            header("Location: index.php");
        }
        exit;
    }
}

setFlash('success', "Payment processed successfully! Thank you for shopping with Sprint Gear.");
header("Location: " . ($user ? "orders.php" : "index.php"));
exit;
