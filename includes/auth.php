<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id' => (int) $_SESSION['user_id'],
        'full_name' => (string) ($_SESSION['full_name'] ?? ''),
        'email' => (string) ($_SESSION['email'] ?? ''),
        'role' => (string) ($_SESSION['role'] ?? ''),
        'profile_pic' => $_SESSION['profile_pic'] ?? null,
    ];
}

function require_login(): array
{
    $u = current_user();
    if ($u === null) {
        header('Location: ' . url('public/login.php'));
        exit;
    }
    return $u;
}

function require_role(string $role): array
{
    $u = require_login();
    if ($u['role'] !== $role) {
        if ($role === 'admin') {
            header('Location: ' . url('dashboard/index.php'));
        } else {
            header('Location: ' . url('admin/index.php'));
        }
        exit;
    }
    return $u;
}

function login_user(array $row): void
{
    $_SESSION['user_id'] = (int) $row['id'];
    $_SESSION['full_name'] = $row['full_name'];
    $_SESSION['email'] = $row['email'];
    $_SESSION['role'] = $row['role'];
    $_SESSION['profile_pic'] = $row['profile_pic'] ?? null;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
