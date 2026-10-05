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
                    <p><i class="fas fa-envelope"></i> contact@digitalsmart.rw</p>
                    <p><i class="fas fa-phone"></i> +250 788 000 000</p>
                </div>
                <div class="social-row">
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
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

<script src="<?= site_url('assets/js/main.js'); ?>"></script>
</body>
</html>
