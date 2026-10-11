<?php
// config/payhere.php - PayHere Payment Gateway Configuration

if (!defined('PAYHERE_MERCHANT_ID')) {
    define('PAYHERE_MERCHANT_ID', '1238531');
}

if (!defined('PAYHERE_MERCHANT_SECRET')) {
    define('PAYHERE_MERCHANT_SECRET', 'MTIwNzc3MTM4MDg5NTg2MjIzNDMzMzk2Mzc4NTAxNDIzMDM5MDYz');
}

if (!defined('PAYHERE_CURRENCY')) {
    define('PAYHERE_CURRENCY', 'LKR');
}

// Mode: 'sandbox' or 'live'
if (!defined('PAYHERE_MODE')) {
    define('PAYHERE_MODE', 'sandbox');
}

// Endpoint URL
if (!defined('PAYHERE_CHECKOUT_URL')) {
    define('PAYHERE_CHECKOUT_URL', (PAYHERE_MODE === 'sandbox')
        ? 'https://sandbox.payhere.lk/pay/checkout'
        : 'https://www.payhere.lk/pay/checkout');
}

/**
 * Generate PayHere Checkout Hash
 * Formula: strtoupper(md5(merchant_id + order_id + amount_formatted + currency + strtoupper(md5(merchant_secret))))
 */
function generatePayHereHash($orderId, $amount, $currency = PAYHERE_CURRENCY) {
    $merchantId = PAYHERE_MERCHANT_ID;
    $merchantSecret = PAYHERE_MERCHANT_SECRET;
    $amountFormatted = number_format((float)$amount, 2, '.', '');
    
    $hashedSecret = strtoupper(md5($merchantSecret));
    $hash = strtoupper(md5($merchantId . $orderId . $amountFormatted . $currency . $hashedSecret));
    
    return $hash;
}

/**
 * Verify PayHere Notification MD5 Signature (md5sig)
 * Formula: strtoupper(md5(merchant_id + order_id + payhere_amount + payhere_currency + status_code + strtoupper(md5(merchant_secret))))
 */
function verifyPayHereMd5Sig($orderId, $payhereAmount, $payhereCurrency, $statusCode) {
    $merchantId = PAYHERE_MERCHANT_ID;
    $merchantSecret = PAYHERE_MERCHANT_SECRET;
    
    $hashedSecret = strtoupper(md5($merchantSecret));
    $localMd5Sig = strtoupper(md5($merchantId . $orderId . $payhereAmount . $payhereCurrency . $statusCode . $hashedSecret));
    
    return $localMd5Sig;
}
