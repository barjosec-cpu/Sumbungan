<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$u = current_user();
if ($u !== null) {
    header('Location: ' . ($u['role'] === 'admin' ? url('admin/index.php') : url('dashboard/index.php')));
    exit;
}

$error = '';
$portal = $_GET['portal'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $portal = $_POST['portal'] ?? $portal;
    $email = sanitize_string($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = db()->prepare('SELECT id, full_name, email, password_hash, role, profile_pic FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($password, $row['password_hash'])) {
            $error = 'Invalid email or password.';
        } elseif ($portal === 'admin' && $row['role'] !== 'admin') {
            $error = 'This account is not an administrator.';
        } else {
            login_user($row);
            flash_set('success', 'Welcome back, ' . $row['full_name'] . '!');
            header('Location: ' . ($row['role'] === 'admin' ? url('admin/index.php') : url('dashboard/index.php')));
            exit;
        }
    }
}

$pageTitle = ($portal === 'admin' ? 'Admin Login' : 'Sign in') . ' | Sumbungan';
$bodyClass = '';
require_once dirname(__DIR__) . '/includes/public_header.php';
?>
<div class="s-auth">
    <aside class="s-auth__art">
        <a class="s-logo" href="<?php echo htmlspecialchars(url('public/index.php')); ?>"><img src="<?php echo htmlspecialchars(asset('img/Logo.png')); ?>" alt="Barangay San Roque Logo"> Sumbungan</a>
        <div>
            <h2>Your community, made transparent.</h2>
            <p>Sign in to file complaints, follow updates, and stay informed about your barangay.</p>
            <ul class="s-auth__bullets">
                <li><i class="fas fa-check-circle"></i> Real-time complaint tracking</li>
                <li><i class="fas fa-check-circle"></i> Secure session-based access</li>
                <li><i class="fas fa-check-circle"></i> Mobile-friendly officer portal</li>
            </ul>
        </div>
        <small style="opacity:.6;">&copy; <?php echo date('Y'); ?> Sumbungan Brgy.</small>
    </aside>
    <section class="s-auth__form">
        <div class="s-auth__form-inner">
            <h1 class="s-auth__title"><?php echo $portal === 'admin' ? 'Admin Portal' : 'Welcome back'; ?></h1>
            <p class="s-auth__sub">Sign in with your email and password to continue.</p>

            <?php echo render_flashes(); ?>
            <?php if ($error !== ''): ?>
                <div class="s-alert s-alert--error"><i class="fas fa-circle-exclamation"></i><span><?php echo htmlspecialchars($error); ?></span></div>
            <?php endif; ?>

            <form method="post">
                <?php if ($portal === 'admin'): ?><input type="hidden" name="portal" value="admin"><?php endif; ?>
                <div class="s-field">
                    <label for="email">Email</label>
                    <input class="s-input" type="email" id="email" name="email" required autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="you@example.com">
                </div>
                <div class="s-field">
                    <label for="password">Password</label>
                    <input class="s-input" type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                </div>
                <button type="submit" class="s-btn s-btn--block s-btn--lg">Sign in</button>
            </form>

            <div style="margin-top: 1.5rem; display: flex; justify-content: space-between; font-size: .9rem;">
                <a href="<?php echo htmlspecialchars(url('public/register.php')); ?>"><i class="fas fa-user-plus"></i> Create account</a>
                <?php if ($portal === 'admin'): ?>
                    <a href="<?php echo htmlspecialchars(url('public/login.php')); ?>"><i class="fas fa-user"></i> Resident sign in</a>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars(url('public/login.php')); ?>?portal=admin"><i class="fas fa-user-shield"></i> Admin portal</a>
                <?php endif; ?>
            </div>

            <div style="margin-top: 2rem; padding: 1rem; background: var(--brand-tint); border-radius: var(--radius); font-size: .85rem; color: var(--ink-soft);">
                <strong>Demo accounts</strong><br>
                Admin: <code>kap@sanroque.gov.ph</code> / <code>admin</code><br>
                Resident: <code>juana@example.com</code> / <code>123</code>
            </div>
        </div>
    </section>
</div>
<?php require_once dirname(__DIR__) . '/includes/public_footer.php'; ?>
