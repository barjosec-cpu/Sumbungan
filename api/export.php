<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$user = current_user();
if ($user === null || $user['role'] !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}

$type = $_GET['type'] ?? 'cases';
$pdo = db();

header('Content-Type: text/csv; charset=utf-8');
$filename = 'sumbungan_' . $type . '_' . date('Ymd_His') . '.csv';
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

if ($type === 'cases') {
    $status = sanitize_string($_GET['status'] ?? '');
    $kind = sanitize_string($_GET['type'] ?? '');
    $q = sanitize_string($_GET['q'] ?? '');

    $sql = 'SELECT c.code, c.type, c.status, c.location, c.description, c.created_at, c.updated_at, u.full_name AS complainant, u.email
        FROM complaints c LEFT JOIN users u ON u.id = c.complainant_id WHERE 1=1';
    $params = [];
    if (in_array($status, ['pending', 'in-progress', 'resolved', 'rejected'], true)) {
        $sql .= ' AND c.status = ?';
        $params[] = $status;
    }
    if (in_array($kind, ['Noise', 'Sanitation', 'Security', 'Traffic', 'Other'], true)) {
        $sql .= ' AND c.type = ?';
        $params[] = $kind;
    }
    if ($q !== '') {
        $sql .= ' AND (c.code LIKE ? OR c.location LIKE ? OR u.full_name LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= ' ORDER BY c.created_at DESC';

    fputcsv($out, ['Code', 'Type', 'Status', 'Location', 'Description', 'Filed', 'Updated', 'Complainant', 'Email']);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch()) {
        fputcsv($out, [
            $row['code'],
            $row['type'],
            $row['status'],
            $row['location'],
            $row['description'],
            $row['created_at'],
            $row['updated_at'],
            $row['complainant'] ?? '',
            $row['email'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

if ($type === 'users') {
    fputcsv($out, ['Name', 'Email', 'Role', 'Address', 'Joined', 'Total cases']);
    $stmt = $pdo->query("SELECT u.full_name, u.email, u.role, u.address, u.created_at,
        (SELECT COUNT(*) FROM complaints c WHERE c.complainant_id = u.id) AS total_cases
        FROM users u ORDER BY u.created_at DESC");
    while ($row = $stmt->fetch()) {
        fputcsv($out, [
            $row['full_name'],
            $row['email'],
            $row['role'],
            $row['address'] ?? '',
            $row['created_at'],
            (int) $row['total_cases'],
        ]);
    }
    fclose($out);
    exit;
}

fclose($out);
http_response_code(400);
exit('Unknown export type.');
