<?php
$page_title = "About Us — DIGITAL SMART CLASS";
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="section-subtitle">Our story</div>
        <h1>About Digital Smart Class</h1>
        <p>Empowering Africa's digital creators and entrepreneurs through direct, practical education.</p>
    </div>
</section>

<div class="container" style="padding-top: 2.4rem; padding-bottom: 4rem; max-width: 920px;">
    <div class="content-card" style="line-height: 1.8; color: #334155;">
        <h3>Our mission</h3>
        <p style="margin-bottom: 1.6rem;">
            Digital Smart Class is an advanced e-learning platform created to bridge the skills gap for modern digital content creators in Rwanda and across East Africa. We focus exclusively on high-income, practical digital disciplines: YouTube Channel Architecture, Short-Form Vertical Video Viral Distribution, and AI-Assisted Automated Video Production.
        </p>

        <h3>How our platform works</h3>
        <div class="split-grid" style="margin-bottom: 1.8rem;">
            <div class="info-tile">
                <h4 style="font-weight: 800; color: #4f46e5; margin-bottom: 0.5rem;"><i class="fas fa-mobile-alt"></i> Local MoMo integration</h4>
                <p style="font-size: 0.9rem; color: #64748b;">No international credit cards needed. Enroll easily via MTN Mobile Money, Airtel Money, or direct bank transfer.</p>
            </div>
            <div class="info-tile">
                <h4 style="font-weight: 800; color: #4f46e5; margin-bottom: 0.5rem;"><i class="fas fa-user-shield"></i> Vetted instructors</h4>
                <p style="font-size: 0.9rem; color: #64748b;">Instructors undergo rigorous portfolio verification and admin approval before being granted permission to teach.</p>
            </div>
        </div>

        <h3>Our core values</h3>
        <ul style="padding-left: 1.35rem; margin-bottom: 2rem;">
            <li><strong>Practicality first:</strong> Zero generic theory; 100% actionable screen workflows and templates.</li>
            <li><strong>Fair pricing:</strong> World-class digital creator training accessible in local RWF currency.</li>
            <li><strong>Progress &amp; accountability:</strong> Track lessons, watch times, and earn verified digital completion credentials.</li>
        </ul>

        <div style="text-align: center; margin-top: 1.5rem;">
            <a href="<?= site_url('courses.php'); ?>" class="btn btn-primary btn-lg">Explore flagship courses</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
