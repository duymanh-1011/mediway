import { installPortalHistoryReload } from './portal-history.js';
import { initializePendingForms } from './form-pending.js';
import { initializeNotificationToast } from './portal-notification-toast.js';

installPortalHistoryReload(window);
initializePendingForms();

const menu = document.querySelector('[data-portal-menu]');
const navigation = document.getElementById(menu?.getAttribute('aria-controls'));
if (menu && navigation) {
    const mobile = window.matchMedia('(max-width: 767px)');
    const setOpen = (open) => {
        navigation.classList.toggle('is-open', open);
        menu.setAttribute('aria-expanded', String(open));
        menu.querySelector('.sr-only').textContent = open ? 'Đóng menu điều hướng' : 'Mở menu điều hướng';
    };

    menu.addEventListener('click', () => setOpen(!navigation.classList.contains('is-open')));
    navigation.addEventListener('click', (event) => {
        if (mobile.matches && event.target.closest('a[href]')) setOpen(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !navigation.classList.contains('is-open')) return;
        setOpen(false);
        menu.focus();
    });
    mobile.addEventListener('change', () => {
        const returnFocus = mobile.matches && navigation.contains(document.activeElement);
        setOpen(false);
        if (returnFocus) menu.focus();
    });
    window.addEventListener('pageshow', () => setOpen(false));
}

document.querySelector('.portal-auth form [aria-invalid="true"]')?.focus();
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        if (!input) return;
        const reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';
        button.textContent = reveal ? 'Ẩn' : 'Hiện';
        button.setAttribute('aria-label', reveal ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    });
});

// Poll only while the page is visible; the endpoint returns data for the signed-in patient.
const notificationLink = document.querySelector('[data-notification-link]');
if (notificationLink) {
    let pending = false;
    let failures = 0;
    const updateToast = initializeNotificationToast(notificationLink);
    const refreshStatus = document.querySelector('[data-notification-refresh-status]');
    const refreshNotifications = async () => {
        if (pending || document.hidden) return;
        pending = true;
        try {
            const response = await fetch(notificationLink.dataset.notificationUrl, {
                headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store',
            });
            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('Notifications unavailable');
            }
            const data = await response.json();
            failures = 0;
            if (refreshStatus) refreshStatus.hidden = true;
            const count = Number.isInteger(data.count) && data.count > 0 ? data.count : 0;
            document.querySelectorAll('[data-notification-count]').forEach((badge) => {
                badge.hidden = count === 0;
                badge.textContent = count > 99 ? '99+' : String(count);
            });
            const mobileCount = document.querySelector('[data-notification-mobile-count]');
            if (mobileCount) mobileCount.textContent = count ? `(${count})` : '';
            notificationLink.setAttribute('aria-label', count ? `Thông báo, ${count} thông báo chưa đọc` : 'Thông báo');
            updateToast(data);
        } catch (_) {
            failures += 1;
            if (refreshStatus && failures >= 2) refreshStatus.hidden = false;
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
