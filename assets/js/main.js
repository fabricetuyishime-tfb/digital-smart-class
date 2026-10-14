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


/* ================= Responsive navbar / dashboard sidebar drawer =================
 * Public pages: the hamburger opens the top navigation drawer.
 * Dashboard pages (which render a .sidebar): the hamburger opens that sidebar
 * as an off-canvas drawer on mobile.
 */
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('navToggle');
    const menu = document.getElementById('navMenu');
    const overlay = document.getElementById('navOverlay');
    const closeBtn = document.getElementById('navClose');
    if (!toggle) return;

    const sidebar = document.querySelector('.dashboard-layout .sidebar') || document.querySelector('.sidebar');
    const MOBILE_BREAKPOINT = 900;
    const isMobile = function () { return window.innerWidth <= MOBILE_BREAKPOINT; };

    // Build the sidebar overlay + close button once.
    let sidebarOverlay = null;
    let sidebarClose = null;
    if (sidebar) {
        if (!sidebar.id) {
            sidebar.id = 'dashSidebar';
        }
        toggle.setAttribute('aria-controls', sidebar.id);

        sidebarOverlay = document.createElement('div');
        sidebarOverlay.className = 'sidebar-overlay';
        sidebarOverlay.setAttribute('aria-hidden', 'true');
        document.body.appendChild(sidebarOverlay);

        sidebarClose = document.createElement('button');
        sidebarClose.type = 'button';
        sidebarClose.className = 'sidebar-close';
        sidebarClose.setAttribute('aria-label', 'Close menu');
        sidebarClose.innerHTML = '<i class="fas fa-times"></i>';
        sidebar.insertBefore(sidebarClose, sidebar.firstChild);
    }

    function setIcon(isOpen) {
        const icon = toggle.querySelector('i');
        if (!icon) return;
        icon.classList.toggle('fa-bars', !isOpen);
        icon.classList.toggle('fa-times', isOpen);
    }

    function setNavOpen(isOpen) {
        if (!menu) return;
        menu.classList.toggle('is-open', isOpen);
        document.body.classList.toggle('nav-open', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (overlay) {
            overlay.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        }
        setIcon(isOpen);
        if (isOpen && closeBtn) {
            closeBtn.focus();
        }
    }

    function setSidebarOpen(isOpen) {
        if (!sidebar) return;
        sidebar.classList.toggle('is-open', isOpen);
        document.body.classList.toggle('sidebar-open', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (sidebarOverlay) {
            sidebarOverlay.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        }
        setIcon(isOpen);
    }

    toggle.addEventListener('click', function () {
        if (sidebar) {
            setSidebarOpen(!sidebar.classList.contains('is-open'));
        } else {
            setNavOpen(!(menu && menu.classList.contains('is-open')));
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            setNavOpen(false);
            toggle.focus();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            setNavOpen(false);
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            setSidebarOpen(false);
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', function () {
            setSidebarOpen(false);
            toggle.focus();
        });
    }

    if (menu) {
        menu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isMobile()) {
                    setNavOpen(false);
                }
            });
        });
    }

    if (sidebar) {
        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isMobile()) {
                    setSidebarOpen(false);
                }
            });
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (sidebar && sidebar.classList.contains('is-open')) {
            setSidebarOpen(false);
            toggle.focus();
        } else if (menu && menu.classList.contains('is-open')) {
            setNavOpen(false);
            toggle.focus();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > MOBILE_BREAKPOINT) {
            if (menu && menu.classList.contains('is-open')) {
                setNavOpen(false);
            }
            if (sidebar && sidebar.classList.contains('is-open')) {
                setSidebarOpen(false);
            }
        }
    });
});
