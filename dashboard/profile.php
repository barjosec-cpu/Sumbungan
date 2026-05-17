<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$u = require_login();
if ($u['role'] === 'admin') {
    header('Location: ' . url('admin/index.php'));
    exit;
}

$error = '';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'profile';

    if ($action === 'profile') {
        $fullName = sanitize_string($_POST['full_name'] ?? '');
        $address = sanitize_string($_POST['address'] ?? '');

        if ($fullName !== '') {
            $pdo->prepare('UPDATE users SET full_name = ?, address = ? WHERE id = ?')->execute([$fullName, $address ?: null, $u['id']]);
            $_SESSION['full_name'] = $fullName;
            $u['full_name'] = $fullName;
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
                    $pdo->prepare('UPDATE users SET profile_pic = ? WHERE id = ?')->execute([$picPath, $u['id']]);
                    $_SESSION['profile_pic'] = $picPath;
                    $u['profile_pic'] = $picPath;
                }
            }
        }
        flash_set('success', 'Profile updated.');
        header('Location: ' . url('dashboard/profile.php'));
        exit;
    }

    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$u['id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New password and confirmation do not match.';
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            flash_set('success', 'Password updated.');
            header('Location: ' . url('dashboard/profile.php'));
            exit;
        }
    }
}

$stmt = $pdo->prepare('SELECT full_name, email, address, profile_pic, created_at FROM users WHERE id = ?');
$stmt->execute([(int) $u['id']]);
$me = $stmt->fetch();

$pageTitle = 'My profile';
$pageSubtitle = 'Manage your details and account security.';
$activeNav = 'profile';
require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>
<?php if ($error !== ''): ?>
    <div class="s-alert s-alert--error"><i class="fas fa-circle-exclamation"></i><span><?php echo htmlspecialchars($error); ?></span></div>
<?php endif; ?>

<div class="s-row s-row--1-2">
    <div class="s-card" style="text-align: center;">
        <?php echo user_avatar_html(['full_name' => $me['full_name'], 'profile_pic' => $me['profile_pic']], 96, 's-avatar-lg'); ?>
        <h3 style="margin-top: 1rem;"><?php echo htmlspecialchars($me['full_name']); ?></h3>
        <p style="color: var(--muted); margin: 0;"><?php echo htmlspecialchars($me['email']); ?></p>
        <p style="color: var(--muted); margin-top: .25rem; font-size: .85rem;">Member since <?php echo htmlspecialchars((new DateTime($me['created_at']))->format('M j, Y')); ?></p>
    </div>

    <div>
        <div class="s-card">
            <div class="s-card__header"><h3 class="s-card__title">Personal information</h3></div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="profile">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="s-field">
                        <label>Full name</label>
                        <input class="s-input" type="text" name="full_name" required value="<?php echo htmlspecialchars($me['full_name']); ?>">
                    </div>
                    <div class="s-field">
                        <label>Email</label>
                        <input class="s-input" type="email" disabled value="<?php echo htmlspecialchars($me['email']); ?>">
                    </div>
                </div>
                <div class="s-field">
                    <label>Address</label>
                    <input class="s-input" type="text" name="address" value="<?php echo htmlspecialchars($me['address'] ?? ''); ?>">
                </div>
                <div class="s-field">
                    <label>Profile photo</label>
                    <input class="s-input" type="file" name="profile_pic" accept="image/*">
                    <div class="s-help">PNG, JPG or WebP, max 2MB.</div>
                </div>
                <button class="s-btn" type="submit"><i class="fas fa-save"></i> Save changes</button>
            </form>
        </div>

        <div class="s-card" style="margin-top: 1rem;">
            <div class="s-card__header"><h3 class="s-card__title">Change password</h3></div>
            <form method="post">
                <input type="hidden" name="action" value="password">
                <div class="s-field">
                    <label>Current password</label>
                    <input class="s-input" type="password" name="current_password" required>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="s-field">
                        <label>New password</label>
                        <input class="s-input" type="password" name="new_password" minlength="8" required>
                    </div>
                    <div class="s-field">
                        <label>Confirm new password</label>
                        <input class="s-input" type="password" name="confirm_password" minlength="8" required>
                    </div>
                </div>
                <button class="s-btn" type="submit"><i class="fas fa-key"></i> Update password</button>
            </form>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
