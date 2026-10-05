<?php
$page_title = "Contact Us — DIGITAL SMART CLASS";
require_once __DIR__ . '/includes/header.php';

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = true;
    set_flash('success', 'Thank you for reaching out! Our support team will respond within 24 hours.');
}
?>

<section class="page-hero">
    <div class="container">
        <div class="section-subtitle">Support</div>
        <h1>Get in touch</h1>
        <p>Questions about enrollments, MTN Mobile Money payments, or teacher applications? We are here to help.</p>
    </div>
</section>

<div class="container" style="padding-top: 2.4rem; padding-bottom: 4rem; max-width: 980px;">
    <div class="split-grid">
        <div class="content-card">
            <h3>Contact information</h3>

            <div class="contact-row">
                <div class="contact-icon indigo"><i class="fas fa-map-marker-alt"></i></div>
                <div>
                    <h5 style="font-weight: 800; color: #0f172a; margin-bottom: 0.2rem;">Location</h5>
                    <p style="font-size: 0.9rem; color: #64748b;">Kigali Innovation Hub, Nyarugenge, Kigali, Rwanda</p>
                </div>
            </div>

            <div class="contact-row">
                <div class="contact-icon green"><i class="fas fa-phone-alt"></i></div>
                <div>
                    <h5 style="font-weight: 800; color: #0f172a; margin-bottom: 0.2rem;">Phone &amp; WhatsApp</h5>
                    <p style="font-size: 0.9rem; color: #64748b;">+250 788 000 000 / +250 730 000 002</p>
                </div>
            </div>

            <div class="contact-row">
                <div class="contact-icon amber"><i class="fas fa-envelope"></i></div>
                <div>
                    <h5 style="font-weight: 800; color: #0f172a; margin-bottom: 0.2rem;">Email support</h5>
                    <p style="font-size: 0.9rem; color: #64748b;">contact@digitalsmart.rw / finance@digitalsmart.rw</p>
                </div>
            </div>

            <div class="info-tile" style="font-size: 0.85rem; color: #64748b;">
                <strong style="color: #0f172a;">Need payment assistance?</strong><br>
                For MTN Mobile Money or Airtel confirmation issues, mention your Transaction ID when messaging.
            </div>
        </div>

        <div class="content-card">
            <h3>Send a message</h3>
            <form action="" method="POST">
                <div class="form-group">
                    <label class="form-label">Your full name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Patrick Habimana">
                </div>
                <div class="form-group">
                    <label class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control" required placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" required placeholder="Course enrollment / MoMo support">
                </div>
                <div class="form-group">
                    <label class="form-label">Your message</label>
                    <textarea name="message" class="form-control" rows="4" required placeholder="How can we help you?"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-paper-plane"></i> Send message
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
