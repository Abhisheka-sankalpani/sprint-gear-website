<?php
// register.php - User Registration Page
$pageTitle = "Register Account";
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header("Location: profile.php");
    exit;
}

$errorMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name) || !$email || empty($password)) {
        $errorMsg = "Please complete all required fields with valid information.";
    } elseif ($password !== $confirmPassword) {
        $errorMsg = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $errorMsg = "Password must be at least 6 characters long.";
    } else {
        $pdo = getDBConnection();
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            $errorMsg = "An account with this email address already exists.";
        } else {
            $hashedPass = password_hash($password, PASSWORD_BCRYPT);
            $stmtIns = $pdo->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmtIns->execute([$name, $email, $hashedPass, $phone]);

            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'customer';

            setFlash('success', "Welcome to Sprint Gear, $name! Your account has been created.");
            header("Location: index.php");
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- CSS Styles directly added to the file -->
<style>
    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #f9fafb;
    }
    .auth-card {
        background: #ffffff;
        max-width: 500px;
        width: 100%;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        font-family: Arial, sans-serif;
    }
    .auth-header {
        text-align: center;
        margin-bottom: 25px;
    }
    .auth-header h2 {
        color: #0b1c3a;
        font-size: 24px;
        margin-bottom: 10px;
        font-weight: 800;
    }
    .auth-header p {
        color: #666;
        font-size: 14px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #333;
        font-size: 14px;
    }
    .demo-credentials {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    font-size: 13px;
    color: #666;
}
.demo-credentials p {
    margin: 5px 0;
}
    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
        font-size: 14px;
    }
    .form-control:focus {
        border-color: #f34848;
        outline: none;
        box-shadow: 0 0 5px rgba(243, 72, 72, 0.3);
    }
    .btn-auth-submit {
        background-color: #f34848;
        color: white;
        padding: 12px;
        border: none;
        border-radius: 4px;
        width: 100%;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        margin-top: 15px;
        transition: background-color 0.3s;
    }
    .btn-auth-submit:hover {
        background-color: #d13a3a;
    }
    .auth-footer {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
    }
    .auth-footer a {
        text-decoration: none;
    }
    .auth-footer a:hover {
        text-decoration: underline;
    }
    .text-red-500 {
        color: #f34848;
    }
    .font-bold {
        font-weight: bold;
    }
</style>

<div class="container" style="padding: 4rem 1.25rem;">
    <div class="auth-card">
        <div class="auth-header">
            <h2><i class="fas fa-user-plus text-red-500"></i> CREATE AN ACCOUNT</h2>
            <p>Join the Sprint Gear community for seamless athletic shopping.</p>
        </div>

        <?php if ($errorMsg): ?>
            <div class="alert-error" style="margin-bottom:1rem; padding:0.8rem; background:#FEF2F2; border:1px solid #EF4444; border-radius:6px; color:#991B1B; font-size:0.9rem;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" class="form-control" placeholder="John Doe" required>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" class="form-control" placeholder="+94 77 123 4567">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required>
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
            </div>

            <button type="submit" class="btn-auth-submit">CREATE ACCOUNT</button>
        </form>

        <div class="auth-footer">
            <p>Already registered? <a href="login.php" class="text-red-500 font-bold">Login Here</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>