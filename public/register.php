<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (current_user() !== null) {
    header('Location: ' . url('dashboard/index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize_string($_POST['full_name'] ?? '');
    $email = sanitize_string($_POST['email'] ?? '');
    $address = sanitize_string($_POST['address'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if ($fullName === '' || $email === '' || $password === '' || strlen($password) < 8) {
        $error = 'Full name, email, and a password of at least 8 characters are required.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = db()->prepare('INSERT INTO users (full_name, email, password_hash, role, address) VALUES (?,?,?,?,?)');
            $stmt->execute([$fullName, $email, $hash, 'complainant', $address ?: null]);
            $id = (int) db()->lastInsertId();
            $stmt = db()->prepare('SELECT id, full_name, email, password_hash, role, profile_pic FROM users WHERE id = ?');
            $stmt->execute([$id]);
            login_user($stmt->fetch());
            add_notification(db(), $id, 'Welcome to Sumbungan! File your first complaint anytime.');
            flash_set('success', 'Account created. Welcome to Sumbungan!');
            header('Location: ' . url('dashboard/index.php'));
            exit;
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate')) {
                $error = 'That email is already registered.';
            } else {
                $error = 'Could not create your account. Please try again.';
            }
        }
    }
}

$pageTitle = 'Create account | Sumbungan';
$bodyClass = '';
require_once dirname(__DIR__) . '/includes/public_header.php';
?>
<div class="s-auth">
    <aside class="s-auth__art">
        <a class="s-logo" href="<?php echo htmlspecialchars(url('public/index.php')); ?>"><img src="<?php echo htmlspecialchars(asset('img/Logo.png')); ?>" alt="Barangay San Roque Logo"> Sumbungan</a>
        <div>
            <h2>Be heard. Be helped.</h2>
            <p>Join your barangay's official channel for community concerns and stay updated.</p>
            <ul class="s-auth__bullets">
                <li><i class="fas fa-check-circle"></i> File complaints with photos</li>
                <li><i class="fas fa-check-circle"></i> Track every status update</li>
                <li><i class="fas fa-check-circle"></i> Receive in-app notifications</li>
            </ul>
        </div>
        <small style="opacity:.6;">&copy; <?php echo date('Y'); ?> Sumbungan Brgy.</small>
    </aside>
    <section class="s-auth__form">
        <div class="s-auth__form-inner">
            <h1 class="s-auth__title">Create your account</h1>
            <p class="s-auth__sub">It only takes a minute. Already a member? <a href="<?php echo htmlspecialchars(url('public/login.php')); ?>">Sign in</a>.</p>

            <?php if ($error !== ''): ?>
                <div class="s-alert s-alert--error"><i class="fas fa-circle-exclamation"></i><span><?php echo htmlspecialchars($error); ?></span></div>
            <?php endif; ?>

            <form method="post">
                <div class="s-field">
                    <label for="full_name">Full name</label>
                    <input class="s-input" type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" placeholder="Juan Dela Cruz">
                </div>
                <div class="s-field">
                    <label for="address">Address / Street</label>
                    <input class="s-input" type="text" id="address" name="address" required value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" placeholder="Sitio 1, Brgy. Sanroque">
                </div>
                <div class="s-field">
                    <label for="email">Email</label>
                    <input class="s-input" type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="you@example.com">
                </div>
                <div class="s-field">
                    <label for="password">Password</label>
                    <input class="s-input" type="password" id="password" name="password" required minlength="8" placeholder="At least 8 characters">
                </div>
                <button type="submit" class="s-btn s-btn--block s-btn--lg">Create account</button>
            </form>

            <p style="margin-top: 1.5rem; font-size: .85rem; color: var(--muted); text-align: center;">
                <a href="<?php echo htmlspecialchars(url('public/index.php')); ?>"><i class="fas fa-arrow-left"></i> Back to home</a>
            </p>
        </div>
    </section>
</div>
<?php require_once dirname(__DIR__) . '/includes/public_footer.php'; ?>
