<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$u = require_login();
if ($u['role'] === 'admin') {
    header('Location: ' . url('admin/index.php'));
    exit;
}

$pdo = db();
$uid = (int) $u['id'];

$stmt = $pdo->prepare("SELECT status, COUNT(*) AS c FROM complaints WHERE complainant_id = ? GROUP BY status");
$stmt->execute([$uid]);
$counts = ['pending' => 0, 'in-progress' => 0, 'resolved' => 0, 'rejected' => 0];
$total = 0;
foreach ($stmt->fetchAll() as $r) {
    $counts[$r['status']] = (int) $r['c'];
    $total += (int) $r['c'];
}

$stmt = $pdo->prepare('SELECT code, type, status, created_at FROM complaints WHERE complainant_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$uid]);
$recent = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT DATE(created_at) AS d, COUNT(*) AS c
    FROM complaints
    WHERE complainant_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at)
    ORDER BY d ASC
");
$stmt->execute([$uid]);
$daily = [];
foreach ($stmt->fetchAll() as $r) {
    $daily[$r['d']] = (int) $r['c'];
}
$labels = [];
$values = [];
for ($i = 13; $i >= 0; $i--) {
    $d = (new DateTimeImmutable())->modify("-{$i} days")->format('Y-m-d');
    $labels[] = (new DateTimeImmutable($d))->format('M j');
    $values[] = $daily[$d] ?? 0;
}

$pageTitle = 'Hi, ' . explode(' ', $u['full_name'])[0];
$pageSubtitle = 'Here is what is happening with your complaints.';
$activeNav = 'overview';
$pageActions = '<a class="s-btn" href="' . htmlspecialchars(url('dashboard/file-complaint.php')) . '"><i class="fas fa-plus"></i> File a complaint</a>';
require_once dirname(__DIR__) . '/includes/dashboard_header.php';
?>
<div class="s-kpi-grid">
    <div class="s-kpi"><div class="s-kpi__icon"><i class="fas fa-folder-open"></i></div><div><div class="s-kpi__label">Total cases</div><div class="s-kpi__value"><?php echo $total; ?></div></div></div>
    <div class="s-kpi"><div class="s-kpi__icon is-pending"><i class="fas fa-hourglass-half"></i></div><div><div class="s-kpi__label">Pending</div><div class="s-kpi__value"><?php echo $counts['pending']; ?></div></div></div>
    <div class="s-kpi"><div class="s-kpi__icon is-progress"><i class="fas fa-spinner"></i></div><div><div class="s-kpi__label">In progress</div><div class="s-kpi__value"><?php echo $counts['in-progress']; ?></div></div></div>
    <div class="s-kpi"><div class="s-kpi__icon is-resolved"><i class="fas fa-check-circle"></i></div><div><div class="s-kpi__label">Resolved</div><div class="s-kpi__value"><?php echo $counts['resolved']; ?></div></div></div>
</div>

<div class="s-row s-row--2-1">
    <div class="s-chart">
        <div class="s-card__header"><h3 class="s-card__title">Your activity (14 days)</h3></div>
        <canvas id="chartActivity"></canvas>
    </div>
    <div class="s-card">
        <div class="s-card__header">
            <h3 class="s-card__title">Recent complaints</h3>
            <a href="<?php echo htmlspecialchars(url('dashboard/my-complaints.php')); ?>" style="font-size:.85rem;">View all</a>
        </div>
        <?php if (count($recent) === 0): ?>
            <div class="s-empty">
                <p>You haven't filed a complaint yet.</p>
                <a class="s-btn s-btn--soft s-btn--sm" href="<?php echo htmlspecialchars(url('dashboard/file-complaint.php')); ?>">File your first complaint</a>
            </div>
        <?php else: foreach ($recent as $c): ?>
            <div style="padding: .8rem 0; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; gap: .5rem;">
                <div>
                    <div style="font-weight:600;"><?php echo htmlspecialchars(complaint_code_display($c['code'])); ?></div>
                    <div style="font-size:.8rem; color: var(--muted);"><?php echo htmlspecialchars($c['type']); ?> · <?php echo htmlspecialchars(time_ago($c['created_at'])); ?></div>
                </div>
                <span class="s-badge status-<?php echo htmlspecialchars($c['status']); ?>"><?php echo htmlspecialchars(status_label($c['status'])); ?></span>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<script>
new Chart(document.getElementById('chartActivity'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Complaints filed',
            data: <?php echo json_encode($values); ?>,
            borderColor: '#4E841F',
            backgroundColor: 'rgba(124, 207, 53, 0.18)',
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointBackgroundColor: '#4E841F'
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } }
        }
    }
});
</script>
<?php require_once dirname(__DIR__) . '/includes/dashboard_footer.php'; ?>
