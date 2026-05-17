<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$user = current_user();
if ($user === null) {
    json_response(false, null, 'Unauthorized.', 401);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'POST' && (($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '') === 'PATCH' || ($_POST['_method'] ?? '') === 'PATCH')) {
    $method = 'PATCH';
}

function complaint_row(PDO $pdo, array $c, bool $withComplainant): array
{
    $out = [
        'id' => (int) $c['id'],
        'code' => $c['code'],
        'code_display' => complaint_code_display($c['code']),
        'type' => $c['type'],
        'location' => $c['location'],
        'description' => $c['description'],
        'photo_path' => $c['photo_path'],
        'status' => $c['status'],
        'created_at' => $c['created_at'],
        'updated_at' => $c['updated_at'],
    ];
    if ($withComplainant) {
        $out['complainant_id'] = (int) $c['complainant_id'];
        $out['complainant_name'] = $c['complainant_name'] ?? null;
    }
    return $out;
}

function fetch_timeline(PDO $pdo, int $complaintId): array
{
    $stmt = $pdo->prepare('SELECT id, status_label, details, created_at FROM complaint_timeline WHERE complaint_id = ? ORDER BY created_at ASC');
    $stmt->execute([$complaintId]);
    return $stmt->fetchAll();
}

try {
    $pdo = db();

    if ($method === 'GET') {
        $idParam = $_GET['id'] ?? null;
        if ($idParam !== null && $idParam !== '') {
            $code = normalize_complaint_id((string) $idParam);
            $sql = 'SELECT c.*';
            if ($user['role'] === 'admin') {
                $sql .= ', u.full_name AS complainant_name';
            }
            $sql .= ' FROM complaints c';
            if ($user['role'] === 'admin') {
                $sql .= ' LEFT JOIN users u ON u.id = c.complainant_id';
            }
            $sql .= ' WHERE c.code = ? LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code]);
            $row = $stmt->fetch();
            if (!$row) {
                json_response(false, null, 'Complaint not found.', 404);
            }
            if ($user['role'] !== 'admin' && (int) $row['complainant_id'] !== $user['id']) {
                json_response(false, null, 'Forbidden.', 403);
            }
            $detail = complaint_row($pdo, $row, $user['role'] === 'admin');
            $detail['timeline'] = fetch_timeline($pdo, (int) $row['id']);
            json_response(true, $detail);
        }

        $status = sanitize_string($_GET['status'] ?? '');
        $type = sanitize_string($_GET['type'] ?? '');
        $q = sanitize_string($_GET['q'] ?? '');

        if ($user['role'] === 'admin') {
            $sql = 'SELECT c.*, u.full_name AS complainant_name FROM complaints c LEFT JOIN users u ON u.id = c.complainant_id WHERE 1=1';
            $params = [];
            if ($status !== '' && in_array($status, ['pending', 'in-progress', 'resolved', 'rejected'], true)) {
                $sql .= ' AND c.status = ?';
                $params[] = $status;
            }
            if ($type !== '' && in_array($type, ['Noise', 'Sanitation', 'Security', 'Traffic', 'Other'], true)) {
                $sql .= ' AND c.type = ?';
                $params[] = $type;
            }
            if ($q !== '') {
                $sql .= ' AND (c.code LIKE ? OR c.location LIKE ? OR u.full_name LIKE ?)';
                $like = '%' . $q . '%';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
            $sql .= ' ORDER BY c.created_at DESC LIMIT 200';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            $sql = 'SELECT c.* FROM complaints c WHERE c.complainant_id = ?';
            $params = [$user['id']];
            if ($status !== '' && in_array($status, ['pending', 'in-progress', 'resolved', 'rejected'], true)) {
                $sql .= ' AND c.status = ?';
                $params[] = $status;
            }
            $sql .= ' ORDER BY c.created_at DESC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
        $rows = $stmt->fetchAll();
        $list = array_map(fn ($r) => complaint_row($pdo, $r, $user['role'] === 'admin'), $rows);
        json_response(true, ['items' => $list]);
    }

    if ($method === 'POST') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $type = '';
        $location = '';
        $description = '';
        $photoPath = null;

        if (str_contains($contentType, 'multipart/form-data')) {
            $type = sanitize_string($_POST['type'] ?? '');
            $location = sanitize_string($_POST['location'] ?? '');
            $description = sanitize_string($_POST['description'] ?? '');
            $photoPath = sanitize_string($_POST['photo_path'] ?? '') ?: null;
            if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $f = $_FILES['photo'];
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
                $mime = mime_content_type($f['tmp_name']);
                if (isset($allowed[$mime]) && $f['size'] <= 5 * 1024 * 1024) {
                    $ext = $allowed[$mime];
                    $dir = ROOT_PATH . '/assets/uploads/';
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $name = 'c_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    if (move_uploaded_file($f['tmp_name'], $dir . $name)) {
                        $photoPath = 'uploads/' . $name;
                    }
                }
            }
        } else {
            $body = read_json_body();
            $type = sanitize_string($body['type'] ?? '');
            $location = sanitize_string($body['location'] ?? '');
            $description = sanitize_string($body['description'] ?? '');
            $photoPath = isset($body['photo_path']) ? sanitize_string((string) $body['photo_path']) : null;
            if ($photoPath === '') {
                $photoPath = null;
            }
        }

        $allowedTypes = ['Noise', 'Sanitation', 'Security', 'Traffic', 'Other'];
        if (!in_array($type, $allowedTypes, true) || $location === '' || $description === '') {
            json_response(false, null, 'Valid type, location, and description are required.', 400);
        }

        try {
            $row = complaint_repo_create($pdo, $user['id'], $type, $location, $description, $photoPath);
        } catch (InvalidArgumentException) {
            json_response(false, null, 'Invalid complaint type.', 400);
        }
        json_response(true, complaint_row($pdo, $row, false), null, 201);
    }

    if ($method === 'PATCH') {
        if ($user['role'] !== 'admin') {
            json_response(false, null, 'Forbidden.', 403);
        }
        $code = normalize_complaint_id((string) ($_GET['id'] ?? ''));
        if ($code === '') {
            json_response(false, null, 'Complaint id required.', 400);
        }
        $body = read_json_body();
        if ($body === []) {
            $body = $_POST;
        }
        $stmt = $pdo->prepare('SELECT * FROM complaints WHERE code = ? LIMIT 1');
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        if (!$row) {
            json_response(false, null, 'Not found.', 404);
        }
        $cid = (int) $row['id'];

        $newStatus = $body['status'] ?? null;
        $timelineLabel = sanitize_string($body['timeline_label'] ?? '');
        $timelineDetails = sanitize_string($body['timeline_details'] ?? '');

        if ($newStatus !== null && in_array((string) $newStatus, ['pending', 'in-progress', 'resolved', 'rejected'], true)) {
            $pdo->prepare('UPDATE complaints SET status = ? WHERE id = ?')->execute([(string) $newStatus, $cid]);
        }
        if ($timelineLabel !== '') {
            $pdo->prepare('INSERT INTO complaint_timeline (complaint_id, status_label, details) VALUES (?,?,?)')
                ->execute([$cid, $timelineLabel, $timelineDetails ?: null]);
        }

        $stmt = $pdo->prepare('SELECT c.*, u.full_name AS complainant_name FROM complaints c LEFT JOIN users u ON u.id = c.complainant_id WHERE c.id = ?');
        $stmt->execute([$cid]);
        $fresh = $stmt->fetch();
        $out = complaint_row($pdo, $fresh, true);
        $out['timeline'] = fetch_timeline($pdo, $cid);
        json_response(true, $out);
    }

    json_response(false, null, 'Method not allowed.', 405);
} catch (Throwable $e) {
    json_response(false, null, 'Server error.', 500);
}
