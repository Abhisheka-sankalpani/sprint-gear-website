<?php
// login.php - User Login Portal
$pageTitle = "Login to Sprint Gear";
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    $user = getLoggedInUser();
    header("Location: " . ($user['role'] === 'admin' ? "admin/index.php" : "profile.php"));
    exit;
}

$errorMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($email && !empty($password)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $userRow = $stmt->fetch();

        if ($userRow && password_verify($password, $userRow['password'])) {
            $_SESSION['user_id'] = $userRow['id'];
            $_SESSION['user_name'] = $userRow['name'];
            $_SESSION['user_email'] = $userRow['email'];
            $_SESSION['user_role'] = $userRow['role'];

            // Merge guest cart items into user cart
            $guestCartId = getActiveCartId();

            setFlash('success', "Welcome back, " . htmlspecialchars($userRow['name']) . "!");
            if ($userRow['role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $errorMsg = "Invalid email address or password combination.";
        }
    } else {
        $errorMsg = "Please fill in all required login fields.";
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 4rem 1.25rem;">
    <div class="auth-card">
        <div class="auth-header">
            <h2><i class="fas fa-running text-red-500"></i> LOGIN TO SPRINT GEAR</h2>
            <p>Access your sports gear orders, address book, and wishlist.</p>
        </div>

        <?php if ($errorMsg): ?>
            <div class="alert-error" style="margin-bottom:1rem; padding:0.8rem; background:#FEF2F2; border:1px solid #EF4444; border-radius:6px; color:#991B1B; font-size:0.9rem;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="user@example.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-auth-submit">LOGIN NOW</button>
        </form>

        <div class="auth-footer">
            <p>Don't have a Sprint Gear account? <a href="register.php" class="text-red-500 font-bold">Register Account</a></p>
            <div style="margin-top:1.25rem; padding-top:1rem; border-top:1px solid var(--border-color); font-size:0.8rem; color:var(--text-muted); text-align:left;">
                <strong>Demo Credentials:</strong><br>
                👑 Admin: <code>admin@sprintgear.lk</code> / <code>admin123</code><br>
                👟 Customer: <code>john@example.com</code> / <code>user123</code>
            </div>
        </div>
    </div>
</div>

<style>
.auth-card { max-width: 440px; margin: 0 auto; background: white; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2.5rem; box-shadow: var(--shadow-lg); }
.auth-header { text-align: center; margin-bottom: 2rem; }
.auth-header h2 { font-family: var(--font-heading); font-size: 1.6rem; font-weight: 900; margin-bottom: 0.25rem; }
.auth-header p { color: var(--text-muted); font-size: 0.85rem; }

.btn-auth-submit { width: 100%; background: var(--primary-red); color: white; border: none; padding: 0.85rem; border-radius: var(--radius-sm); font-family: var(--font-heading); font-weight: 800; font-size: 0.95rem; cursor: pointer; margin-top: 1rem; }
.btn-auth-submit:hover { background: var(--primary-red-hover); }

.auth-footer { text-align: center; margin-top: 1.5rem; font-size: 0.9rem; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
