'use strict';
const sidebar = document.querySelector('.candidate-page aside');
if (sidebar) {
    sidebar.id = 'candidate-sidebar';
    const currentPath = location.pathname.replace(/\/(?:index\.php)?$/, '');
    sidebar.querySelectorAll('a').forEach(link => {
        if (new URL(link.href).pathname.replace(/\/$/, '') === currentPath) {
            link.setAttribute('aria-current', 'page');
        }
    });
}
const toggle = document.getElementById('toggle-sidebar');
toggle?.addEventListener('click', () => {
    const open = document.body.classList.toggle('sidebar-open');
    toggle.setAttribute('aria-expanded', String(open));
});
document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
        document.body.classList.remove('sidebar-open');
        toggle?.setAttribute('aria-expanded', 'false');
        toggle?.focus();
    }
});
const detail = document.getElementById('candidate-detail');
if (detail && typeof detail.showModal === 'function') {
    detail.removeAttribute('open');
    detail.showModal();
    detail.addEventListener('click', event => {
        if (event.target !== detail) return;
        const bounds = detail.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right ||
            event.clientY < bounds.top || event.clientY > bounds.bottom) {
            location.href = detail.querySelector('a').href;
        }
    });
    detail.addEventListener('cancel', event => {
        event.preventDefault();
        location.href = detail.querySelector('a').href;
    });
}
