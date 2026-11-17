const form = document.querySelector('#f08-payment-form');

if (form) {
    const items = [...form.querySelectorAll('[data-payment-item]:not(:disabled)')];
    const selectAll = form.querySelector('[data-payment-all]');
    const button = form.querySelector('#f08-submit');
    const cashFields = form.querySelector('[data-cash-fields]');
    const confirmation = cashFields.querySelector('[name="confirm_payment"]');
    const totals = document.querySelectorAll('[data-payment-total]');
    const count = document.querySelector('[data-payment-count]');
    const selectedCode = document.querySelector('[data-payment-code]');
    const summaryMethod = document.querySelector('[data-payment-method]');
    const qrInstructions = form.querySelector('[data-qr-fields]');
    const formatAmount = new Intl.NumberFormat('vi-VN', {maximumFractionDigits: 0});

    function update() {
        const chosen = items.filter(item => item.type === 'hidden' || item.checked);
        const cents = chosen.reduce((sum, item) => sum + Math.round(Number(item.dataset.amount) * 100), 0);
        const isQr = form.elements.payment_method.value === 'QR';

        totals.forEach(field => { field.textContent = formatAmount.format(cents / 100) + ' đ'; });
        count.textContent = chosen.length;
        selectedCode.textContent = chosen.length === 1 ? chosen[0].dataset.code : '—';
        summaryMethod.textContent = isQr ? 'Chuyển khoản QR' : 'Tiền mặt';
        cashFields.hidden = cashFields.disabled = isQr;
        qrInstructions.hidden = !isQr;
        confirmation.required = !isQr;
        button.disabled = chosen.length === 0 || chosen.length > 100;
        button.textContent = isQr ? 'Tạo mã QR' : 'Xác nhận thu tiền';

        if (selectAll) {
            selectAll.checked = chosen.length === items.length && items.length > 0;
            selectAll.indeterminate = chosen.length > 0 && chosen.length < items.length;
        }
    }

    if (selectAll && items.length > 1) {
        form.querySelector('[data-payment-all-row]').hidden = false;
    }

    form.addEventListener('change', event => {
        if (event.target === selectAll) {
            items.forEach(item => { item.checked = selectAll.checked; });
        }
        if (event.target === selectAll || event.target.matches('[data-payment-item], [name="payment_method"]')) {
            confirmation.checked = false;
            update();
        }
    });

    form.addEventListener('submit', event => {
        if (button.disabled) {
            event.preventDefault();
            return;
        }
        button.disabled = true;
        button.textContent = 'Đang xử lý…';
    });

    window.addEventListener('pageshow', update);
    update();
}