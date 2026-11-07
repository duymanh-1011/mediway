export function initializePendingForms(doc = document, win = window) {
    const active = new Map();
    win.addEventListener('submit', (event) => {
        const form = event.target;
        if (active.has(form)) {
            event.preventDefault();
            return;
        }
        if (event.defaultPrevented || form.method !== 'post') return;

        const button = event.submitter || form.querySelector('[data-pending-label]');
        if (!button?.dataset.pendingLabel || button.disabled || button.name) return;
        active.set(form, { button, label: button.textContent });
        button.disabled = true;
        button.textContent = button.dataset.pendingLabel;
        form.setAttribute('aria-busy', 'true');
    });

    win.addEventListener('pageshow', () => {
        for (const [form, { button, label }] of active) {
            button.disabled = false;
            button.textContent = label;
            form.removeAttribute('aria-busy');
        }
        active.clear();
    });
}
