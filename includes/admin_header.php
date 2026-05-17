<?php

declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/includes/bootstrap.php';
}

$u = require_role('admin');
$pageTitle = $pageTitle ?? 'Admin';
$pageSubtitle = $pageSubtitle ?? '';
$activeNav = $activeNav ?? '';

$unread = unread_notifications_count((int) $u['id']);
$recentNotifs = recent_notifications((int) $u['id'], 6);
$initials = htmlspecialchars(user_initials((string) $u['full_name']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?> | Sumbungan Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset('css/admin.css')); ?>">
</head>
<body class="hold-transition sidebar-mini layout-fixed admin-theme">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="nav-link" style="font-weight: 600; color: var(--brand-deep);"><?php echo htmlspecialchars($pageTitle); ?></span>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto" style="position: relative;">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo htmlspecialchars(url('public/index.php')); ?>" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> View site</a>
            </li>
            <li class="nav-item" style="position: relative;">
                <a class="nav-link" href="#" data-toggle="dropdown-s" data-target="#adminNotifDropdown">
                    <i class="far fa-bell"></i>
                    <?php if ($unread > 0): ?>
                        <span class="badge badge-danger navbar-badge"><?php echo $unread > 9 ? '9+' : $unread; ?></span>
                    <?php endif; ?>
                </a>
                <div id="adminNotifDropdown" class="s-dropdown s-dropdown--notif" style="right: 1rem; top: 56px;">
                    <div class="s-dropdown__header">
                        <span>Notifications</span>
                        <a href="<?php echo htmlspecialchars(url('admin/notifications.php')); ?>" style="font-size:.8rem;">View all</a>
                    </div>
                    <div class="s-notif-list">
                        <?php if (count($recentNotifs) === 0): ?>
                            <div class="s-notif--empty">No notifications.</div>
                        <?php else: foreach ($recentNotifs as $n): ?>
                            <div class="s-notif <?php echo $n['is_read'] ? '' : 'unread'; ?>">
                                <p><?php echo htmlspecialchars($n['message']); ?></p>
                                <small><?php echo htmlspecialchars(time_ago($n['created_at'])); ?></small>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </li>
            <li class="nav-item" style="position: relative;">
                <a class="nav-link" href="#" data-toggle="dropdown-s" data-target="#adminUserDropdown" style="display: flex; align-items: center; gap: .5rem;">
                    <span class="topbar-avatar">
                        <?php if (!empty($u['profile_pic'])): ?>
                            <img src="<?php echo htmlspecialchars(asset((string) $u['profile_pic'])); ?>" alt="">
                        <?php else: ?>
                            <?php echo $initials; ?>
                        <?php endif; ?>
                    </span>
                    <span style="font-weight: 600; color: var(--brand-deep);" class="d-none d-md-inline"><?php echo htmlspecialchars($u['full_name']); ?></span>
                    <i class="fas fa-chevron-down" style="font-size:.7rem; color: var(--muted);"></i>
                </a>
                <div id="adminUserDropdown" class="s-dropdown" style="right: 1rem; top: 56px;">
                    <div class="s-dropdown__header"><?php echo htmlspecialchars($u['full_name']); ?> · Admin</div>
                    <a class="s-dropdown__item" href="<?php echo htmlspecialchars(url('admin/profile.php')); ?>"><i class="fas fa-user"></i> Profile</a>
                    <a class="s-dropdown__item" href="<?php echo htmlspecialchars(url('admin/settings.php')); ?>"><i class="fas fa-cog"></i> Settings</a>
                    <a class="s-dropdown__item" href="<?php echo htmlspecialchars(url('admin/notifications.php')); ?>"><i class="fas fa-bell"></i> Notifications</a>
                    <div class="s-dropdown__divider"></div>
                    <a class="s-dropdown__item" href="<?php echo htmlspecialchars(url('public/logout.php')); ?>"><i class="fas fa-right-from-bracket"></i> Sign out</a>
                </div>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar elevation-4">
        <a href="<?php echo htmlspecialchars(url('admin/index.php')); ?>" class="brand-link">
            <img src="<?php echo htmlspecialchars(asset('img/Logo.png')); ?>" alt="Barangay San Roque Logo" style="width: 28px; height: 28px; object-fit: contain; border-radius: 50%; background: #fff; padding: 2px; margin-right: .5rem;">
            <span class="brand-text"><b>Sumbungan</b> Admin</span>
        </a>
        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <?php if (!empty($u['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars(asset((string) $u['profile_pic'])); ?>" class="img-circle elevation-2" alt="" style="width:2rem;height:2rem;object-fit:cover;">
                    <?php else: ?>
                        <span class="topbar-avatar" style="width:2rem;height:2rem;font-size:.75rem;"><?php echo $initials; ?></span>
                    <?php endif; ?>
                </div>
                <div class="info">
                    <a href="<?php echo htmlspecialchars(url('admin/profile.php')); ?>" class="d-block"><?php echo htmlspecialchars($u['full_name']); ?></a>
                </div>
            </div>
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-header" style="color: #fff;">DASHBOARD</li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(url('admin/index.php')); ?>" class="nav-link <?php echo $activeNav === 'analytics' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-chart-pie"></i><p>Analytics</p>
                        </a>
                    </li>
                    <li class="nav-header" style="color: #fff;">CASES</li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(url('admin/cases.php')); ?>" class="nav-link <?php echo $activeNav === 'cases' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tasks"></i><p>Case management</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(url('admin/cases.php')); ?>?status=pending" class="nav-link <?php echo ($activeNav === 'cases' && ($_GET['status'] ?? '') === 'pending') ? 'active' : ''; ?>">
                            <i class="nav-icon far fa-clock"></i><p>Pending review</p>
                        </a>
                    </li>
                    <li class="nav-header" style="color: #fff;">PEOPLE</li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(url('admin/users.php')); ?>" class="nav-link <?php echo $activeNav === 'users' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-users"></i><p>Residents</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(url('admin/notifications.php')); ?>" class="nav-link <?php echo $activeNav === 'notifications' ? 'active' : ''; ?>">
                            <i class="nav-icon far fa-bell"></i><p>Notifications</p>
                        </a>
                    </li>
                    <li class="nav-header" style="color: #fff;">SYSTEM</li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(url('admin/settings.php')); ?>" class="nav-link <?php echo $activeNav === 'settings' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-cog"></i><p>Barangay settings</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(url('admin/profile.php')); ?>" class="nav-link <?php echo $activeNav === 'profile' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-user-circle"></i><p>My profile</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(url('public/logout.php')); ?>" class="nav-link">
                            <i class="nav-icon fas fa-sign-out-alt"></i><p>Sign out</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2 align-items-center">
                    <div class="col-sm-7">
                        <h1 class="m-0"><?php echo htmlspecialchars($pageTitle); ?></h1>
                        <?php if ($pageSubtitle !== ''): ?><small style="color: var(--muted);"><?php echo htmlspecialchars($pageSubtitle); ?></small><?php endif; ?>
                    </div>
                    <div class="col-sm-5 text-sm-right">
                        <?php if (!empty($pageActions)): ?><?php echo $pageActions; ?><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                <?php echo render_flashes(); ?>
