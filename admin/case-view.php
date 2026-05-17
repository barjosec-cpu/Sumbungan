<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_role('admin');

$code = normalize_complaint_id($_GET['id'] ?? '');
$pdo = db();
$complaint = null;
if ($code !== '') {
    $stmt = $pdo->prepare('SELECT c.*, u.full_name AS complainant_name, u.email AS complainant_email, u.address AS complainant_address FROM complaints c LEFT JOIN users u ON u.id = c.complainant_id WHERE c.code = ? LIMIT 1');
    $stmt->execute([$code]);
    $complaint = $stmt->fetch();
}

if (!$complaint) {
    $pageTitle = 'Case not found';
    $activeNav = 'cases';
    require_once dirname(__DIR__) . '/includes/admin_header.php';
    echo '<div class="alert alert-warning">Complaint not found.</div>';
    require_once dirname(__DIR__) . '/includes/admin_footer.php';
    require_once dirname(__DIR__) . '/includes/admin_layout_end.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';
    $cid = (int) $complaint['id'];

    if ($action === 'update') {
        $newStatus = sanitize_string($_POST['status'] ?? '');
        $timelineLabel = sanitize_string($_POST['timeline_label'] ?? '');
        $timelineDetails = sanitize_string($_POST['timeline_details'] ?? '');

        $changed = false;
        if (in_array($newStatus, ['pending', 'in-progress', 'resolved', 'rejected'], true) && $newStatus !== $complaint['status']) {
            $pdo->prepare('UPDATE complaints SET status = ? WHERE id = ?')->execute([$newStatus, $cid]);
            $complaint['status'] = $newStatus;
            $changed = true;
            add_notification($pdo, (int) $complaint['complainant_id'], 'Case ' . complaint_code_display($complaint['code']) . ' status changed to ' . status_label($newStatus) . '.');
            $pdo->prepare('INSERT INTO complaint_timeline (complaint_id, status_label, details) VALUES (?,?,?)')
                ->execute([$cid, 'Status: ' . status_label($newStatus), 'Updated by administrator.']);
        }
        if ($timelineLabel !== '') {
            $pdo->prepare('INSERT INTO complaint_timeline (complaint_id, status_label, details) VALUES (?,?,?)')
                ->execute([$cid, $timelineLabel, $timelineDetails ?: null]);
            $changed = true;
        }
        flash_set($changed ? 'success' : 'info', $changed ? 'Case updated.' : 'No changes.');
    }

    if ($action === 'message') {
        $msg = sanitize_string($_POST['message'] ?? '');
        if ($msg !== '') {
            add_notification($pdo, (int) $complaint['complainant_id'], 'Case ' . complaint_code_display($complaint['code']) . ': ' . $msg);
            flash_set('success', 'Notification sent to complainant.');
        }
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM complaints WHERE id = ?')->execute([$cid]);
        flash_set('success', 'Case deleted.');
        header('Location: ' . url('admin/cases.php'));
        exit;
    }

    header('Location: ' . url('admin/case-view.php') . '?id=' . urlencode($complaint['code']));
    exit;
}

$stmt = $pdo->prepare('SELECT status_label, details, created_at FROM complaint_timeline WHERE complaint_id = ? ORDER BY created_at ASC');
$stmt->execute([(int) $complaint['id']]);
$timeline = $stmt->fetchAll();

$pageTitle = 'Case ' . complaint_code_display($complaint['code']);
$pageSubtitle = htmlspecialchars($complaint['type']) . ' · ' . htmlspecialchars($complaint['location']);
$activeNav = 'cases';
$pageActions = '<a class="btn btn-outline-primary" href="' . htmlspecialchars(url('admin/cases.php')) . '"><i class="fas fa-arrow-left mr-1"></i> Back to cases</a>';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Case details</h3>
                <span class="badge badge-<?php echo htmlspecialchars(status_badge_class($complaint['status'])); ?>" style="font-size:.85rem;"><?php echo htmlspecialchars(status_label($complaint['status'])); ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6"><p class="text-muted mb-0" style="font-size:.8rem;">Complainant</p><p><?php echo htmlspecialchars($complaint['complainant_name'] ?? '—'); ?></p></div>
                    <div class="col-md-6"><p class="text-muted mb-0" style="font-size:.8rem;">Email</p><p><?php echo htmlspecialchars($complaint['complainant_email'] ?? '—'); ?></p></div>
                    <div class="col-md-6"><p class="text-muted mb-0" style="font-size:.8rem;">Type</p><p><?php echo htmlspecialchars($complaint['type']); ?></p></div>
                    <div class="col-md-6"><p class="text-muted mb-0" style="font-size:.8rem;">Filed</p><p><?php echo htmlspecialchars((new DateTime($complaint['created_at']))->format('M j, Y g:i A')); ?></p></div>
                    <div class="col-12"><p class="text-muted mb-0" style="font-size:.8rem;">Location</p><p><?php echo htmlspecialchars($complaint['location']); ?></p></div>
                    <div class="col-12">
                        <p class="text-muted mb-0" style="font-size:.8rem;">Description</p>
                        <p style="white-space: pre-line;"><?php echo htmlspecialchars($complaint['description']); ?></p>
                    </div>
                    <?php if (!empty($complaint['photo_path'])): ?>
                        <div class="col-12">
                            <p class="text-muted mb-1" style="font-size:.8rem;">Attachment</p>
                            <a href="<?php echo htmlspecialchars(asset($complaint['photo_path'])); ?>" target="_blank" rel="noopener">
                                <img src="<?php echo htmlspecialchars(asset($complaint['photo_path'])); ?>" alt="Attachment" class="img-fluid" style="max-height: 320px; border-radius: 12px;">
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Timeline</h3></div>
            <div class="card-body">
                <div class="s-timeline">
                    <?php foreach ($timeline as $t): ?>
                        <div class="s-timeline__item">
                            <div class="s-timeline__dot"></div>
                            <div class="s-timeline__title"><?php echo htmlspecialchars($t['status_label']); ?></div>
                            <?php if (!empty($t['details'])): ?>
                                <div style="color: var(--ink-soft); font-size: .9rem;"><?php echo htmlspecialchars($t['details']); ?></div>
                            <?php endif; ?>
                            <div class="s-timeline__meta"><?php echo htmlspecialchars((new DateTime($t['created_at']))->format('M j, Y g:i A')); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Update status & timeline</h3></div>
            <form method="post" class="card-body">
                <input type="hidden" name="action" value="update">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <?php foreach (['pending', 'in-progress', 'resolved', 'rejected'] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo $complaint['status'] === $s ? 'selected' : ''; ?>><?php echo htmlspecialchars(status_label($s)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Timeline label</label>
                    <input type="text" name="timeline_label" class="form-control" placeholder="e.g. Site inspection scheduled">
                </div>
                <div class="form-group">
                    <label>Details (optional)</label>
                    <textarea name="timeline_details" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i> Save</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Message complainant</h3></div>
            <form method="post" class="card-body">
                <input type="hidden" name="action" value="message">
                <div class="form-group">
                    <label>Send a notification</label>
                    <textarea name="message" class="form-control" rows="3" required placeholder="A short update for the complainant"></textarea>
                </div>
                <button type="submit" class="btn btn-success btn-block"><i class="fas fa-paper-plane mr-1"></i> Send</button>
            </form>
        </div>

        <form method="post" data-confirm="Permanently delete this case and its timeline? This cannot be undone.">
            <input type="hidden" name="action" value="delete">
            <button class="btn btn-outline-danger btn-block" type="submit"><i class="fas fa-trash mr-1"></i> Delete case</button>
        </form>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
<?php require_once dirname(__DIR__) . '/includes/admin_layout_end.php'; ?>
