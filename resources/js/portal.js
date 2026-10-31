const menu = document.querySelector('[data-portal-menu]');
const navigation = document.querySelector('.portal-nav');
window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload();
});
menu?.addEventListener('click', () => {
    const open = navigation.classList.toggle('is-open');
    menu.setAttribute('aria-expanded', String(open));
});
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        if (!input) return;
        const reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';
        button.textContent = reveal ? 'Ẩn' : 'Hiện';
    });
});

// MEDIWAY_PORTAL_PAGE_ASSETS_BEGIN
import './appointments-create.js';
import './appointments-actions.js';

// MEDIWAY_PORTAL_PAGE_ASSETS_END
