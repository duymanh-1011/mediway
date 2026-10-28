export const sidebarInitiallyOpen = (mobile, saved) => !mobile && saved !== 'closed';

export function initializeAdminSidebar(doc = document, win = window) {
    const sidebar = doc.getElementById('admin-sidebar');
    const toggle = doc.querySelector('[data-sidebar-toggle]');
    const main = doc.querySelector('.admin-main');
    const backdrop = doc.querySelector('.admin-sidebar-backdrop');
    if (!sidebar || !toggle || !main || !backdrop || sidebar.dataset.sidebarReady) return;
    sidebar.dataset.sidebarReady = 'true';
    const media = win.matchMedia('(max-width: 1024px)');
    const read = (key) => { try { return win.localStorage.getItem(key); } catch { return null; } };
    const save = (key, value) => { try { win.localStorage.setItem(key, value); } catch { /* Storage is optional. */ } };
    let open = false;
    const setOpen = (value, persist = false, moveFocus = false) => {
        doc.documentElement.dataset.sidebarAnimated = String(persist || moveFocus);
        doc.documentElement.dataset.sidebarState = value ? 'open' : 'closed';
        open = value;
        if (!open) {
            main.inert = false;
            if (sidebar.contains(doc.activeElement)) toggle.focus();
        }
        doc.body.classList.toggle('admin-sidebar-collapsed', !open);
        doc.body.classList.toggle('admin-sidebar-open', media.matches && open);
        sidebar.inert = !open;
        sidebar.setAttribute('aria-hidden', String(!open));
        main.inert = media.matches && open;
        toggle.setAttribute('aria-expanded', String(open));
        const label = `${open ? 'Đóng' : 'Mở'} thanh điều hướng`;
        toggle.setAttribute('aria-label', label);
        toggle.title = label;
        backdrop.hidden = !media.matches || !open;
        if (persist && !media.matches) save('mediway_sidebar_state', open ? 'open' : 'closed');
        if (moveFocus && media.matches && open) {
            const focusDrawer = () => { if (open && media.matches) sidebar.querySelector('[data-sidebar-close]').focus(); };
            if (win.requestAnimationFrame) win.requestAnimationFrame(focusDrawer);
            else focusDrawer();
        }
    };
    toggle.addEventListener('click', () => setOpen(!open, true, true));
    doc.querySelectorAll('[data-sidebar-close]').forEach((button) => button.addEventListener('click', () => setOpen(false, true)));
    sidebar.addEventListener('click', (event) => {
        if (media.matches && event.target.closest('a[href]')) setOpen(false);
    });
    doc.addEventListener('keydown', (event) => {
        if (!open) return;
        if (event.key === 'Escape' && (media.matches || sidebar.contains(doc.activeElement))) {
            event.preventDefault(); setOpen(false, true); toggle.focus();
        }
        if (event.key === 'Tab' && media.matches) {
            const controls = [...sidebar.querySelectorAll('a[href], button:not(:disabled), summary, input:not(:disabled), select:not(:disabled)')]
                .filter((element) => element.getClientRects().length > 0);
            const first = controls[0], last = controls.at(-1);
            if (!first) { event.preventDefault(); sidebar.focus(); }
            else if (event.shiftKey && doc.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && doc.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    media.addEventListener('change', () => setOpen(sidebarInitiallyOpen(media.matches, read('mediway_sidebar_state'))));
    win.addEventListener('pagehide', () => { doc.documentElement.dataset.sidebarAnimated = 'false'; });
    win.addEventListener('pageshow', () => setOpen(sidebarInitiallyOpen(media.matches, read('mediway_sidebar_state'))));
    const position = Number(read('mediway_sidebar_scroll'));
    if (Number.isFinite(position) && position >= 0) sidebar.scrollTop = position;
    sidebar.addEventListener('scroll', () => save('mediway_sidebar_scroll', String(sidebar.scrollTop)), {passive: true});
    setOpen(sidebarInitiallyOpen(media.matches, read('mediway_sidebar_state')));
}
