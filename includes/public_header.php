<?php

declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/includes/bootstrap.php';
}

$pageTitle = $pageTitle ?? 'Sumbungan Brgy.';
$bodyClass = $bodyClass ?? 'sumbungan-public';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="Sumbungan — modern barangay complaint management for Philippine communities.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset('css/sumbungan.css')); ?>">
</head>
<body class="<?php echo htmlspecialchars($bodyClass); ?>">
