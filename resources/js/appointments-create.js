/* MEDIWAY_PORTAL_EXTRACTED_FROM: resources/views/portal/appointments/create.blade.php */
if (document.querySelector('[data-mediway-page-script="appointments-create"]')) {
document.addEventListener('DOMContentLoaded', () => {
        const profileError = document.querySelector('[data-profile-error]');
        if (profileError) {
            profileError.focus({ preventScroll: true });
            profileError.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
        const form = document.getElementById('mw-book-form');

        if (!form) {
            return;
        }

        const reason = document.getElementById('reason_for_visit');
        const notes = document.getElementById('patient_notes');
        const consent = document.getElementById('confirmed_information');

        const confirmPanel = document.getElementById(
            'mw-confirm-panel'
        );

        const reasonError = document.getElementById(
            'mw-reason-error'
        );

        const notesError = document.getElementById(
            'mw-notes-error'
        );

        const confirmError = document.getElementById(
            'mw-confirm-error'
        );

        function mark(field, output, message) {
            field.setAttribute(
                'aria-invalid',
                message ? 'true' : 'false'
            );

            output.textContent = message;

            return !message;
        }

        function checkReason() {
            const value = reason.value.trim();

            let error = '';

            if (!value) {
                error = 'Vui lòng nhập lý do khám.';
            } else if (!/\p{L}/u.test(value)) {
                error =
                    'Lý do khám phải có chữ cái, ' +
                    'không được chỉ nhập số hoặc ký tự đặc biệt.';
            } else if (value.length > 2000) {
                error =
                    'Lý do khám không được vượt quá 2000 ký tự.';
            }

            return mark(
                reason,
                reasonError,
                error
            );
        }

        function checkNotes() {
            const error = notes.value.length > 2000
                ? 'Ghi chú không được vượt quá 2000 ký tự.'
                : '';

            return mark(
                notes,
                notesError,
                error
            );
        }

        function checkConsent() {
            const error = consent.checked
                ? ''
                : 'Bạn cần tích chọn xác nhận thông tin trước khi đặt lịch.';

            const valid = mark(
                consent,
                confirmError,
                error
            );

            confirmPanel.classList.toggle(
                'has-error',
                !valid
            );

            return valid;
        }

        // Kiem tra khi nguoi dung roi khoi o.
        reason.addEventListener(
            'blur',
            checkReason
        );

        notes.addEventListener(
            'blur',
            checkNotes
        );

        // Nhap lai dung thi tu xoa loi.
        reason.addEventListener('input', () => {
            if (
                reason.getAttribute('aria-invalid') === 'true'
            ) {
                checkReason();
            }
        });

        notes.addEventListener('input', () => {
            if (
                notes.getAttribute('aria-invalid') === 'true'
            ) {
                checkNotes();
            }
        });

        // Kiem tra checkbox.
        consent.addEventListener(
            'change',
            checkConsent
        );

        // Khong gui form neu du lieu sai.
        form.addEventListener('submit', (event) => {
            const validReason = checkReason();
            const validNotes = checkNotes();
            const validConsent = checkConsent();

            if (
                !validReason
                || !validNotes
                || !validConsent
            ) {
                event.preventDefault();

                if (!validReason) {
                    reason.focus();
                } else if (!validNotes) {
                    notes.focus();
                } else {
                    consent.focus();
                }
            }
        });
    });
}
