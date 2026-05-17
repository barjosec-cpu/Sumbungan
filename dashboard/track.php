<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$u = require_login();
if ($u['role'] === 'admin') {
    header('Location: ' . url('admin/index.php'));
    exit;
}

$code = normalize_complaint_id($_GET['id'] ?? '');
$complaint = null;
$timeline = [];

if ($code !== '') {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM complaints WHERE code = ? AND complainant_id = ? LIMIT 1');
    $stmt->execute([$code, (int) $u['id']]);
    $complaint = $stmt->fetch();
    if ($complaint) {
        $stmt = $pdo->prepare('SELECT status_label, details, created_at FROM complaint_timeline WHERE complaint_id = ? ORDER BY created_at ASC');
        $stmt->execute([(int) $complaint['id']]);
        $timeline = $stmt->fetchAll();
    }
}

$pageTitle = 'Tracking';
$pageSubtitle = 'Enter your case ID to view the latest status and timeline.';
$activeNav = 'track';
require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>
<div class="s-card" style="max-width: 760px;">
    <form method="get" class="s-toolbar" style="margin-bottom: 0;">
        <div class="s-toolbar__filters" style="flex: 1;">
            <input class="s-input" type="text" name="id" placeholder="Case ID e.g. BRY-4025" value="<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>" style="flex: 1;">
        </div>
        <button class="s-btn" type="submit"><i class="fas fa-magnifying-glass"></i> Track</button>
    </form>
</div>

<?php if ($code !== '' && !$complaint): ?>
    <div class="s-alert s-alert--warn" style="margin-top: 1rem;"><i class="fas fa-circle-info"></i><span>Case not found, or it does not belong to your account.</span></div>
<?php endif; ?>

<?php if ($complaint): ?>
    <div class="s-row s-row--2-1" style="margin-top: 1rem;">
        <div class="s-card">
            <div class="s-card__header">
                <div>
                    <h3 class="s-card__title"><?php echo htmlspecialchars(complaint_code_display($complaint['code'])); ?></h3>
                    <p class="s-card__sub"><?php echo htmlspecialchars($complaint['type']); ?> · <?php echo htmlspecialchars($complaint['location']); ?></p>
                </div>
                <span class="s-badge status-<?php echo htmlspecialchars($complaint['status']); ?>"><?php echo htmlspecialchars(status_label($complaint['status'])); ?></span>
            </div>

            <p style="white-space: pre-line;"><?php echo htmlspecialchars($complaint['description']); ?></p>

            <?php if (!empty($complaint['photo_path'])): ?>
                <div style="margin-top: 1rem;">
                    <img src="<?php echo htmlspecialchars(asset($complaint['photo_path'])); ?>" alt="Attachment" style="max-width: 100%; border-radius: var(--radius);">
                </div>
            <?php endif; ?>
        </div>

        <div class="s-card">
            <div class="s-card__header"><h3 class="s-card__title">Timeline</h3></div>
            <div class="s-timeline">
                <?php foreach ($timeline as $t): ?>
                    <div class="s-timeline__item">
                        <div class="s-timeline__dot"></div>
                        <div class="s-timeline__title"><?php echo htmlspecialchars($t['status_label']); ?></div>
                        <?php if (!empty($t['details'])): ?>
                            <div style="margin: .25rem 0; color: var(--ink-soft); font-size: .9rem;"><?php echo htmlspecialchars($t['details']); ?></div>
                        <?php endif; ?>
                        <div class="s-timeline__meta"><?php echo htmlspecialchars((new DateTime($t['created_at']))->format('M j, Y g:i A')); ?></div>
                    </div>
                <?php endforeach; ?>
                <div class="s-timeline__item is-pending">
                    <div class="s-timeline__dot"></div>
                    <div class="s-timeline__title">Awaiting next update</div>
                    <div class="s-timeline__meta">You'll be notified when there's progress.</div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
