<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
$u = require_role('admin');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'broadcast') {
        $msg = sanitize_string($_POST['message'] ?? '');
        if ($msg !== '') {
            $stmt = $pdo->query("SELECT id FROM users WHERE role = 'complainant'");
            $count = 0;
            foreach ($stmt->fetchAll() as $row) {
                add_notification($pdo, (int) $row['id'], $msg);
                $count++;
            }
            flash_set('success', 'Broadcast sent to ' . $count . ' resident(s).');
        }
    }
    if ($action === 'read_all') {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([(int) $u['id']]);
        flash_set('success', 'Marked all as read.');
    }
    if ($action === 'read_one' && !empty($_POST['id'])) {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?')->execute([(int) $_POST['id']]);
    }
    if ($action === 'delete_one' && !empty($_POST['id'])) {
        $pdo->prepare('DELETE FROM notifications WHERE id = ?')->execute([(int) $_POST['id']]);
        flash_set('success', 'Notification removed.');
    }
    header('Location: ' . url('admin/notifications.php'));
    exit;
}

$stmt = $pdo->query('SELECT n.id, n.message, n.is_read, n.created_at, u.full_name AS user_name, u.email AS user_email
    FROM notifications n LEFT JOIN users u ON u.id = n.user_id ORDER BY n.created_at DESC LIMIT 200');
$rows = $stmt->fetchAll();

$pageTitle = 'Notifications';
$pageSubtitle = 'Send broadcasts and review system notifications.';
$activeNav = 'notifications';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-bullhorn mr-1"></i> Broadcast to residents</h3></div>
            <form method="post" class="card-body">
                <input type="hidden" name="action" value="broadcast">
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" class="form-control" rows="4" required placeholder="Announcement to all residents..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block" data-confirm-link="Send this notification to all residents?"><i class="fas fa-paper-plane mr-1"></i> Broadcast</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Recent notifications</h3>
                <form method="post"><input type="hidden" name="action" value="read_all"><button class="btn btn-sm btn-outline-primary"><i class="fas fa-check-double mr-1"></i> Mark mine read</button></form>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>To</th><th>Message</th><th>Read</th><th>When</th><th></th></tr></thead>
                    <tbody>
                    <?php if (count($rows) === 0): ?>
                        <tr><td colspan="5" class="text-center text-muted p-4">No notifications.</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($r['user_name'] ?? 'Unknown'); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($r['user_email'] ?? ''); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($r['message']); ?></td>
                            <td><?php echo $r['is_read'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-warning">No</span>'; ?></td>
                            <td><?php echo htmlspecialchars(time_ago($r['created_at'])); ?></td>
                            <td class="text-right">
                                <form method="post" data-confirm="Delete this notification?" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_one">
                                    <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
<?php require_once dirname(__DIR__) . '/includes/admin_layout_end.php'; ?>
