<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Analytics';
$pageSubtitle = 'Real-time complaint metrics for Barangay Sanroque.';
$activeNav = 'analytics';
$pageActions = '<a class="btn btn-primary" href="' . htmlspecialchars(url('admin/cases.php')) . '"><i class="fas fa-tasks mr-1"></i> Manage cases</a>';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$apiBase = htmlspecialchars(url('api/analytics.php'), ENT_QUOTES, 'UTF-8');
?>
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner"><h3 id="kpiTotal">—</h3><p>Total cases</p></div>
            <div class="icon"><i class="fas fa-folder-open"></i></div>
            <a href="<?php echo htmlspecialchars(url('admin/cases.php')); ?>" class="small-box-footer">View all <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner"><h3 id="kpiPending">—</h3><p>Pending</p></div>
            <div class="icon"><i class="fas fa-hourglass-half"></i></div>
            <a href="<?php echo htmlspecialchars(url('admin/cases.php')); ?>?status=pending" class="small-box-footer">Review now <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner"><h3 id="kpiProgress">—</h3><p>In progress</p></div>
            <div class="icon"><i class="fas fa-spinner"></i></div>
            <a href="<?php echo htmlspecialchars(url('admin/cases.php')); ?>?status=in-progress" class="small-box-footer">Open <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner"><h3 id="kpiResolved">—</h3><p>Resolved</p></div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <a href="<?php echo htmlspecialchars(url('admin/cases.php')); ?>?status=resolved" class="small-box-footer">Browse <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="info-box">
            <span class="info-box-icon bg-secondary"><i class="fas fa-percentage"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Resolution rate</span>
                <span class="info-box-number" id="kpiResolution">—</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-user-friends"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Registered residents</span>
                <span class="info-box-number" id="kpiResidents">—</span>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="info-box">
            <span class="info-box-icon bg-secondary"><i class="far fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Avg time to resolve</span>
                <span class="info-box-number" >24h</span>
            </div>
        </div>  
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-ban"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Rejected / cancelled</span>
                <span class="info-box-number" id="kpiRejected">—</span>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Complaints (last 14 days)</h3></div>
            <div class="card-body"><canvas id="chartDaily" style="max-height: 280px;"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">By type</h3></div>
            <div class="card-body"><canvas id="chartType" style="max-height: 280px;"></canvas></div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Status by week</h3></div>
            <div class="card-body"><canvas id="chartStatus" style="max-height: 280px;"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Top locations</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="topLocations"><li class="list-group-item text-muted">Loading...</li></ul>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Recent cases</h3>
                <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars(url('admin/cases.php')); ?>">View all</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Code</th><th>Type</th><th>Complainant</th><th>Status</th><th>Filed</th><th></th></tr></thead>
                    <tbody id="recentCases"><tr><td colspan="6" class="text-muted text-center p-4">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
<script>
(function () {
    'use strict';
    const api = '<?php echo $apiBase; ?>';
    const palette = ['#4E841F', '#7CCF35', '#92D756', '#385F16', '#AAE07B', '#64A928'];

    function jget(metric) {
        return fetch(api + '?metric=' + encodeURIComponent(metric), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (j) { if (!j.ok) throw new Error(j.error); return j.data; });
    }

    jget('summary').then(function (s) {
        document.getElementById('kpiTotal').textContent = s.total;
        document.getElementById('kpiPending').textContent = s.pending;
        document.getElementById('kpiProgress').textContent = s.in_progress;
        document.getElementById('kpiResolved').textContent = s.resolved;
        document.getElementById('kpiResolution').textContent = s.resolution_percent + '%';
        document.getElementById('kpiResidents').textContent = s.residents_count;
        document.getElementById('kpiAvgResolve').textContent = (s.avg_resolution_hours != null ? s.avg_resolution_hours + ' hr' : 'n/a');
        document.getElementById('kpiRejected').textContent = s.rejected;
    }).catch(function () {});

    jget('daily').then(function (d) {
        new Chart(document.getElementById('chartDaily'), {
            type: 'line',
            data: { labels: d.labels, datasets: [{
                label: 'New complaints', data: d.values,
                borderColor: '#4E841F', backgroundColor: 'rgba(124, 207, 53, 0.18)',
                fill: true, tension: 0.3, pointBackgroundColor: '#4E841F'
            }]},
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
        });
    }).catch(function () {});

    jget('by_type').then(function (bt) {
        const items = bt.items || [];
        new Chart(document.getElementById('chartType'), {
            type: 'doughnut',
            data: { labels: items.map(function (x) { return x.type; }),
                datasets: [{ data: items.map(function (x) { return parseInt(x.c, 10); }), backgroundColor: palette, borderWidth: 0 }] },
            options: { plugins: { legend: { position: 'bottom' } }, cutout: '65%' }
        });
    }).catch(function () {});

    jget('by_status').then(function (bs) {
        new Chart(document.getElementById('chartStatus'), {
            type: 'bar',
            data: {
                labels: bs.labels,
                datasets: [
                    { label: 'Pending',     data: bs.pending,     backgroundColor: '#c0392b' },
                    { label: 'In progress', data: bs.in_progress, backgroundColor: '#d8a13a' },
                    { label: 'Resolved',    data: bs.resolved,    backgroundColor: '#4E841F' },
                    { label: 'Rejected',    data: bs.rejected,    backgroundColor: '#7d8270' }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } },
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }).catch(function () {});

    jget('top_locations').then(function (tl) {
        const ul = document.getElementById('topLocations');
        ul.innerHTML = '';
        const items = tl.items || [];
        if (items.length === 0) { ul.innerHTML = '<li class="list-group-item text-muted">No data yet.</li>'; return; }
        items.forEach(function (row) {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.innerHTML = '<span><i class="fas fa-map-marker-alt mr-1" style="color:#7CCF35;"></i>' + row.location.replace(/</g, '&lt;') + '</span><span class="badge badge-success">' + row.c + '</span>';
            ul.appendChild(li);
        });
    }).catch(function () {});

    jget('recent').then(function (rc) {
        const tb = document.getElementById('recentCases');
        tb.innerHTML = '';
        const items = rc.items || [];
        if (items.length === 0) { tb.innerHTML = '<tr><td colspan="6" class="text-muted text-center p-4">No recent cases.</td></tr>'; return; }
        const baseUrl = '<?php echo htmlspecialchars(url('admin/case-view.php'), ENT_QUOTES, 'UTF-8'); ?>';
        items.forEach(function (row) {
            const tr = document.createElement('tr');
            const status = row.status;
            const cls = status === 'resolved' ? 'success' : (status === 'pending' ? 'danger' : (status === 'in-progress' ? 'warning' : 'secondary'));
            tr.innerHTML = '<td><strong>#' + row.code + '</strong></td>' +
                '<td>' + row.type + '</td>' +
                '<td>' + (row.complainant_name || '') + '</td>' +
                '<td><span class="badge badge-' + cls + '">' + status + '</span></td>' +
                '<td>' + row.created_at + '</td>' +
                '<td><a class="btn btn-sm btn-primary" href="' + baseUrl + '?id=' + encodeURIComponent(row.code) + '">Manage</a></td>';
            tb.appendChild(tr);
        });
    }).catch(function () {});
})();
</script>
<?php require_once dirname(__DIR__) . '/includes/admin_layout_end.php'; ?>
