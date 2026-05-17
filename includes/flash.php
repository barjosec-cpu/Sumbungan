<?php

declare(strict_types=1);

function flash_set(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
        $_SESSION['_flash'] = [];
    }
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flash_take(): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $msgs = $_SESSION['_flash'] ?? [];
    $_SESSION['_flash'] = [];
    return is_array($msgs) ? $msgs : [];
}

function render_flashes(): string
{
    $out = '';
    foreach (flash_take() as $f) {
        $type = $f['type'] ?? 'info';
        $cls = match ($type) {
            'success' => 's-alert--success',
            'error', 'danger' => 's-alert--error',
            'warning' => 's-alert--warn',
            default => 's-alert--info',
        };
        $icon = match ($type) {
            'success' => 'fa-circle-check',
            'error', 'danger' => 'fa-circle-exclamation',
            'warning' => 'fa-triangle-exclamation',
            default => 'fa-circle-info',
        };
        $out .= '<div class="s-alert ' . $cls . '"><i class="fas ' . $icon . '"></i><span>' . htmlspecialchars((string) ($f['message'] ?? '')) . '</span></div>';
    }
    return $out;
}
