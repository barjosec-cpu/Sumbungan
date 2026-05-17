<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_string($_POST['barangay_name'] ?? '');
    $contact = sanitize_string($_POST['contact_number'] ?? '');
    $addr = sanitize_string($_POST['address'] ?? '');
    if ($name !== '') {
        db()->prepare('UPDATE barangay_settings SET barangay_name = ?, contact_number = ?, address = ? WHERE id = 1')
            ->execute([$name, $contact ?: null, $addr ?: null]);
        flash_set('success', 'Barangay settings saved.');
    }
    header('Location: ' . url('admin/settings.php'));
    exit;
}

$stmt = db()->query('SELECT * FROM barangay_settings WHERE id = 1 LIMIT 1');
$settings = $stmt->fetch() ?: ['barangay_name' => '', 'contact_number' => '', 'address' => ''];

$totalCases = (int) db()->query('SELECT COUNT(*) FROM complaints')->fetchColumn();
$totalUsers = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$dbVersion = db()->query('SELECT VERSION()')->fetchColumn();

$pageTitle = 'Barangay settings';
$pageSubtitle = 'Manage barangay identity and system info.';
$activeNav = 'settings';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">General information</h3></div>
            <form method="post" class="card-body">
                <div class="form-group">
                    <label>Barangay name</label>
                    <input type="text" name="barangay_name" class="form-control" required value="<?php echo htmlspecialchars($settings['barangay_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Contact number</label>
                    <input type="text" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($settings['contact_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($settings['address'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save</button>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">System info</h3></div>
            <div class="card-body">
                <p class="mb-1 text-muted" style="font-size:.8rem;">PHP version</p>
                <p class="mb-3"><strong><?php echo htmlspecialchars(PHP_VERSION); ?></strong></p>
                <p class="mb-1 text-muted" style="font-size:.8rem;">MySQL version</p>
                <p class="mb-3"><strong><?php echo htmlspecialchars((string) $dbVersion); ?></strong></p>
                <p class="mb-1 text-muted" style="font-size:.8rem;">Total complaints</p>
                <p class="mb-3"><strong><?php echo $totalCases; ?></strong></p>
                <p class="mb-1 text-muted" style="font-size:.8rem;">Total users</p>
                <p class="mb-0"><strong><?php echo $totalUsers; ?></strong></p>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">API quick links</h3></div>
            <div class="card-body p-0">
                <a class="dropdown-item" href="<?php echo htmlspecialchars(url('docs/API.md')); ?>" target="_blank"><i class="fas fa-book mr-1"></i> API documentation</a>
                <a class="dropdown-item" href="<?php echo htmlspecialchars(url('api/analytics.php')); ?>?metric=summary" target="_blank"><i class="fas fa-link mr-1"></i> /api/analytics.php?metric=summary</a>
                <a class="dropdown-item" href="<?php echo htmlspecialchars(url('api/complaints.php')); ?>" target="_blank"><i class="fas fa-link mr-1"></i> /api/complaints.php</a>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
<?php require_once dirname(__DIR__) . '/includes/admin_layout_end.php'; ?>
