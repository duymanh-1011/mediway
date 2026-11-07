export function initializeConfirmations(doc = document) {
    const dialog = doc.getElementById('admin-confirm-dialog');
    let pending = null;
    let confirmedForm = null;

    doc.addEventListener('submit', (event) => {
        const form = event.target;
        const submitter = event.submitter;
        const message = submitter?.dataset.confirm || form.dataset.confirm;
        if (!message || event.defaultPrevented || form === confirmedForm) return;
        if (!dialog?.showModal) {
            if (!window.confirm(message)) event.preventDefault();
            return;
        }

        event.preventDefault();
        if (dialog.open) return;
        pending = { form, submitter, previousFocus: doc.activeElement };
        dialog.querySelector('[data-confirm-message]').textContent = message;
        dialog.returnValue = '';
        dialog.showModal();
    });

    dialog?.addEventListener('close', () => {
        if (!pending) return;
        const { form, submitter, previousFocus } = pending;
        pending = null;
        (submitter || previousFocus)?.focus();
        if (dialog.returnValue !== 'confirm') return;

        confirmedForm = form;
        try {
            if (submitter) form.requestSubmit(submitter);
            else form.requestSubmit();
        } finally {
            confirmedForm = null;
        }
    });
}
