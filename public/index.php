<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Sumbungan Brgy. | Modern Barangay Complaint System';
$bodyClass = 'sumbungan-public';
require_once dirname(__DIR__) . '/includes/public_header.php';
?>
<header class="s-container">
    <nav class="s-nav">
        <div class="s-logo"><img src="<?php echo htmlspecialchars(asset('img/Logo.png')); ?>" alt="Barangay San Roque Logo"> Sumbungan</div>
        <div class="s-nav__links">
            <a href="#features">Features</a>
            <a href="#how">How it works</a>
            <a href="#about">About</a>
            <a href="<?php echo htmlspecialchars(url('public/login.php')); ?>" class="s-btn s-btn--ghost s-btn--sm">Sign in</a>
            <a href="<?php echo htmlspecialchars(url('public/register.php')); ?>" class="s-btn s-btn--sm">Get started</a>
        </div>
    </nav>
</header>

<main>
    <section class="s-container s-hero">
        <div>
            <span class="s-badge" style="margin-bottom: 1rem;">Barangay-grade • <?php echo date('Y'); ?></span>
            <h1>A safer, more orderly <span>community.</span></h1>
            <p>File complaints, follow updates in real time, and watch your barangay resolve concerns transparently — all in one place.</p>
            <div class="s-hero__actions">
                <a href="<?php echo htmlspecialchars(url('public/register.php')); ?>" class="s-btn s-btn--lg">File a complaint <i class="fas fa-arrow-right"></i></a>
                <a href="#how" class="s-btn s-btn--ghost s-btn--lg">How it works</a>
            </div>
        </div>
        <div class="s-hero__art">
            <img src="<?php echo htmlspecialchars(asset('img/hero.png')); ?>" alt="Peaceful community at sunset">
            
        </div>
    </section>

    <section class="s-container" id="features">
        <div class="s-section__head">
            <h2>Why choose <span>Sumbungan?</span></h2>
            <p>Empowering residents through transparency and digital efficiency.</p>
        </div>
        <div class="s-features">
            <div class="s-feature">
                <div class="s-feature__icon"><i class="fas fa-bolt"></i></div>
                <h3>Real-time tracking</h3>
                <p>Follow your complaint from filing to resolution with a clear timeline.</p>
            </div>
            <div class="s-feature">
                <div class="s-feature__icon"><i class="fas fa-lock"></i></div>
                <h3>Secure &amp; private</h3>
                <p>Sessions, hashed passwords, and access controlled by role.</p>
            </div>
            <div class="s-feature">
                <div class="s-feature__icon"><i class="fas fa-chart-line"></i></div>
                <h3>Smart analytics</h3>
                <p>Officials see live KPIs, trends, and hotspots to act faster.</p>
            </div>
        </div>
    </section>

    <section class="s-container" id="how">
        <div class="s-section__head">
            <h2>How it <span>works</span></h2>
            <p>Three simple steps from filing to resolution.</p>
        </div>
        <div class="s-steps">
            <div class="s-step"><div class="s-step__num">1</div><h4>File a report</h4><p>Sign in, describe the incident, attach a photo if needed.</p></div>
            <div class="s-step"><div class="s-step__num">2</div><h4>Official review</h4><p>Peace officers validate and assign your case.</p></div>
            <div class="s-step"><div class="s-step__num">3</div><h4>Resolution</h4><p>Track each timeline update until your case is resolved.</p></div>
        </div>
    </section>
<!--
    <section class="s-container" id="about" style="padding: 4rem 0;">
        <div class="s-stats">
            <div><div class="s-stat__num">500+</div><div class="s-stat__label">Active residents</div></div>
            <div><div class="s-stat__num">98%</div><div class="s-stat__label">Resolution rate</div></div>
            <div><div class="s-stat__num">24/7</div><div class="s-stat__label">Accessibility</div></div>
            <div><div class="s-stat__num">15</div><div class="s-stat__label">Peace officers</div></div>
        </div>-->
        <div class="s-section__head" style="margin-bottom: 2rem;">
            <h2>About <span>Sumbungan</span></h2>
            <p>Sumbungan bridges residents and barangay leaders through a transparent, easy-to-use platform built for everyday use.</p>
        </div>
    </section>
</main>

<footer class="s-footer">
    <div class="s-container">
        <div class="s-logo" style="color:#fff; justify-content:center;"><img src="<?php echo htmlspecialchars(asset('img/Logo.png')); ?>" alt="Barangay San Roque Logo"> Sumbungan</div>
        <p style="opacity:.75; max-width:560px; margin: 1rem auto;">The official complaint and management portal of Barangay Sanroque.</p>
        <div class="s-footer__copy">&copy; <?php echo date('Y'); ?> Sumbungan Brgy. All rights reserved.</div>
    </div>
</footer>

<?php require_once dirname(__DIR__) . '/includes/public_footer.php'; ?>
