import { installPortalHistoryReload } from './portal-history.js';

const menu = document.querySelector('[data-portal-menu]');
const navigation = document.querySelector('.portal-nav');
installPortalHistoryReload(window);
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

// Poll only while the page is visible; the endpoint returns data for the signed-in patient.
const notificationLink = document.querySelector('[data-notification-link]');
if (notificationLink) {
    let pending = false;
    const refreshNotifications = async () => {
        if (pending || document.hidden) return;
        pending = true;
        try {
            const response = await fetch(notificationLink.dataset.notificationUrl, {
                headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store',
            });
            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) return;
            const data = await response.json();
            const count = Number.isInteger(data.count) && data.count > 0 ? data.count : 0;
            document.querySelectorAll('[data-notification-count]').forEach((badge) => {
                badge.hidden = count === 0;
                badge.textContent = count > 99 ? '99+' : String(count);
            });
            const mobileCount = document.querySelector('[data-notification-mobile-count]');
            if (mobileCount) mobileCount.textContent = count ? `(${count})` : '';
            notificationLink.setAttribute('aria-label', count ? `Thông báo, ${count} thông báo chưa đọc` : 'Thông báo');
            const alert = document.querySelector('[data-call-alert]');
            if (alert) {
                alert.hidden = !data.active_call;
                alert.querySelector('[data-call-title]').textContent = data.active_call ? 'Đã đến lượt khám.' : '';
                alert.querySelector('[data-call-message]').textContent = data.active_call?.message || '';
                alert.querySelector('[data-call-key]').value = data.active_call?.key || '';
            }
        } catch (_) {
            // A temporary connection error must not disrupt the rest of the portal.
        } finally {
            pending = false;
        }
    };
    refreshNotifications();
    window.setInterval(refreshNotifications, 15000);
    document.addEventListener('visibilitychange', refreshNotifications);
}

// MEDIWAY_PORTAL_PAGE_ASSETS_BEGIN
import './appointments-create.js';
import './appointments-actions.js';

// MEDIWAY_PORTAL_PAGE_ASSETS_END
