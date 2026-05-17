<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$u = require_login();
if ($u['role'] === 'admin') {
    header('Location: ' . url('admin/index.php'));
    exit;
}

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $code = normalize_complaint_id($_POST['code'] ?? '');
    if ($code !== '') {
        $stmt = $pdo->prepare('SELECT id, status FROM complaints WHERE code = ? AND complainant_id = ? LIMIT 1');
        $stmt->execute([$code, (int) $u['id']]);
        $row = $stmt->fetch();
        if ($row && $row['status'] === 'pending') {
            $pdo->prepare('UPDATE complaints SET status = ? WHERE id = ?')->execute(['rejected', (int) $row['id']]);
            $pdo->prepare('INSERT INTO complaint_timeline (complaint_id, status_label, details) VALUES (?,?,?)')
                ->execute([(int) $row['id'], 'Cancelled by complainant', 'Resident requested cancellation while case was still pending.']);
            flash_set('success', 'Complaint #' . $code . ' has been cancelled.');
        } else {
            flash_set('warning', 'Only pending complaints can be cancelled.');
        }
    }
    header('Location: ' . url('dashboard/my-complaints.php'));
    exit;
}

$status = sanitize_string($_GET['status'] ?? '');
$type = sanitize_string($_GET['type'] ?? '');
$q = sanitize_string($_GET['q'] ?? '');

$sql = 'SELECT * FROM complaints WHERE complainant_id = ?';
$params = [(int) $u['id']];
if (in_array($status, ['pending', 'in-progress', 'resolved', 'rejected'], true)) {
    $sql .= ' AND status = ?';
    $params[] = $status;
}
if (in_array($type, ['Noise', 'Sanitation', 'Security', 'Traffic', 'Other'], true)) {
    $sql .= ' AND type = ?';
    $params[] = $type;
}
if ($q !== '') {
    $sql .= ' AND (code LIKE ? OR location LIKE ? OR description LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'My complaints';
$pageSubtitle = 'Browse, track, and manage every complaint you have filed.';
$activeNav = 'list';
$pageActions = '<a class="s-btn" href="' . htmlspecialchars(url('dashboard/file-complaint.php')) . '"><i class="fas fa-plus"></i> New complaint</a>';
require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>

<div class="s-card">
    <form method="get" class="s-toolbar" style="margin-bottom: 0;">
        <div class="s-toolbar__filters">
            <input class="s-input" type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search code, location, description...">
            <select class="s-select" name="status">
                <option value="">All statuses</option>
                <?php foreach (['pending', 'in-progress', 'resolved', 'rejected'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo htmlspecialchars(status_label($s)); ?></option>
                <?php endforeach; ?>
            </select>
            <select class="s-select" name="type">
                <option value="">All types</option>
                <?php foreach (['Noise', 'Sanitation', 'Security', 'Traffic', 'Other'] as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo $type === $t ? 'selected' : ''; ?>><?php echo htmlspecialchars($t); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="s-page-actions">
            <button class="s-btn s-btn--soft s-btn--sm" type="submit"><i class="fas fa-filter"></i> Filter</button>
            <a class="s-btn s-btn--ghost s-btn--sm" href="<?php echo htmlspecialchars(url('dashboard/my-complaints.php')); ?>">Reset</a>
        </div>
    </form>
</div>

<div class="s-card" style="margin-top: 1rem; padding: 0;">
    <div class="s-table-wrap">
        <table class="s-table">
            <thead>
            <tr>
                <th>Case ID</th>
                <th>Type</th>
                <th>Location</th>
                <th>Filed</th>
                <th>Status</th>
                <th style="text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="6"><div class="s-empty">No complaints match these filters.</div></td></tr>
            <?php else: foreach ($rows as $c): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars(complaint_code_display($c['code'])); ?></strong></td>
                    <td><?php echo htmlspecialchars($c['type']); ?></td>
                    <td><?php echo htmlspecialchars($c['location']); ?></td>
                    <td><?php echo htmlspecialchars((new DateTime($c['created_at']))->format('M j, Y')); ?></td>
                    <td><span class="s-badge status-<?php echo htmlspecialchars($c['status']); ?>"><?php echo htmlspecialchars(status_label($c['status'])); ?></span></td>
                    <td style="text-align:right;">
                        <span class="row-actions">
                            <a class="s-btn s-btn--soft s-btn--sm" href="<?php echo htmlspecialchars(url('dashboard/track.php')); ?>?id=<?php echo urlencode($c['code']); ?>"><i class="fas fa-eye"></i> Track</a>
                            <?php if ($c['status'] === 'pending'): ?>
                                <form method="post" data-confirm="Cancel this complaint? This cannot be undone." style="display:inline;">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="code" value="<?php echo htmlspecialchars($c['code']); ?>">
                                    <button class="s-btn s-btn--danger s-btn--sm" type="submit"><i class="fas fa-xmark"></i> Cancel</button>
                                </form>
                            <?php endif; ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
