<?php
// admin/contact-messages.php - Contact Inquiries & Messages Management Panel
$adminTitle = "Contact Messages & Inquiries";
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Process Status Update or Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $msgId = (int)$_POST['message_id'];
    
    if ($_POST['action'] === 'update_status') {
        $newStatus = sanitize($_POST['status']);
        $stmtUpd = $pdo->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
        $stmtUpd->execute([$newStatus, $msgId]);
        setFlash('success', "Message status updated to $newStatus.");
    } elseif ($_POST['action'] === 'delete_message') {
        $stmtDel = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmtDel->execute([$msgId]);
        setFlash('info', 'Message deleted.');
    }
    header("Location: contact-messages.php");
    exit;
}

// Fetch all messages
$stmtMsgs = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $stmtMsgs->fetchAll();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="fas fa-envelope text-red-500"></i> Customer Inquiries (<?php echo count($messages); ?>)</h2>
    </div>

    <?php if (!empty($messages)): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $m): 
                    $badgeStyle = 'background:#334155; color:white;';
                    if ($m['status'] === 'New') $badgeStyle = 'background:#EF4444; color:white;';
                    if ($m['status'] === 'Read') $badgeStyle = 'background:#3B82F6; color:white;';
                    if ($m['status'] === 'Replied') $badgeStyle = 'background:#10B981; color:white;';
                ?>
                    <tr>
                        <td style="white-space:nowrap; font-size:0.8rem; color:#94A3B8;">
                            <?php echo date('M d, Y H:i', strtotime($m['created_at'])); ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($m['name']); ?></strong></td>
                        <td>
                            <a href="mailto:<?php echo htmlspecialchars($m['email']); ?>" style="color:#3B82F6; text-decoration:underline;">
                                <?php echo htmlspecialchars($m['email']); ?>
                            </a>
                        </td>
                        <td><span style="font-weight:700; color:#F8FAFC;"><?php echo htmlspecialchars($m['subject']); ?></span></td>
                        <td style="max-width:300px; font-size:0.85rem; color:#CBD5E1;">
                            <?php echo nl2br(htmlspecialchars($m['message'])); ?>
                        </td>
                        <td>
                            <form action="contact-messages.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="message_id" value="<?php echo $m['id']; ?>">
                                <select name="status" class="admin-control" style="padding:0.2rem 0.4rem; font-size:0.8rem; width:100px;" onchange="this.form.submit()">
                                    <option value="New" <?php echo $m['status'] === 'New' ? 'selected' : ''; ?>>New</option>
                                    <option value="Read" <?php echo $m['status'] === 'Read' ? 'selected' : ''; ?>>Read</option>
                                    <option value="Replied" <?php echo $m['status'] === 'Replied' ? 'selected' : ''; ?>>Replied</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form action="contact-messages.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this inquiry?');">
                                <input type="hidden" name="action" value="delete_message">
                                <input type="hidden" name="message_id" value="<?php echo $m['id']; ?>">
                                <button type="submit" class="btn-admin-action btn-admin-sm" style="background:#991B1B;"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align:center; padding:3rem; color:#94A3B8;">
            <i class="fas fa-inbox" style="font-size:3rem; margin-bottom:1rem; opacity:0.5;"></i>
            <p>No customer contact messages received yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
