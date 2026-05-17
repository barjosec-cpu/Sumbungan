<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, null, 'Method not allowed.', 405);
}

$user = current_user();
if ($user === null) {
    json_response(false, null, 'Unauthorized.', 401);
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    json_response(false, null, 'No file uploaded.', 400);
}

$f = $_FILES['file'];
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
$mime = mime_content_type($f['tmp_name']);
if (!isset($allowed[$mime])) {
    json_response(false, null, 'Invalid image type.', 400);
}

if ($f['size'] > 5 * 1024 * 1024) {
    json_response(false, null, 'File too large (max 5MB).', 400);
}

$ext = $allowed[$mime];
$dir = ROOT_PATH . '/assets/uploads/';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$name = 'c_' . bin2hex(random_bytes(8)) . '.' . $ext;
$dest = $dir . $name;
if (!move_uploaded_file($f['tmp_name'], $dest)) {
    json_response(false, null, 'Could not save file.', 500);
}

$webPath = 'uploads/' . $name;
json_response(true, ['path' => $webPath, 'url' => asset($webPath)]);
