<?php
// profile.php - User Profile & Address Management Page
$pageTitle = "My Profile & Addresses";
require_once __DIR__ . '/includes/functions.php';

requireLogin();
$user = getLoggedInUser();
$pdo = getDBConnection();

// Fetch fresh user profile
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$user['id']]);
$userProfile = $stmtUser->fetch();

// Handle Profile Details Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');

    if (!empty($name)) {
        $stmtUpd = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
        $stmtUpd->execute([$name, $phone, $user['id']]);
        $_SESSION['user_name'] = $name;
        setFlash('success', 'Profile updated successfully!');
    }
    header("Location: profile.php");
    exit;
}

// Handle Add/Delete Address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_address') {
    $line1 = sanitize($_POST['address_line1'] ?? '');
    $line2 = sanitize($_POST['address_line2'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $district = sanitize($_POST['district'] ?? '');
    $postal = sanitize($_POST['postal_code'] ?? '');

    if (!empty($line1) && !empty($city) && !empty($district) && !empty($postal)) {
        $stmtInsAddr = $pdo->prepare("INSERT INTO addresses (user_id, address_line1, address_line2, city, district, postal_code, is_default) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmtInsAddr->execute([$user['id'], $line1, $line2, $city, $district, $postal]);
        setFlash('success', 'New delivery address added!');
    }
    header("Location: profile.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_address') {
    $addrId = (int)($_POST['address_id'] ?? 0);
    $stmtDelAddr = $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
    $stmtDelAddr->execute([$addrId, $user['id']]);
    setFlash('info', 'Address deleted.');
    header("Location: profile.php");
    exit;
}

// Fetch user addresses
$stmtAddrs = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$stmtAddrs->execute([$user['id']]);
$userAddresses = $stmtAddrs->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1 class="page-title">MY ACCOUNT PROFILE</h1>
        <ul class="breadcrumb">
            <li><a href="index.php">Home</a></li>
            <li class="active">My Profile</li>
        </ul>
    </div>
</div>

<div class="container profile-page-wrapper">
    <div class="profile-layout">
        <!-- SIDEBAR ACCOUNT NAVIGATION -->
        <aside class="profile-sidebar">
            <div class="user-avatar-card">
                <div class="user-avatar-circle"><i class="fas fa-user"></i></div>
                <h3><?php echo htmlspecialchars($userProfile['name']); ?></h3>
                <p><?php echo htmlspecialchars($userProfile['email']); ?></p>
                <span class="role-badge"><?php echo strtoupper($userProfile['role']); ?></span>
            </div>
            <ul class="account-nav">
                <li><a href="profile.php" class="active"><i class="fas fa-user-cog"></i> Profile & Address</a></li>
                <li><a href="orders.php"><i class="fas fa-box"></i> My Orders</a></li>
                <li><a href="wishlist.php"><i class="fas fa-heart"></i> My Wishlist</a></li>
                <li><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="profile-content">
            <!-- 1. PERSONAL INFORMATION CARD -->
            <div class="profile-card">
                <h3 class="card-heading"><i class="fas fa-id-card text-red"></i> Personal Information</h3>
                <form action="profile.php" method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($userProfile['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address (Read-only)</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($userProfile['email']); ?>" disabled>
                        </div>
                        <div class="form-group span-2">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($userProfile['phone'] ?? ''); ?>" placeholder="+94 XX XXXXXXX">
                        </div>
                    </div>
                    <button type="submit" class="btn-save-profile">Save Changes</button>
                </form>
            </div>

            <!-- 2. ADDRESS MANAGEMENT CARD -->
            <div class="profile-card">
                <h3 class="card-heading"><i class="fas fa-map-marked-alt text-red"></i> Delivery Address Book</h3>
                
                <!-- Saved Addresses Grid -->
                <?php if (!empty($userAddresses)): ?>
                    <div class="address-grid">
                        <?php foreach ($userAddresses as $addr): ?>
                            <div class="address-card-item">
                                <?php if ($addr['is_default']): ?>
                                    <span class="default-pill">DEFAULT</span>
                                <?php endif; ?>
                                <p class="addr-line1"><strong><?php echo htmlspecialchars($addr['address_line1']); ?></strong></p>
                                <?php if ($addr['address_line2']): ?>
                                    <p class="addr-line2"><?php echo htmlspecialchars($addr['address_line2']); ?></p>
                                <?php endif; ?>
                                <p class="addr-meta"><?php echo htmlspecialchars($addr['city']); ?>, <?php echo htmlspecialchars($addr['district']); ?> <?php echo htmlspecialchars($addr['postal_code']); ?></p>
                                
                                <form action="profile.php" method="POST" class="delete-addr-form">
                                    <input type="hidden" name="action" value="delete_address">
                                    <input type="hidden" name="address_id" value="<?php echo $addr['id']; ?>">
                                    <button type="submit" class="btn-delete-addr" onclick="return confirm('Delete this address?');">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-address-text">No delivery addresses added yet.</p>
                <?php endif; ?>

                <!-- Add New Address Form -->
                <h4 class="sub-form-heading">Add New Delivery Address</h4>
                <form action="profile.php" method="POST">
                    <input type="hidden" name="action" value="add_address">
                    <div class="form-grid-2">
                        <div class="form-group span-2">
                            <label>Address Line 1 *</label>
                            <input type="text" name="address_line1" class="form-control" placeholder="House / Street No." required>
                        </div>
                        <div class="form-group span-2">
                            <label>Address Line 2</label>
                            <input type="text" name="address_line2" class="form-control" placeholder="Apartment / Area">
                        </div>
                        <div class="form-group">
                            <label>City *</label>
                            <input type="text" name="city" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>District *</label>
                            <input type="text" name="district" class="form-control" required>
                        </div>
                        <div class="form-group span-2">
                            <label>Postal Code *</label>
                            <input type="text" name="postal_code" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-add-address">Add Address</button>
                </form>
            </div>
        </main>
    </div>
</div>

<style>
/* Page Layout */
.profile-page-wrapper {
    padding-bottom: 5rem;
}

.profile-layout {
    display: grid;
    grid-template-columns: 290px 1fr;
    gap: 2rem;
    align-items: start;
}

/* Sidebar Styling */
.profile-sidebar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.75rem 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.user-avatar-card {
    text-align: center;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 1.5rem;
    margin-bottom: 1.25rem;
}

.user-avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #0f172a;
    color: #ef4444;
    font-size: 2.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    border: 3px solid #f1f5f9;
}

.user-avatar-card h3 {
    font-size: 1.15rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.35rem;
}

.user-avatar-card p {
    font-size: 0.85rem;
    color: #64748b;
    word-break: break-all;
    margin-bottom: 0.5rem;
}

.role-badge {
    display: inline-block;
    background: #ef4444;
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.2rem 0.65rem;
    border-radius: 20px;
    letter-spacing: 0.5px;
}

/* Navigation Menu */
.account-nav {
    list-style: none;
    padding: 0;
    margin: 0;
}

.account-nav li {
    margin-bottom: 0.4rem;
}

.account-nav a {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.92rem;
    color: #475569;
    text-decoration: none;
    transition: all 0.2s ease;
}

.account-nav a.active,
.account-nav a:hover {
    background: #fff1f2;
    color: #ef4444;
}

.account-nav a.logout-link {
    color: #ef4444;
}

.account-nav a.logout-link:hover {
    background: #fef2f2;
}

/* Card Content Area */
.profile-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.card-heading {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #f8fafc;
}

.sub-form-heading {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1e293b;
    margin: 2rem 0 1.25rem;
    padding-top: 1.5rem;
    border-top: 1px solid #f1f5f9;
}

.text-red {
    color: #ef4444;
}

/* Grid & Form Controls */
.form-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.25rem 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.form-group.span-2 {
    grid-column: span 2;
}

.form-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.92rem;
    color: #1e293b;
    background: #ffffff;
    box-sizing: border-box;
    outline: none;
    transition: all 0.2s ease;
}

.form-control:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-control:disabled {
    background: #f8fafc;
    color: #64748b;
    border-color: #e2e8f0;
    cursor: not-allowed;
}

/* Action Buttons */
.btn-save-profile {
    background: #ef4444;
    color: #ffffff;
    border: none;
    padding: 0.8rem 2rem;
    font-weight: 700;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 1.5rem;
    transition: background 0.2s ease;
}

.btn-save-profile:hover {
    background: #dc2626;
}

.btn-add-address {
    background: #0f172a;
    color: #ffffff;
    border: none;
    padding: 0.8rem 2rem;
    font-weight: 700;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 1.5rem;
    transition: background 0.2s ease;
}

.btn-add-address:hover {
    background: #1e293b;
}

/* Address Cards Display */
.address-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}

.address-card-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1.25rem;
    position: relative;
    font-size: 0.88rem;
    line-height: 1.5;
}

.default-pill {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: #0f172a;
    color: #ffffff;
    font-size: 0.65rem;
    font-weight: 800;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
}

.btn-delete-addr {
    background: none;
    border: none;
    color: #ef4444;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    padding: 0;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-top: 0.5rem;
}

.btn-delete-addr:hover {
    text-decoration: underline;
}

.no-address-text {
    color: #64748b;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

/* Responsive adjustments */
@media (max-width: 900px) {
    .profile-layout {
        grid-template-columns: 1fr;
    }
    .address-grid {
        grid-template-columns: 1fr;
    }
    .form-grid-2 {
        grid-template-columns: 1fr;
    }
    .form-group.span-2 {
        grid-column: span 1;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>