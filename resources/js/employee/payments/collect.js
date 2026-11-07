(function () {
            'use strict';

            const form = document.getElementById('f08-payment-form');
            const button = document.getElementById('f08-submit');

            if (!form || !button) {
                return;
            }

            const initiallyDisabled = button.disabled;
            const initialText = button.textContent;

            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    form.reportValidity();
                    return;
                }

                if (form.dataset.submitting === '1') {
                    event.preventDefault();
                    return;
                }

                form.dataset.submitting = '1';
                button.disabled = true;
                button.textContent = 'Đang ghi nhận...';
            });

            window.addEventListener('pageshow', function () {
                form.dataset.submitting = '0';
                button.disabled = initiallyDisabled;
                button.textContent = initialText;
            });
        })();
