<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
$u = require_role('admin');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'profile';

    if ($action === 'profile') {
        $fullName = sanitize_string($_POST['full_name'] ?? '');
        if ($fullName !== '') {
            db()->prepare('UPDATE users SET full_name = ? WHERE id = ?')->execute([$fullName, $u['id']]);
            $_SESSION['full_name'] = $fullName;
        }
        if (!empty($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $f = $_FILES['profile_pic'];
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime = mime_content_type($f['tmp_name']);
            if (isset($allowed[$mime]) && $f['size'] <= 2 * 1024 * 1024) {
                $dir = ROOT_PATH . '/assets/uploads/';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $name = 'p_' . $u['id'] . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
                if (move_uploaded_file($f['tmp_name'], $dir . $name)) {
                    $picPath = 'uploads/' . $name;
                    db()->prepare('UPDATE users SET profile_pic = ? WHERE id = ?')->execute([$picPath, $u['id']]);
                    $_SESSION['profile_pic'] = $picPath;
                }
            }
        }
        flash_set('success', 'Profile updated.');
        header('Location: ' . url('admin/profile.php'));
        exit;
    }

    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$u['id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New password and confirmation do not match.';
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            flash_set('success', 'Password updated.');
            header('Location: ' . url('admin/profile.php'));
            exit;
        }
    }
}

$stmt = db()->prepare('SELECT full_name, email, profile_pic, created_at FROM users WHERE id = ?');
$stmt->execute([(int) $u['id']]);
$me = $stmt->fetch();

$pageTitle = 'My profile';
$activeNav = 'profile';
require_once dirname(__DIR__) . '/includes/admin_header.php';
?>
<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><i class="fas fa-circle-exclamation mr-1"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <?php echo user_avatar_html($me, 96, 's-avatar-lg'); ?>
                <h4 class="mt-3 mb-1"><?php echo htmlspecialchars($me['full_name']); ?></h4>
                <p class="text-muted"><?php echo htmlspecialchars($me['email']); ?></p>
                <p class="text-muted small">Administrator since <?php echo htmlspecialchars((new DateTime($me['created_at']))->format('M j, Y')); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Personal information</h3></div>
            <form method="post" enctype="multipart/form-data" class="card-body">
                <input type="hidden" name="action" value="profile">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($me['full_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" disabled value="<?php echo htmlspecialchars($me['email']); ?>">
                </div>
                <div class="form-group">
                    <label>Profile photo</label>
                    <input type="file" name="profile_pic" class="form-control-file" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save</button>
            </form>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Change password</h3></div>
            <form method="post" class="card-body">
                <input type="hidden" name="action" value="password">
                <div class="form-group">
                    <label>Current password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>New password</label>
                        <input type="password" name="new_password" class="form-control" minlength="8" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Confirm</label>
                        <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-key mr-1"></i> Update password</button>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
<?php require_once dirname(__DIR__) . '/includes/admin_layout_end.php'; ?>
