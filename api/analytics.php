<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$user = current_user();
if ($user === null || $user['role'] !== 'admin') {
    json_response(false, null, 'Forbidden.', 403);
}

$metric = $_GET['metric'] ?? 'summary';

try {
    $pdo = db();

    if ($metric === 'summary') {
        $totals = $pdo->query("SELECT status, COUNT(*) AS c FROM complaints GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
        $total = (int) $pdo->query('SELECT COUNT(*) FROM complaints')->fetchColumn();
        $resolved = (int) ($totals['resolved'] ?? 0);
        $pending = (int) ($totals['pending'] ?? 0);
        $inProgress = (int) ($totals['in-progress'] ?? 0);
        $rejected = (int) ($totals['rejected'] ?? 0);
        $resolutionPct = $total > 0 ? round(100 * $resolved / $total, 1) : 0.0;
        $residents = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'complainant'")->fetchColumn();

        $avg = $pdo->query("
            SELECT AVG(TIMESTAMPDIFF(HOUR, c.created_at, rt.resolved_at))
            FROM complaints c
            INNER JOIN (
                SELECT
                    complaint_id,
                    MIN(created_at) AS resolved_at
                FROM complaint_timeline
                WHERE LOWER(status_label) LIKE '%resolved%'
                GROUP BY complaint_id
            ) rt ON rt.complaint_id = c.id
            WHERE c.status = 'resolved' AND rt.resolved_at >= c.created_at
        ")->fetchColumn();
        $avgHours = $avg !== false && $avg !== null ? round((float) $avg, 1) : null;

        json_response(true, [
            'total' => $total,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'resolved' => $resolved,
            'rejected' => $rejected,
            'resolution_percent' => $resolutionPct,
            'residents_count' => $residents,
            'avg_resolution_hours' => $avgHours,
        ]);
    }

    if ($metric === 'daily') {
        $stmt = $pdo->query("
            SELECT DATE(created_at) AS d, COUNT(*) AS c
            FROM complaints
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
            GROUP BY DATE(created_at)
            ORDER BY d ASC
        ");
        $rows = $stmt->fetchAll();
        $map = [];
        foreach ($rows as $r) {
            $map[$r['d']] = (int) $r['c'];
        }
        $labels = [];
        $values = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = (new DateTimeImmutable())->modify("-{$i} days")->format('Y-m-d');
            $labels[] = $d;
            $values[] = $map[$d] ?? 0;
        }
        json_response(true, ['labels' => $labels, 'values' => $values]);
    }

    if ($metric === 'by_type') {
        $stmt = $pdo->query('SELECT type, COUNT(*) AS c FROM complaints GROUP BY type ORDER BY c DESC');
        $rows = $stmt->fetchAll();
        json_response(true, ['items' => $rows]);
    }

    if ($metric === 'by_status') {
        $stmt = $pdo->query("
            SELECT
                YEARWEEK(created_at, 1) AS yw,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) AS in_progress,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected
            FROM complaints
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 56 DAY)
            GROUP BY yw
            ORDER BY yw ASC
        ");
        $rows = $stmt->fetchAll();
        $labels = [];
        $pending = [];
        $inProgress = [];
        $resolved = [];
        $rejected = [];
        foreach ($rows as $r) {
            $labels[] = 'W' . (string) $r['yw'];
            $pending[] = (int) $r['pending'];
            $inProgress[] = (int) $r['in_progress'];
            $resolved[] = (int) $r['resolved'];
            $rejected[] = (int) $r['rejected'];
        }
        json_response(true, [
            'labels' => $labels,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'resolved' => $resolved,
            'rejected' => $rejected,
        ]);
    }

    if ($metric === 'top_locations') {
        $stmt = $pdo->query('SELECT location, COUNT(*) AS c FROM complaints GROUP BY location ORDER BY c DESC LIMIT 8');
        json_response(true, ['items' => $stmt->fetchAll()]);
    }

    if ($metric === 'recent') {
        $stmt = $pdo->query("
            SELECT c.code, c.type, c.status, c.created_at, u.full_name AS complainant_name
            FROM complaints c
            LEFT JOIN users u ON u.id = c.complainant_id
            ORDER BY c.created_at DESC
            LIMIT 5
        ");
        json_response(true, ['items' => $stmt->fetchAll()]);
    }

    json_response(false, null, 'Unknown metric.', 400);
} catch (Throwable $e) {
    json_response(false, null, 'Server error.', 500);
}
