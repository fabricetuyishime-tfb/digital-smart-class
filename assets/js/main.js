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
