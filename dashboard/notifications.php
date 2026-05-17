<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$u = require_login();
if ($u['role'] === 'admin') {
    header('Location: ' . url('admin/index.php'));
    exit;
}

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'read_all') {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([(int) $u['id']]);
        flash_set('success', 'All notifications marked as read.');
    } elseif ($action === 'read_one' && !empty($_POST['id'])) {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([(int) $_POST['id'], (int) $u['id']]);
    } elseif ($action === 'delete_one' && !empty($_POST['id'])) {
        $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?')->execute([(int) $_POST['id'], (int) $u['id']]);
        flash_set('success', 'Notification removed.');
    }
    header('Location: ' . url('dashboard/notifications.php'));
    exit;
}

$stmt = $pdo->prepare('SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100');
$stmt->execute([(int) $u['id']]);
$rows = $stmt->fetchAll();

$pageTitle = 'Notifications';
$pageSubtitle = 'All updates and announcements for your cases.';
$activeNav = 'notifications';
$pageActions = '';
if (count($rows) > 0) {
    $pageActions = '<form method="post" style="display:inline;"><input type="hidden" name="action" value="read_all"><button class="s-btn s-btn--ghost" type="submit"><i class="fas fa-check-double"></i> Mark all read</button></form>';
}
require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>
<div class="s-card" style="padding: 0;">
    <?php if (count($rows) === 0): ?>
        <div class="s-empty">
            <i class="fas fa-bell-slash" style="font-size:1.6rem; color: var(--muted);"></i>
            <p>You have no notifications yet.</p>
        </div>
    <?php else: foreach ($rows as $n): ?>
        <div class="s-notif <?php echo $n['is_read'] ? '' : 'unread'; ?>" style="padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; border-bottom: 1px solid var(--line); border-left: 3px solid <?php echo $n['is_read'] ? 'transparent' : 'var(--brand)'; ?>;">
            <div>
                <p style="margin: 0;"><?php echo htmlspecialchars($n['message']); ?></p>
                <small style="color: var(--muted);"><?php echo htmlspecialchars(time_ago($n['created_at'])); ?></small>
            </div>
            <div style="display: flex; gap: .35rem;">
                <?php if (!$n['is_read']): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="read_one">
                        <input type="hidden" name="id" value="<?php echo (int) $n['id']; ?>">
                        <button class="s-btn s-btn--soft s-btn--sm" type="submit" title="Mark as read"><i class="fas fa-check"></i></button>
                    </form>
                <?php endif; ?>
                <form method="post" data-confirm="Delete this notification?" style="display:inline;">
                    <input type="hidden" name="action" value="delete_one">
                    <input type="hidden" name="id" value="<?php echo (int) $n['id']; ?>">
                    <button class="s-btn s-btn--danger s-btn--sm" type="submit" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
