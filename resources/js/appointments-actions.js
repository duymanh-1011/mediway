
/**
 * MEDIWAY - SINGLE VALIDATION MESSAGE
 *
 * Moi form chi hien thi 1 thong bao loi.
 * Khong tao validation summary.
 * Giu nguyen validation phia Laravel.
 */

function initAppointmentActions() {
    const root = document.querySelector(
        '.mw-appointment-action'
    );

    if (!root) return;

    // Loai bo thong bao tong cua JS cu neu con trong DOM.
    root.querySelectorAll(
        '.mw-validation-summary'
    ).forEach(element => element.remove());

    function createFeedback(anchor, id) {
        if (!anchor) return null;

        let feedback = root.querySelector(`#${id}`);

        if (!feedback) {
            feedback = document.createElement('div');
            feedback.id = id;
            feedback.className = 'mw-validation-message';
            feedback.setAttribute('role', 'alert');
            feedback.hidden = true;

            anchor.insertAdjacentElement(
                'afterend',
                feedback
            );
        }

        return feedback;
    }

    function setError(feedback, message) {
        if (!feedback) return;

        feedback.textContent = message;
        feedback.hidden = message === '';
    }

    function addDescription(input, id) {
        if (!input || !id) return;

        const ids = (
            input.getAttribute('aria-describedby') || ''
        ).split(/\s+/).filter(Boolean);

        if (!ids.includes(id)) {
            ids.push(id);
        }

        input.setAttribute(
            'aria-describedby',
            ids.join(' ')
        );
    }

    // =====================================
    // FORM HUY LICH KHAM
    // =====================================

    const cancelForm = root.querySelector(
        '[data-mw-cancel-form]'
    );

    if (cancelForm) {
        cancelForm.noValidate = true;

        const reason = cancelForm.querySelector(
            '[data-mw-cancellation-reason]'
        );

        const counter = cancelForm.querySelector(
            '[data-mw-reason-counter]'
        );

        if (reason) {
            const helper = cancelForm.querySelector(
                '.mw-form-helper-row'
            );

            const feedback = createFeedback(
                helper || reason,
                'mw-cancel-validation-error'
            );

            addDescription(reason, feedback?.id);

            let attempted = false;
            let touched = false;

            const min = Number(reason.minLength) > 0
                ? Number(reason.minLength)
                : 5;

            const max = Number(reason.maxLength) > 0
                ? Number(reason.maxLength)
                : 2000;

            function updateCounter() {
                if (!counter) return;

                counter.textContent =
                    `${reason.value.length.toLocaleString('vi-VN')} / ` +
                    `${max.toLocaleString('vi-VN')} ký tự`;
            }

            function validate(show = false) {
                const value = reason.value.trim();
                let message = '';

                if (!value) {
                    message =
                        'Vui lòng nhập lý do hủy lịch khám.';
                } else if (value.length < min) {
                    message =
                        `Lý do hủy lịch phải có ít nhất ${min} ký tự.`;
                } else if (value.length > max) {
                    message =
                        `Lý do hủy lịch không được vượt quá ${max} ký tự.`;
                }

                const visible = show ? message : '';

                reason.classList.toggle(
                    'mw-is-invalid',
                    Boolean(visible)
                );

                reason.setAttribute(
                    'aria-invalid',
                    String(Boolean(visible))
                );

                // Chi hien 1 thong bao duoi textarea.
                setError(feedback, visible);

                return message === '';
            }

            reason.addEventListener('input', () => {
                updateCounter();

                if (touched || attempted) {
                    validate(true);
                }
            });

            reason.addEventListener('blur', () => {
                touched = true;
                validate(true);
            });

            cancelForm.addEventListener('submit', event => {
                attempted = true;

                if (!validate(true)) {
                    event.preventDefault();
                    reason.focus();
                }
            });

            updateCounter();
        }
    }


}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initAppointmentActions,
        { once: true }
    );
} else {
    initAppointmentActions();
}
