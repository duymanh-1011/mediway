export function initializeNotificationToast(notificationLink, doc = document, win = window) {
    const toast = doc.querySelector('[data-notification-toast]');
    if (!toast) return () => {};

    const storageKey = 'mediway.notification.seen.' + notificationLink.dataset.notificationAccount;
    const title = toast.querySelector('[data-toast-title]');
    const category = toast.querySelector('[data-toast-category]');
    const detail = toast.querySelector('[data-toast-detail]');
    const key = toast.querySelector('[data-toast-key]');
    const announce = doc.querySelector('[data-toast-announcement]');
    const waiting = new Map();
    let known = null;
    let active = null;

    const stored = () => {
        try {
            const value = JSON.parse(win.localStorage.getItem(storageKey));
            return Array.isArray(value) ? value.filter((entry) => typeof entry === 'string') : null;
        } catch {
            return null;
        }
    };

    const renderNext = () => {
        active = [...waiting.values()].find((item) => item.urgent) ?? waiting.values().next().value ?? null;
        toast.hidden = active === null;
        if (!active) {
            announce.textContent = '';
            return;
        }
        waiting.delete(active.key);
        key.value = active.key;
        title.textContent = active.title;
        category.textContent = 'Thông báo mới · ' + active.category;
        detail.textContent = active.detail ?? '';
        detail.hidden = !active.detail;
        announce.textContent = 'Thông báo mới. ' + active.title;
    };

    toast.querySelector('[data-toast-close]').addEventListener('click', () => {
        renderNext();
        if (toast.hidden && notificationLink.getClientRects().length) notificationLink.focus();
        else if (toast.hidden) doc.querySelector('[data-portal-menu]')?.focus();
    });

    return (data) => {
        if (!Array.isArray(data.unread)) return;
        const unread = new Map(data.unread.map((item) => [item.key, item]));
        const previous = stored() ?? known;
        const firstCall = unread.get(data.active_call?.key);
        const incoming = previous === null
            ? [firstCall ?? data.unread[0]].filter(Boolean)
            : data.unread.filter((item) => !previous.includes(item.key) && item.key !== active?.key);

        // Remove alerts read elsewhere or resolved while the patient is on the page.
        for (const id of waiting.keys()) {
            if (!unread.has(id)) waiting.delete(id);
        }
        if (active && !unread.has(active.key)) active = null;
        if (firstCall && incoming.some((item) => item.key === firstCall.key)) {
            // A call to the exam room is time-sensitive.
            if (active && active.key !== firstCall.key) waiting.set(active.key, active);
            active = null;
        }
        for (const item of incoming) {
            const urgent = item.key === firstCall?.key;
            waiting.set(item.key, { ...item, urgent, detail: urgent ? data.active_call.message : null });
        }
        if (!active) renderNext();

        known = [...new Set([...unread.keys(), ...(previous ?? [])])].slice(0, 200);
        try { win.localStorage.setItem(storageKey, JSON.stringify(known)); } catch { /* Session-local state is sufficient. */ }
    };
}
