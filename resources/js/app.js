import { initializeLookups } from './admin-lookups';
import { validationMessage } from './admin-validation';
import { initializeAppointmentBooking } from './appointment-booking';

const initializeAdmin = () => {
    initializeLookups();
    new MutationObserver(() => initializeLookups()).observe(document.body, { childList: true, subtree: true });
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    document.querySelectorAll('.admin-content form, .login-card form').forEach((form) => {
        if (form.method.toLowerCase() === 'get') return;
        form.noValidate = true;
        const controls = [...form.querySelectorAll('input, select, textarea')]
            .filter((control) => control.willValidate && !control.dataset.lookupInput && !control.dataset.lookupEnhanced);

        const validate = (control) => {
            const label = control.dataset.lookupLabel || control.labels?.[0]?.textContent.replace(/\s+/g, ' ').replace('*', '').trim() || 'Thông tin';
            const message = validationMessage(control, label);

            let error = control.nextElementSibling;
            if (!error?.classList.contains('field-error')) {
                error = document.createElement('div');
                error.className = 'field-error';
                control.insertAdjacentElement('afterend', error);
            }
            if (!error.id) error.id = `admin-error-${control.id || control.name.replace(/[^a-z0-9]/gi, '-')}`;
            error.setAttribute('aria-live', 'polite');
            error.textContent = message;
            error.hidden = !message;
            control.setAttribute('aria-invalid', message ? 'true' : 'false');
            if (message) control.setAttribute('aria-describedby', error.id);
            return !message;
        };

        controls.forEach((control) => {
            control.addEventListener('blur', () => validate(control));
            control.addEventListener('input', () => validate(control));
            control.addEventListener('change', () => validate(control));
        });
        form.addEventListener('submit', (event) => {
            const invalid = controls.filter((control) => !validate(control));
            if (invalid.length) {
                event.preventDefault();
                (invalid[0].closest('.admin-lookup')?.querySelector('[role="combobox"]') || invalid[0]).focus();
            }
        });
    });
    initializeAppointmentBooking();
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeAdmin);
else initializeAdmin();
