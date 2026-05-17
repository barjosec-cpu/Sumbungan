<?php

declare(strict_types=1);

function user_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = strtoupper(mb_substr((string) ($parts[0] ?? ''), 0, 1));
    $second = '';
    if (count($parts) > 1) {
        $second = strtoupper(mb_substr((string) end($parts), 0, 1));
    }
    return $first . $second ?: '?';
}

function user_avatar_html(array $u, int $size = 36, string $extraClass = ''): string
{
    $cls = trim('s-avatar ' . $extraClass);
    $style = 'width:' . $size . 'px;height:' . $size . 'px;font-size:' . max(10, intdiv($size, 3)) . 'px;';
    if (!empty($u['profile_pic'])) {
        $src = htmlspecialchars(asset((string) $u['profile_pic']));
        return '<span class="' . $cls . '" style="' . $style . '"><img src="' . $src . '" alt=""></span>';
    }
    $initials = htmlspecialchars(user_initials((string) ($u['full_name'] ?? '')));
    return '<span class="' . $cls . '" style="' . $style . '">' . $initials . '</span>';
}

function unread_notifications_count(int $userId): int
{
    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    } catch (Throwable) {
        return 0;
    }
}

function recent_notifications(int $userId, int $limit = 6): array
{
    try {
        $stmt = db()->prepare('SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . max(1, $limit));
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function time_ago(string $datetime): string
{
    try {
        $then = new DateTimeImmutable($datetime);
        $now = new DateTimeImmutable('now');
    } catch (Throwable) {
        return $datetime;
    }
    $diff = $now->getTimestamp() - $then->getTimestamp();
    if ($diff <= 0) return 'just now';
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return intdiv($diff, 60) . 'm ago';
    if ($diff < 86400) return intdiv($diff, 3600) . 'h ago';
    if ($diff < 604800) return intdiv($diff, 86400) . 'd ago';
    return $then->format('M j, Y');
}
