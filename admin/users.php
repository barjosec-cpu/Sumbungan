<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
$adminUser = require_role('admin');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($action === 'create_user') {
        $fullName = sanitize_string($_POST['full_name'] ?? '');
        $email = strtolower(sanitize_string($_POST['email'] ?? ''));
        $role = sanitize_string($_POST['role'] ?? 'complainant');
        $address = sanitize_string($_POST['address'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($fullName === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'Please provide a valid full name and email.');
        } elseif (!in_array($role, ['complainant', 'officer', 'admin'], true)) {
            flash_set('error', 'Invalid role selected.');
        } elseif (strlen($password) < 8) {
            flash_set('error', 'Password must be at least 8 characters.');
        } else {
            $exists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $exists->execute([$email]);
            if ((int) $exists->fetchColumn() > 0) {
                flash_set('error', 'Email is already in use.');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, address) VALUES (?,?,?,?,?)')
                    ->execute([$fullName, $email, $hash, $role, $address !== '' ? $address : null]);
                flash_set('success', 'User account created.');
            }
        }
    }

    if ($action === 'update_user' && $userId > 0) {
        $fullName = sanitize_string($_POST['full_name'] ?? '');
        $email = strtolower(sanitize_string($_POST['email'] ?? ''));
        $role = sanitize_string($_POST['role'] ?? 'complainant');
        $address = sanitize_string($_POST['address'] ?? '');

        if ($fullName === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'Please provide a valid full name and email.');
        } elseif (!in_array($role, ['complainant', 'officer', 'admin'], true)) {
            flash_set('error', 'Invalid role selected.');
        } elseif ($userId === (int) $adminUser['id'] && $role !== 'admin') {
            flash_set('error', 'You cannot remove your own admin role.');
        } else {
            $exists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?');
            $exists->execute([$email, $userId]);
            if ((int) $exists->fetchColumn() > 0) {
                flash_set('error', 'Email is already in use by another account.');
            } else {
                $pdo->prepare('UPDATE users SET full_name = ?, email = ?, role = ?, address = ? WHERE id = ?')
                    ->execute([$fullName, $email, $role, $address !== '' ? $address : null, $userId]);
                flash_set('success', 'User account updated.');
            }
        }
    }

    if ($action === 'reset_password' && $userId > 0) {
        $temp = bin2hex(random_bytes(4));
        $hash = password_hash($temp, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);
        flash_set('success', 'Temporary password generated: ' . $temp);
    }

    if ($action === 'delete' && $userId > 0) {
        if ($userId === (int) $adminUser['id']) {
            flash_set('error', 'You cannot delete your own account.');
        } else {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
            flash_set('success', 'User account removed.');
        }
    }

    if ($action === 'notify' && $userId > 0) {
        $msg = sanitize_string($_POST['message'] ?? '');
        if ($msg !== '') {
            add_notification($pdo, $userId, $msg);
            flash_set('success', 'Notification sent.');
        }
    }

    header('Location: ' . url('admin/users.php'));
    exit;
}

$q = sanitize_string($_GET['q'] ?? '');
$roleFilter = sanitize_string($_GET['role'] ?? '');
$sql = "SELECT
        u.id, u.full_name, u.email, u.role, u.address, u.profile_pic, u.created_at,
        COALESCE(cs.total_cases, 0) AS total_cases,
        COALESCE(cs.pending_cases, 0) AS pending_cases
    FROM users u
    LEFT JOIN (
        SELECT
            complainant_id,
            COUNT(*) AS total_cases,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_cases
        FROM complaints
        GROUP BY complainant_id
    ) cs ON cs.complainant_id = u.id
    WHERE 1=1";
$params = [];
if (in_array($roleFilter, ['complainant', 'officer', 'admin'], true)) {
    $sql .= ' AND u.role = ?';
    $params[] = $roleFilter;
}
if ($q !== '') {
    $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR u.address LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
$sql .= ' ORDER BY u.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalResidents = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'complainant'")->fetchColumn();
$totalStaff = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'officer')")->fetchColumn();
$activeFilers = (int) $pdo->query("SELECT COUNT(DISTINCT complainant_id) FROM complaints WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

$pageTitle = 'User accounts';
$pageSubtitle = 'Manage residents, officers, and administrators.';
$activeNav = 'users';
$pageActions = '<a class="btn btn-outline-primary mr-2" href="' . htmlspecialchars(url('api/export.php')) . '?type=users"><i class="fas fa-file-csv mr-1"></i> Export CSV</a><button type="button" class="btn btn-primary" data-toggle="collapse" data-target="#addUserForm" aria-expanded="false" aria-controls="addUserForm"><i class="fas fa-user-plus mr-1"></i> Add account</button>';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>

<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
            <div class="info-box-content"><span class="info-box-text">Total accounts</span><span class="info-box-number"><?php echo $totalUsers; ?></span></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-home"></i></span>
            <div class="info-box-content"><span class="info-box-text">Residents</span><span class="info-box-number"><?php echo $totalResidents; ?></span></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-user-shield"></i></span>
            <div class="info-box-content"><span class="info-box-text">Staff (admin/officer)</span><span class="info-box-number"><?php echo $totalStaff; ?></span></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="info-box">
            <span class="info-box-icon bg-secondary"><i class="fas fa-bolt"></i></span>
            <div class="info-box-content"><span class="info-box-text">Active filers (30d)</span><span class="info-box-number"><?php echo $activeFilers; ?></span></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="collapse mb-3" id="addUserForm">
            <form method="post" class="border rounded p-3">
                <input type="hidden" name="action" value="create_user">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Full name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Role</label>
                        <select name="role" class="form-control" required>
                            <option value="complainant">Complainant</option>
                            <option value="officer">Officer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Temporary password</label>
                        <input type="text" name="password" class="form-control" minlength="8" required>
                    </div>
                </div>
                <div class="form-group mb-2">
                    <label>Address (optional)</label>
                    <input type="text" name="address" class="form-control">
                </div>
                <button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i> Create account</button>
            </form>
        </div>

        <form method="get" class="form-inline" style="gap:.5rem; flex-wrap:wrap;">
            <input type="search" name="q" class="form-control mr-2" placeholder="Search by name, email, address" value="<?php echo htmlspecialchars($q); ?>" style="min-width:280px;">
            <select name="role" class="form-control mr-2">
                <option value="">All roles</option>
                <?php foreach (['complainant' => 'Complainant', 'officer' => 'Officer', 'admin' => 'Admin'] as $role => $label): ?>
                    <option value="<?php echo htmlspecialchars($role); ?>" <?php echo $roleFilter === $role ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary mr-2" type="submit"><i class="fas fa-search mr-1"></i> Search</button>
            <a class="btn btn-link" href="<?php echo htmlspecialchars(url('admin/users.php')); ?>">Reset</a>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Address</th><th>Cases</th><th>Pending</th><th>Joined</th><th></th></tr></thead>
            <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="8" class="text-center text-muted p-4">No users match.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:.65rem;">
                            <?php echo user_avatar_html(['full_name' => $r['full_name'], 'profile_pic' => $r['profile_pic']], 32); ?>
                            <strong><?php echo htmlspecialchars($r['full_name']); ?></strong>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($r['email']); ?></td>
                    <td><span class="badge badge-<?php echo $r['role'] === 'admin' ? 'danger' : ($r['role'] === 'officer' ? 'warning' : 'info'); ?>"><?php echo htmlspecialchars(ucfirst($r['role'])); ?></span></td>
                    <td><?php echo htmlspecialchars($r['address'] ?? '--'); ?></td>
                    <td><span class="badge badge-info"><?php echo (int) $r['total_cases']; ?></span></td>
                    <td><?php if ((int) $r['pending_cases'] > 0): ?><span class="badge badge-danger"><?php echo (int) $r['pending_cases']; ?></span><?php else: ?>--<?php endif; ?></td>
                    <td><?php echo htmlspecialchars((new DateTime($r['created_at']))->format('M j, Y')); ?></td>
                    <td class="text-right">
                        <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#notifyModal<?php echo (int) $r['id']; ?>"><i class="fas fa-paper-plane"></i></button>
                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#editModal<?php echo (int) $r['id']; ?>"><i class="fas fa-user-edit"></i></button>
                        <form method="post" data-confirm="Reset this user's password?" style="display:inline;">
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="user_id" value="<?php echo (int) $r['id']; ?>">
                            <button class="btn btn-sm btn-warning" type="submit" title="Reset password"><i class="fas fa-key"></i></button>
                        </form>
                        <form method="post" data-confirm="Permanently delete this user? Related records may also be removed." style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?php echo (int) $r['id']; ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete" <?php echo (int) $r['id'] === (int) $adminUser['id'] ? 'disabled' : ''; ?>><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($rows as $r): ?>
    <div class="modal fade" id="editModal<?php echo (int) $r['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="post" class="modal-content">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" value="<?php echo (int) $r['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit user: <?php echo htmlspecialchars($r['full_name']); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full name</label>
                        <input type="text" class="form-control" name="full_name" required value="<?php echo htmlspecialchars($r['full_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($r['email']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select class="form-control" name="role" required>
                            <?php foreach (['complainant' => 'Complainant', 'officer' => 'Officer', 'admin' => 'Admin'] as $role => $label): ?>
                                <option value="<?php echo htmlspecialchars($role); ?>" <?php echo $r['role'] === $role ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Address</label>
                        <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($r['address'] ?? ''); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="notifyModal<?php echo (int) $r['id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="post" class="modal-content">
                <input type="hidden" name="action" value="notify">
                <input type="hidden" name="user_id" value="<?php echo (int) $r['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Send notification to <?php echo htmlspecialchars($r['full_name']); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <textarea name="message" class="form-control" rows="4" required placeholder="Type your message..." autocomplete="off"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Send</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
<?php require_once dirname(__DIR__) . '/includes/admin_layout_end.php'; ?>