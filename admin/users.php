<?php
// admin/users.php - User Account & Privilege Management
$adminTitle = "Users Management";
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Toggle User Role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_role') {
    $userId = (int)$_POST['user_id'];
    $stmtRole = $pdo->prepare("UPDATE users SET role = IF(role='admin', 'customer', 'admin') WHERE id = ?");
    $stmtRole->execute([$userId]);
    setFlash('info', 'User role updated.');
    header("Location: users.php");
    exit;
}

$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fas fa-users text-red-500"></i> User Accounts (<?php echo count($users); ?>)</h2>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email Address</th>
                <th>Phone Number</th>
                <th>Role</th>
                <th>Registered Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?php echo $u['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo htmlspecialchars($u['phone'] ?? 'N/A'); ?></td>
                    <td>
                        <span style="padding:0.2rem 0.6rem; border-radius:4px; font-weight:800; font-size:0.75rem; background:<?php echo $u['role'] === 'admin' ? '#DC2626' : '#334155'; ?>; color:white;">
                            <?php echo strtoupper($u['role']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                    <td>
                        <?php if ($u['id'] !== $adminUser['id']): ?>
                            <form action="users.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_role">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn-admin-action btn-admin-secondary btn-admin-sm">
                                    Toggle <?php echo $u['role'] === 'admin' ? 'Customer' : 'Admin'; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <small style="color:#94A3B8;">Current Session</small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
