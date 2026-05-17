<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$u = require_login();
if ($u['role'] === 'admin') {
    header('Location: ' . url('admin/index.php'));
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = sanitize_string($_POST['type'] ?? '');
    $location = sanitize_string($_POST['location'] ?? '');
    $description = sanitize_string($_POST['description'] ?? '');
    $photoPath = null;
    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['photo'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $mime = mime_content_type($f['tmp_name']);
        if (isset($allowed[$mime]) && $f['size'] <= 5 * 1024 * 1024) {
            $dir = ROOT_PATH . '/assets/uploads/';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $name = 'c_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
            if (move_uploaded_file($f['tmp_name'], $dir . $name)) {
                $photoPath = 'uploads/' . $name;
            }
        }
    }
    try {
        $row = complaint_repo_create(db(), (int) $u['id'], $type, $location, $description, $photoPath);
        add_notification(db(), (int) $u['id'], 'Your complaint ' . complaint_code_display($row['code']) . ' was received and is pending review.');
        flash_set('success', 'Complaint filed successfully. Case ID: ' . complaint_code_display($row['code']));
        header('Location: ' . url('dashboard/track.php') . '?id=' . urlencode($row['code']));
        exit;
    } catch (InvalidArgumentException) {
        $error = 'Please choose a valid complaint type and fill all required fields.';
    } catch (Throwable) {
        $error = 'Could not submit complaint. Please try again.';
    }
}

$pageTitle = 'File a complaint';
$pageSubtitle = 'Describe the incident with as much detail as possible.';
$activeNav = 'file';
require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>
<?php if ($error !== ''): ?>
    <div class="s-alert s-alert--error"><i class="fas fa-circle-exclamation"></i><span><?php echo htmlspecialchars($error); ?></span></div>
<?php endif; ?>

<div class="s-card" style="max-width: 860px;">
    <form method="post" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="s-field">
                <label for="type">Nature of complaint</label>
                <select id="type" name="type" class="s-select" required>
                    <option value="Noise">Noisy neighbors / disturbance</option>
                    <option value="Sanitation">Waste / sanitation</option>
                    <option value="Security">Security / safety</option>
                    <option value="Traffic">Traffic / parking</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="s-field">
                <label for="location">Exact location</label>
                <input class="s-input" id="location" name="location" type="text" required placeholder="e.g. Purok 3, near chapel">
            </div>
        </div>
        <div class="s-field">
            <label for="description">Detailed description</label>
            <textarea class="s-textarea" id="description" name="description" required placeholder="When did it happen? Who or what is involved?"></textarea>
        </div>
        <div class="s-field">
            <label>Photo (optional, max 5MB)</label>
            <label class="s-upload" data-upload>
                <input type="file" name="photo" accept="image/*">
                <div class="s-upload__placeholder">
                    <i class="fas fa-cloud-arrow-up"></i>
                    <strong>Click to upload</strong>
                    <small>JPG, PNG, WebP up to 5MB</small>
                </div>
                <div class="s-upload__preview"><img src="" alt=""></div>
            </label>
        </div>
        <div style="display: flex; gap: .5rem; justify-content: flex-end;">
            <a class="s-btn s-btn--ghost" href="<?php echo htmlspecialchars(url('dashboard/index.php')); ?>">Cancel</a>
            <button class="s-btn" type="submit"><i class="fas fa-paper-plane"></i> Submit report</button>
        </div>
    </form>
</div>
<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
