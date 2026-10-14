</div> <!-- End of .main-content -->

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <h3>Digital Smart Class</h3>
                <p class="footer-about">
                    A professional academy in Kigali for creators who want practical YouTube, Shorts, and AI video skills.
                </p>
                <div class="footer-contact">
                    <p><i class="fas fa-map-marker-alt"></i> Kigali, Rwanda</p>
                    <p><i class="fas fa-envelope"></i> <a href="mailto:<?= e(support_email()); ?>"><?= e(support_email()); ?></a></p>
                    <p><i class="fas fa-phone"></i> <a href="tel:<?= e(support_phone_tel()); ?>"><?= e(support_phone_display()); ?></a></p>
                    <p><i class="fab fa-whatsapp"></i> <a href="<?= e(support_whatsapp_url('Hello Digital Smart Class support team, I need assistance.')); ?>" target="_blank" rel="noopener">WhatsApp Support</a></p>
                </div>
                <div class="social-row">
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="<?= e(support_whatsapp_url('Hello Digital Smart Class support team, I need assistance.')); ?>" target="_blank" rel="noopener" aria-label="Chat with support on WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>

            <div class="footer-col">
                <h5>Popular Courses</h5>
                <ul class="footer-links">
                    <li><a href="<?= site_url('index.php#courses'); ?>">YouTube Creator Academy</a></li>
                    <li><a href="<?= site_url('index.php#courses'); ?>">Viral YouTube Shorts</a></li>
                    <li><a href="<?= site_url('index.php#courses'); ?>">AI-Powered Long-Form Video</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h5>Learn With Us</h5>
                <ul class="footer-links">
                    <li><a href="<?= site_url('index.php'); ?>">Home</a></li>
                    <li><a href="<?= site_url('courses.php'); ?>">Browse Courses</a></li>
                    <li><a href="<?= site_url('register.php'); ?>">Enroll Now</a></li>
                    <li><a href="<?= site_url('login.php'); ?>">Student Login</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h5>Academy</h5>
                <ul class="footer-links">
                    <li><a href="<?= site_url('about.php'); ?>">About Us</a></li>
                    <li><a href="<?= site_url('contact.php'); ?>">Contact & Support</a></li>
                    <li><a href="<?= site_url('login.php'); ?>">Teacher Portal</a></li>
                    <li><a href="<?= site_url('register.php'); ?>">Create Account</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                &copy; <?= date('Y'); ?> Digital Smart Class. All rights reserved.
            </div>
            <div>
                Built for learners in Rwanda.
            </div>
        </div>
    </div>
</footer>

<!-- Floating WhatsApp support launcher -->
<a class="wa-support"
   href="<?= e(support_whatsapp_url('Hello Digital Smart Class support team, I would like some help.')); ?>"
   target="_blank" rel="noopener"
   aria-label="Chat with our support team on WhatsApp">
    <span class="wa-support-badge"><i class="fab fa-whatsapp" aria-hidden="true"></i></span>
    <span class="wa-support-body">
        <span class="wa-support-title">Need help?</span>
        <span class="wa-support-sub">Chat with support</span>
    </span>
</a>

<script src="<?= site_url('assets/js/main.js'); ?>"></script>
</body>
</html>
