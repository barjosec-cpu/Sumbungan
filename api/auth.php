<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$postBody = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ctype = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($ctype, 'application/json')) {
        $postBody = read_json_body();
    } else {
        $postBody = $_POST;
    }
}

$action = (string) ($_GET['action'] ?? $postBody['action'] ?? $_POST['action'] ?? '');
if ($action === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($postBody['email'], $postBody['password']) && !isset($postBody['full_name'])) {
        $action = 'login';
    } elseif (isset($postBody['email'], $postBody['password'], $postBody['full_name'])) {
        $action = 'register';
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
        $email = sanitize_string($postBody['email'] ?? '');
        $password = (string) ($postBody['password'] ?? '');
        if ($email === '' || $password === '') {
            json_response(false, null, 'Email and password are required.', 400);
        }
        $stmt = db()->prepare('SELECT id, full_name, email, password_hash, role, profile_pic FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($password, $row['password_hash'])) {
            json_response(false, null, 'Invalid email or password.', 401);
        }
        login_user($row);
        json_response(true, [
            'user' => [
                'id' => (int) $row['id'],
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'role' => $row['role'],
            ],
            'role' => $row['role'],
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register') {
        $fullName = sanitize_string($postBody['full_name'] ?? '');
        $email = sanitize_string($postBody['email'] ?? '');
        $password = (string) ($postBody['password'] ?? '');
        $address = sanitize_string($postBody['address'] ?? '');
        if ($fullName === '' || $email === '' || $password === '' || strlen($password) < 8) {
            json_response(false, null, 'Full name, email, and password (min 8 chars) are required.', 400);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = db()->prepare('INSERT INTO users (full_name, email, password_hash, role, address) VALUES (?,?,?,?,?)');
            $stmt->execute([$fullName, $email, $hash, 'complainant', $address ?: null]);
            $id = (int) db()->lastInsertId();
            $stmt = db()->prepare('SELECT id, full_name, email, password_hash, role, profile_pic FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            login_user($row);
            json_response(true, ['user' => ['id' => $id, 'full_name' => $fullName, 'email' => $email, 'role' => 'complainant']]);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate')) {
                json_response(false, null, 'Email already registered.', 409);
            }
            throw $e;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'logout') {
        logout_user();
        json_response(true, ['logged_out' => true]);
    }

    json_response(false, null, 'Unknown action or method.', 405);
} catch (Throwable $e) {
    json_response(false, null, 'Server error.', 500);
}
