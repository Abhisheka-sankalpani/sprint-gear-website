<?php
// payhere_process.php - Redirects Order to PayHere Payment Gateway
$pageTitle = "Processing Payment...";
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/payhere.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$orderNumber = isset($_GET['order_number']) ? sanitize($_GET['order_number']) : '';

if (!$orderId && !$orderNumber) {
    header("Location: index.php");
    exit;
}

$pdo = getDBConnection();
if ($orderId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
}
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', "Order not found.");
    header("Location: index.php");
    exit;
}

// Split full name into first and last name for PayHere
$nameParts = explode(' ', trim($order['full_name']), 2);
$firstName = $nameParts[0] ?? 'Customer';
$lastName = $nameParts[1] ?? 'Customer';

// Determine base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir = dirname($_SERVER['PHP_SELF']);
$baseUrl = rtrim($protocol . $host . $dir, '/\\') . '/';

$returnUrl = $baseUrl . 'payhere_return.php?order_id=' . $order['id'];
$cancelUrl = $baseUrl . 'checkout.php?status=cancelled';
$notifyUrl = $baseUrl . 'payhere_notify.php';

$hash = generatePayHereHash($order['order_number'], $order['total_amount'], PAYHERE_CURRENCY);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connecting to PayHere Payment Gateway...</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0f172a;
            --card-bg: #1e293b;
            --primary-red: #ef4444;
            --accent-blue: #3b82f6;
            --text-light: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .loader-card {
            background: var(--card-bg);
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 2.5rem 2rem;
            max-width: 460px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .spinner-container {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
        }

        .spinner {
            width: 100%;
            height: 100%;
            border: 4px solid rgba(239, 68, 68, 0.15);
            border-top: 4px solid var(--primary-red);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .spinner-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.6rem;
            color: var(--primary-red);
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .title {
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .order-badge {
            display: inline-block;
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }

        .payhere-logo {
            height: 32px;
            margin-bottom: 1.25rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }

        .btn-manual {
            display: inline-block;
            background: var(--primary-red);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            width: 100%;
            box-sizing: border-box;
        }

        .btn-manual:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>

<div class="loader-card">
    <div class="spinner-container">
        <div class="spinner"></div>
        <i class="fas fa-lock spinner-icon"></i>
    </div>

    <div class="order-badge"><i class="fas fa-file-invoice"></i> Order #<?php echo htmlspecialchars($order['order_number']); ?></div>

    <h2 class="title">Redirecting to PayHere</h2>
    <p class="subtitle">Please wait while we securely connect you to the PayHere Payment Gateway to complete your total payment of <strong><?php echo formatPrice($order['total_amount']); ?></strong>.</p>

    <!-- PayHere Form -->
    <form id="payhereForm" action="<?php echo PAYHERE_CHECKOUT_URL; ?>" method="POST">
        <input type="hidden" name="merchant_id" value="<?php echo PAYHERE_MERCHANT_ID; ?>">
        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($returnUrl); ?>">
        <input type="hidden" name="cancel_url" value="<?php echo htmlspecialchars($cancelUrl); ?>">
        <input type="hidden" name="notify_url" value="<?php echo htmlspecialchars($notifyUrl); ?>">
        
        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_number']); ?>">
        <input type="hidden" name="items" value="Order #<?php echo htmlspecialchars($order['order_number']); ?> - Sprint Gear">
        <input type="hidden" name="currency" value="<?php echo PAYHERE_CURRENCY; ?>">
        <input type="hidden" name="amount" value="<?php echo number_format((float)$order['total_amount'], 2, '.', ''); ?>">
        
        <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($firstName); ?>">
        <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($lastName); ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($order['email']); ?>">
        <input type="hidden" name="phone" value="<?php echo htmlspecialchars($order['phone']); ?>">
        <input type="hidden" name="address" value="<?php echo htmlspecialchars($order['shipping_address']); ?>">
        <input type="hidden" name="city" value="<?php echo htmlspecialchars($order['city']); ?>">
        <input type="hidden" name="country" value="Sri Lanka">
        <input type="hidden" name="hash" value="<?php echo $hash; ?>">

        <button type="submit" class="btn-manual">Proceed to PayHere <i class="fas fa-arrow-right"></i></button>
    </form>
</div>

<script>
    // Automatically submit form after 600ms for smooth visual feedback
    setTimeout(function() {
        document.getElementById('payhereForm').submit();
    }, 600);
</script>

</body>
</html>
