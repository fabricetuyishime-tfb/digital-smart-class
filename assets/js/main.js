/**
 * Digital Smart Class - Client Interactions
 */
document.addEventListener('DOMContentLoaded', function () {
    const path = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('.nav-link').forEach(function (link) {
        const href = link.getAttribute('href') || '';
        if (href.indexOf(path) !== -1 || (path === '' && href.indexOf('index.php') !== -1)) {
            link.classList.add('active');
        }
    });

    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function () {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 500);
        }, 5000);
    });
});


/* ================= 3D hero parallax ================= */
document.addEventListener('DOMContentLoaded', function () {
    const stage = document.querySelector('.hero-stage');
    if (!stage) return;

    const scene = stage.querySelector('.hero-scene');
    const rings = stage.querySelectorAll('.orbit-ring');
    if (!scene) return;

    const BASE_ROTATION = 0;
    let rafId = null;
    let targetX = 0;
    let targetY = 0;
    let currentX = 0;
    let currentY = 0;

    function tick() {
        currentX += (targetX - currentX) * 0.08;
        currentY += (targetY - currentY) * 0.08;
        const transform = `rotateX(${currentX}deg) rotateY(${currentY}deg)`;
        stage.style.transform = transform;
        rafId = requestAnimationFrame(tick);
    }

    stage.addEventListener('pointermove', function (event) {
        const rect = stage.getBoundingClientRect();
        const x = (event.clientX - rect.left) / rect.width - 0.5;
        const y = (event.clientY - rect.top) / rect.height - 0.5;
        targetY = x * 24;
        targetX = -y * 18;
    });

    stage.addEventListener('pointerleave', function () {
        targetX = 0;
        targetY = 0;
    });

    // Pause when tab is hidden to save resources, resume when visible
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            if (rafId) cancelAnimationFrame(rafId);
            rafId = null;
        } else if (!rafId) {
            rafId = requestAnimationFrame(tick);
        }
    });

    rafId = requestAnimationFrame(tick);
});


/* ================= Responsive navbar drawer ================= */
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('navToggle');
    const menu = document.getElementById('navMenu');
    const overlay = document.getElementById('navOverlay');
    const closeBtn = document.getElementById('navClose');
    if (!toggle || !menu) return;

    const MOBILE_BREAKPOINT = 900;

    function setOpen(isOpen) {
        menu.classList.toggle('is-open', isOpen);
        document.body.classList.toggle('nav-open', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (overlay) {
            overlay.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        }
        const icon = toggle.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-bars', !isOpen);
            icon.classList.toggle('fa-times', isOpen);
        }
        if (isOpen && closeBtn) {
            closeBtn.focus();
        }
    }

    toggle.addEventListener('click', function () {
        setOpen(!menu.classList.contains('is-open'));
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            setOpen(false);
            toggle.focus();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            setOpen(false);
        });
    }

    menu.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= MOBILE_BREAKPOINT) {
                setOpen(false);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && menu.classList.contains('is-open')) {
            setOpen(false);
            toggle.focus();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > MOBILE_BREAKPOINT && menu.classList.contains('is-open')) {
            setOpen(false);
        }
    });
});
