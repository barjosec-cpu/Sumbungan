<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_role('admin');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $codes = $_POST['codes'] ?? [];
    if (!is_array($codes)) {
        $codes = [];
    }
    $codes = array_filter(array_map('strval', $codes), fn ($x) => $x !== '');

    if ($action !== '' && count($codes) > 0) {
        $in = implode(',', array_fill(0, count($codes), '?'));

        if (in_array($action, ['set_pending', 'set_in_progress', 'set_resolved', 'set_rejected'], true)) {
            $map = ['set_pending' => 'pending', 'set_in_progress' => 'in-progress', 'set_resolved' => 'resolved', 'set_rejected' => 'rejected'];
            $newStatus = $map[$action];
            $stmt = $pdo->prepare("UPDATE complaints SET status = ? WHERE code IN ($in)");
            $stmt->execute(array_merge([$newStatus], $codes));

            $stmt = $pdo->prepare("SELECT id, code, complainant_id FROM complaints WHERE code IN ($in)");
            $stmt->execute($codes);
            foreach ($stmt->fetchAll() as $r) {
                $pdo->prepare('INSERT INTO complaint_timeline (complaint_id, status_label, details) VALUES (?,?,?)')
                    ->execute([(int) $r['id'], 'Status: ' . status_label($newStatus), 'Status updated by administrator.']);
                add_notification($pdo, (int) $r['complainant_id'], 'Case ' . complaint_code_display($r['code']) . ' status changed to ' . status_label($newStatus) . '.');
            }
            flash_set('success', count($codes) . ' case(s) updated to ' . status_label($newStatus) . '.');
        }

        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM complaints WHERE code IN ($in)");
            $stmt->execute($codes);
            flash_set('success', count($codes) . ' case(s) deleted.');
        }
    }
    $qs = http_build_query(array_intersect_key($_POST, array_flip(['status', 'type', 'q'])));
    header('Location: ' . url('admin/cases.php') . ($qs !== '' ? '?' . $qs : ''));
    exit;
}

$status = sanitize_string($_GET['status'] ?? '');
$type = sanitize_string($_GET['type'] ?? '');
$q = sanitize_string($_GET['q'] ?? '');

$sql = 'SELECT c.*, u.full_name AS complainant_name FROM complaints c LEFT JOIN users u ON u.id = c.complainant_id WHERE 1=1';
$params = [];
if (in_array($status, ['pending', 'in-progress', 'resolved', 'rejected'], true)) {
    $sql .= ' AND c.status = ?';
    $params[] = $status;
}
if (in_array($type, ['Noise', 'Sanitation', 'Security', 'Traffic', 'Other'], true)) {
    $sql .= ' AND c.type = ?';
    $params[] = $type;
}
if ($q !== '') {
    $sql .= ' AND (c.code LIKE ? OR c.location LIKE ? OR u.full_name LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
$sql .= ' ORDER BY c.created_at DESC LIMIT 500';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$exportUrl = url('api/export.php') . '?type=cases'
    . ($status !== '' ? '&status=' . urlencode($status) : '')
    . ($type !== '' ? '&type=' . urlencode($type) : '')
    . ($q !== '' ? '&q=' . urlencode($q) : '');

$pageTitle = 'Case management';
$pageSubtitle = 'Search, filter, update and export complaints.';
$activeNav = 'cases';
$pageActions = '<a class="btn btn-outline-primary mr-2" href="' . htmlspecialchars($exportUrl) . '"><i class="fas fa-file-csv mr-1"></i> Export CSV</a><a class="btn btn-primary" href="' . htmlspecialchars(url('admin/cases.php')) . '?status=pending"><i class="fas fa-hourglass-half mr-1"></i> Pending only</a>';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>
<div class="card">
    <div class="card-header">
        <form method="get" class="form-inline" style="gap:.5rem; flex-wrap: wrap;">
            <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search code, location, complainant..." class="form-control mr-2 mb-2" style="min-width: 240px;">
            <select name="status" class="form-control mr-2 mb-2">
                <option value="">All statuses</option>
                <?php foreach (['pending', 'in-progress', 'resolved', 'rejected'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo htmlspecialchars(status_label($s)); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="type" class="form-control mr-2 mb-2">
                <option value="">All types</option>
                <?php foreach (['Noise', 'Sanitation', 'Security', 'Traffic', 'Other'] as $t): ?>
                    <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $type === $t ? 'selected' : ''; ?>><?php echo htmlspecialchars($t); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary mb-2 mr-2" type="submit"><i class="fas fa-filter mr-1"></i> Filter</button>
            <a class="btn btn-link mb-2" href="<?php echo htmlspecialchars(url('admin/cases.php')); ?>">Reset</a>
        </form>
    </div>

    <form method="post" id="bulkForm">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
        <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" data-check-all="input[name='codes[]']"></th>
                    <th>Code</th>
                    <th>Complainant</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Filed</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($rows) === 0): ?>
                    <tr><td colspan="8" class="text-center text-muted p-4">No cases match these filters.</td></tr>
                <?php else: foreach ($rows as $c): ?>
                    <tr>
                        <td><input type="checkbox" name="codes[]" value="<?php echo htmlspecialchars($c['code']); ?>"></td>
                        <td><strong><?php echo htmlspecialchars(complaint_code_display($c['code'])); ?></strong></td>
                        <td><?php echo htmlspecialchars($c['complainant_name'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($c['type']); ?></td>
                        <td><?php echo htmlspecialchars($c['location']); ?></td>
                        <td><span class="badge badge-<?php echo htmlspecialchars(status_badge_class($c['status'])); ?>"><?php echo htmlspecialchars(status_label($c['status'])); ?></span></td>
                        <td><?php echo htmlspecialchars((new DateTime($c['created_at']))->format('M j, Y')); ?></td>
                        <td><a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars(url('admin/case-view.php')); ?>?id=<?php echo urlencode($c['code']); ?>"><i class="fas fa-edit"></i> Manage</a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center" style="background:#fff;">
            <div class="text-muted" style="font-size:.85rem;"><?php echo count($rows); ?> case(s) shown.</div>
            <div class="form-inline">
                <select name="action" class="form-control mr-2" required>
                    <option value="">Bulk action…</option>
                    <option value="set_pending">Mark as Pending</option>
                    <option value="set_in_progress">Mark as In Progress</option>
                    <option value="set_resolved">Mark as Resolved</option>
                    <option value="set_rejected">Mark as Rejected</option>
                    <option value="delete">Delete selected</option>
                </select>
                <button class="btn btn-success" type="submit" data-confirm-link="Apply this bulk action to selected cases?"><i class="fas fa-bolt mr-1"></i> Apply</button>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('bulkForm').addEventListener('submit', function (e) {
    const action = this.querySelector('select[name="action"]').value;
    const checked = this.querySelectorAll('input[name="codes[]"]:checked').length;
    if (!action) { e.preventDefault(); alert('Choose a bulk action.'); return; }
    if (checked === 0) { e.preventDefault(); alert('Select at least one case.'); return; }
    const verb = action === 'delete' ? 'delete' : 'change status of';
    if (!confirm('Are you sure you want to ' + verb + ' ' + checked + ' case(s)?')) { e.preventDefault(); }
});
</script>
<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
<?php require_once dirname(__DIR__) . '/includes/admin_layout_end.php'; ?>
