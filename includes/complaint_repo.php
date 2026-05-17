<?php

declare(strict_types=1);

function complaint_repo_create(PDO $pdo, int $complainantId, string $type, string $location, string $description, ?string $photoPath): array
{
    $allowedTypes = ['Noise', 'Sanitation', 'Security', 'Traffic', 'Other'];
    if (!in_array($type, $allowedTypes, true)) {
        throw new InvalidArgumentException('Invalid type');
    }
    $tempCode = 'NEW' . bin2hex(random_bytes(6));
    $stmt = $pdo->prepare('INSERT INTO complaints (code, complainant_id, type, location, description, photo_path, status) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$tempCode, $complainantId, $type, $location, $description, $photoPath, 'pending']);
    $newId = (int) $pdo->lastInsertId();
    $finalCode = 'BRY-' . str_pad((string) $newId, 4, '0', STR_PAD_LEFT);
    $pdo->prepare('UPDATE complaints SET code = ? WHERE id = ?')->execute([$finalCode, $newId]);
    $pdo->prepare('INSERT INTO complaint_timeline (complaint_id, status_label, details) VALUES (?,?,?)')
        ->execute([$newId, 'Report Received', 'Complaint filed online.']);
    $stmt = $pdo->prepare('SELECT * FROM complaints WHERE id = ?');
    $stmt->execute([$newId]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new RuntimeException('Insert failed');
    }
    return $row;
}
