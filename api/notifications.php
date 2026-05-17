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
        $stmt = $pdo->prepare('SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
        $stmt->execute([$user['id']]);
        json_response(true, ['items' => $stmt->fetchAll()]);
    }

    if ($method === 'POST' && $action === 'read') {
        $body = read_json_body();
        if ($body === []) {
            $body = $_POST;
        }
        $id = isset($body['id']) ? (int) $body['id'] : 0;
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $user['id']]);
        } else {
            $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$user['id']]);
        }
        json_response(true, ['ok' => true]);
    }

    json_response(false, null, 'Method not allowed.', 405);
} catch (Throwable $e) {
    json_response(false, null, 'Server error.', 500);
}
