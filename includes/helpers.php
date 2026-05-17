<?php

declare(strict_types=1);

function json_response(bool $ok, mixed $data = null, ?string $error = null, int $httpCode = 200): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => $ok,
        'data' => $data,
        'error' => $error,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function sanitize_string(?string $s): string
{
    return trim((string) $s);
}

function complaint_code_display(string $code): string
{
    $code = ltrim($code, '#');
    return str_starts_with($code, 'BRY-') ? '#' . $code : '#' . $code;
}

function normalize_complaint_id(string $id): string
{
    $id = trim($id);
    $id = ltrim($id, '#');
    return $id;
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return [];
    }
    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        return is_array($decoded) ? $decoded : [];
    } catch (JsonException) {
        return [];
    }
}

function status_badge_class(string $status): string
{
    return match ($status) {
        'resolved' => 'success',
        'in-progress' => 'warning',
        'pending' => 'danger',
        'rejected' => 'secondary',
        default => 'info',
    };
}

function status_label(string $status): string
{
    return match ($status) {
        'in-progress' => 'In Progress',
        default => ucfirst($status),
    };
}

function complaint_type_label(string $type): string
{
    return match ($type) {
        'Noise' => 'Noisy neighbors / disturbance',
        'Sanitation' => 'Waste / sanitation',
        'Security' => 'Security / safety',
        'Traffic' => 'Traffic / parking',
        default => $type,
    };
}

function add_notification(PDO $pdo, int $userId, string $message): void
{
    try {
        $pdo->prepare('INSERT INTO notifications (user_id, message) VALUES (?,?)')->execute([$userId, $message]);
    } catch (Throwable) {
    }
}
