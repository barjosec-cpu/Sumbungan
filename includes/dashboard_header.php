<?php

declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/includes/bootstrap.php';
}

$u = require_login();
if ($u['role'] === 'admin') {
    header('Location: ' . url('admin/index.php'));
    exit;
}

$pageTitle = $pageTitle ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? '';
$activeNav = $activeNav ?? '';
$pageActions = $pageActions ?? '';

$unread = unread_notifications_count((int) $u['id']);
$recentNotifs = recent_notifications((int) $u['id'], 6);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | Sumbungan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset('css/sumbungan.css')); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset('css/app.css')); ?>">
</head>
<body class="s-app">
<div class="s-shell">
    <aside class="s-sidebar">
        <a class="s-sidebar__brand" href="<?php echo htmlspecialchars(url('dashboard/index.php')); ?>">
            <img src="<?php echo htmlspecialchars(asset('img/Logo.png')); ?>" alt="Barangay San Roque Logo"> Sumbungan
        </a>
        <div class="s-sidebar__section">Main</div>
        <a class="s-nav-link <?php echo $activeNav === 'overview' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(url('dashboard/index.php')); ?>"><i class="fas fa-house"></i> Overview</a>
        <a class="s-nav-link <?php echo $activeNav === 'file' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(url('dashboard/file-complaint.php')); ?>"><i class="fas fa-file-signature"></i> File a complaint</a>
        <a class="s-nav-link <?php echo $activeNav === 'list' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(url('dashboard/my-complaints.php')); ?>"><i class="fas fa-list-ul"></i> My complaints</a>
        <a class="s-nav-link <?php echo $activeNav === 'track' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(url('dashboard/track.php')); ?>"><i class="fas fa-location-arrow"></i> Tracking</a>

        <div class="s-sidebar__section">Account</div>
        <a class="s-nav-link <?php echo $activeNav === 'notifications' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(url('dashboard/notifications.php')); ?>"><i class="fas fa-bell"></i> Notifications<?php if ($unread > 0): ?> <span style="margin-left:auto; background: var(--danger); color:#fff; border-radius:999px; padding:.05rem .45rem; font-size:.7rem;"><?php echo $unread; ?></span><?php endif; ?></a>
        <a class="s-nav-link <?php echo $activeNav === 'profile' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(url('dashboard/profile.php')); ?>"><i class="fas fa-user"></i> Profile</a>

        <div class="s-sidebar__footer">
            <a class="s-nav-link" href="<?php echo htmlspecialchars(url('public/logout.php')); ?>"><i class="fas fa-right-from-bracket"></i> Sign out</a>
        </div>
    </aside>

    <main class="s-main">
        <header class="s-topbar">
            <div class="s-topbar__title">
                <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                <?php if ($pageSubtitle !== ''): ?><small><?php echo htmlspecialchars($pageSubtitle); ?></small><?php endif; ?>
            </div>
            <div class="s-topbar__right">
                <?php if ($pageActions !== ''): ?><div class="s-page-actions"><?php echo $pageActions; ?></div><?php endif; ?>

                <div style="position: relative;">
                    <button class="s-iconbtn" type="button" data-toggle="dropdown-s" data-target="#notifDropdown" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread > 0): ?><span class="s-iconbtn__dot"><?php echo $unread > 9 ? '9+' : $unread; ?></span><?php endif; ?>
                    </button>
                    <div id="notifDropdown" class="s-dropdown s-dropdown--notif">
                        <div class="s-dropdown__header">
                            <span>Notifications</span>
                            <a href="<?php echo htmlspecialchars(url('dashboard/notifications.php')); ?>" style="font-size:.8rem;">View all</a>
                        </div>
                        <div class="s-notif-list">
                            <?php if (count($recentNotifs) === 0): ?>
                                <div class="s-notif--empty">No notifications yet.</div>
                            <?php else: foreach ($recentNotifs as $n): ?>
                                <div class="s-notif <?php echo $n['is_read'] ? '' : 'unread'; ?>">
                                    <p><?php echo htmlspecialchars($n['message']); ?></p>
                                    <small><?php echo htmlspecialchars(time_ago($n['created_at'])); ?></small>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>

                <div style="position: relative;">
                    <div class="s-user" data-toggle="dropdown-s" data-target="#userDropdown">
                        <?php echo user_avatar_html($u, 36); ?>
                        <div>
                            <div class="s-user__name"><?php echo htmlspecialchars($u['full_name']); ?></div>
                            <div class="s-user__role">Resident</div>
                        </div>
                        <i class="fas fa-chevron-down" style="font-size:.7rem; color: var(--muted);"></i>
                    </div>
                    <div id="userDropdown" class="s-dropdown">
                        <div class="s-dropdown__header"><?php echo htmlspecialchars($u['full_name']); ?></div>
                        <a class="s-dropdown__item" href="<?php echo htmlspecialchars(url('dashboard/profile.php')); ?>"><i class="fas fa-user"></i> Profile</a>
                        <a class="s-dropdown__item" href="<?php echo htmlspecialchars(url('dashboard/notifications.php')); ?>"><i class="fas fa-bell"></i> Notifications</a>
                        <a class="s-dropdown__item" href="<?php echo htmlspecialchars(url('dashboard/file-complaint.php')); ?>"><i class="fas fa-file-signature"></i> File a complaint</a>
                        <div class="s-dropdown__divider"></div>
                        <a class="s-dropdown__item" href="<?php echo htmlspecialchars(url('public/logout.php')); ?>"><i class="fas fa-right-from-bracket"></i> Sign out</a>
                    </div>
                </div>
            </div>
        </header>

        <section class="s-content">
            <?php echo render_flashes(); ?>
