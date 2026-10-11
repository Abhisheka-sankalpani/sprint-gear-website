<?php
// payhere_notify.php - PayHere Server-to-Server Webhook Notification Endpoint
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/payhere.php';

// Disable output buffering & display errors for clean HTTP 200 responses
ini_set('display_errors', 0);
header("Content-Type: text/plain");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method Not Allowed");
}

$merchantId     = $_POST['merchant_id'] ?? '';
$orderId        = $_POST['order_id'] ?? '';
$payhereAmount  = $_POST['payhere_amount'] ?? '';
$payhereCurrency= $_POST['payhere_currency'] ?? '';
$statusCode     = $_POST['status_code'] ?? '';
$md5sig         = $_POST['md5sig'] ?? '';

if (empty($merchantId) || empty($orderId) || empty($md5sig)) {
    http_response_code(400);
    exit("Bad Request: Missing parameters");
}

// Generate local signature verification
$localMd5Sig = verifyPayHereMd5Sig($orderId, $payhereAmount, $payhereCurrency, $statusCode);

if (strtoupper($localMd5Sig) === strtoupper($md5sig)) {
    $pdo = getDBConnection();
    
    // Status Code 2 = Successfully Charged
    if ((int)$statusCode === 2) {
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'Paid', order_status = 'Confirmed' WHERE order_number = ? OR id = ?");
        $stmt->execute([$orderId, (int)$orderId]);
        
        http_response_code(200);
        exit("Payment Verified Successfully");
    } else {
        // Payment failed or cancelled
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'Failed', order_status = 'Cancelled' WHERE order_number = ? OR id = ?");
        $stmt->execute([$orderId, (int)$orderId]);
        
        http_response_code(200);
        exit("Payment Status Updated: " . $statusCode);
    }
} else {
    http_response_code(400);
    exit("Invalid Signature");
}
