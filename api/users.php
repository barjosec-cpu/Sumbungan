<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$user = current_user();
if ($user === null) {
    json_response(false, null, 'Unauthorized.', 401);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

try {
    $pdo = db();

    if ($method === 'GET' && $action === '') {
        if ($user['role'] !== 'admin') {
            json_response(false, null, 'Forbidden.', 403);
        }
        $stmt = $pdo->query("SELECT id, full_name, email, address, created_at FROM users WHERE role = 'complainant' ORDER BY created_at DESC");
        json_response(true, ['items' => $stmt->fetchAll()]);
    }

    if ($method === 'POST' && $action === 'profile') {
        $fullName = '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'multipart/form-data')) {
            $fullName = sanitize_string($_POST['full_name'] ?? '');
        } else {
            $body = read_json_body();
            $fullName = sanitize_string($body['full_name'] ?? '');
        }

        if ($fullName !== '') {
            $pdo->prepare('UPDATE users SET full_name = ? WHERE id = ?')->execute([$fullName, $user['id']]);
            $_SESSION['full_name'] = $fullName;
        }

        $picPath = null;
        if (!empty($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $f = $_FILES['profile_pic'];
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime = mime_content_type($f['tmp_name']);
            if (isset($allowed[$mime]) && $f['size'] <= 2 * 1024 * 1024) {
                $ext = $allowed[$mime];
                $dir = ROOT_PATH . '/assets/uploads/';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $name = 'p_' . $user['id'] . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($f['tmp_name'], $dir . $name)) {
                    $picPath = 'uploads/' . $name;
                    $pdo->prepare('UPDATE users SET profile_pic = ? WHERE id = ?')->execute([$picPath, $user['id']]);
                    $_SESSION['profile_pic'] = $picPath;
                }
            }
        }

        $stmt = $pdo->prepare('SELECT id, full_name, email, role, profile_pic FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        json_response(true, $stmt->fetch());
    }

    json_response(false, null, 'Method not allowed.', 405);
} catch (Throwable $e) {
    json_response(false, null, 'Server error.', 500);
}
