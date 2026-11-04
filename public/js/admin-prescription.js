(() => {
    const form = document.querySelector('[data-prescription-form]');
    if (!form) return;
    const container = form.querySelector('[data-prescription-items]');
    const template = form.querySelector('[data-prescription-template]');
    const add = form.querySelector('[data-add-prescription-item]');
    const message = form.querySelector('[data-prescription-limit]');
    let wholeUnits = [];
    try { wholeUnits = JSON.parse(form.dataset.wholeUnits || '[]'); } catch (_) {}
    let next = Math.max(-1, ...Array.from(container.querySelectorAll('[name^="items["]')).map((input) => Number(input.name.match(/^items\[(\d+)\]/)?.[1] ?? -1))) + 1;
    function refresh() {
        const rows = container.querySelectorAll('[data-prescription-item]');
        rows.forEach((row) => { row.querySelector('[data-remove-prescription-item]').disabled = rows.length === 1; });
        add.disabled = rows.length >= 30;
        message.textContent = rows.length >= 30 ? 'Mỗi đơn có tối đa 30 thuốc.' : '';
    }
    add.addEventListener('click', () => {
        if (container.querySelectorAll('[data-prescription-item]').length >= 30) return;
        const content = template.innerHTML.replaceAll('__INDEX__', String(next++));
        container.insertAdjacentHTML('beforeend', content);
        container.lastElementChild.querySelector('select')?.focus();
        refresh();
    });
    container.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-prescription-item]');
        if (!button || container.querySelectorAll('[data-prescription-item]').length <= 1) return;
        button.closest('[data-prescription-item]').remove();
        add.focus();
        refresh();
    });
    function updateQuantity(row) {
        const unit = row.querySelector('select[name$="[usage_unit]"]').value.trim();
        const input = row.querySelector('input[name$="[quantity]"]');
        const help = row.querySelector('[data-quantity-help]');
        const whole = wholeUnits.includes(unit.toLocaleLowerCase('vi-VN'));
        const value = Number(input.value);
        const invalid = whole && input.value !== '' && (!Number.isInteger(value) || value < 1);
        const error = `Số lượng theo đơn vị ${unit} phải là số nguyên từ 1 trở lên.`;
        input.min = whole ? '1' : '0.01';
        input.step = whole ? '1' : '0.01';
        input.setCustomValidity(invalid ? error : '');
        input.setAttribute('aria-invalid', invalid ? 'true' : 'false');
        help.classList.toggle('field-error', invalid);
        help.textContent = invalid ? error : whole
            ? 'Số lượng cấp phát phải là số nguyên từ 1 trở lên.'
            : 'Số lượng cấp phát tính theo đơn vị đã chọn.';
        return !invalid;
    }
    function updatePrice(row, resetUnit = false) {
        const medicine = row.querySelector('select[name$="[medicine_id]"]');
        const unit = row.querySelector('select[name$="[usage_unit]"]');
        const display = row.querySelector('[data-unit-price]');
        const total = row.querySelector('[data-line-total]');
        const quantity = Number(row.querySelector('input[name$="[quantity]"]')?.value);
        let prices = {};
        try { prices = JSON.parse(medicine.selectedOptions[0]?.dataset.prices || '{}'); } catch (_) {}
        if (resetUnit) unit.value = '';
        for (const option of unit.options) {
            if (option.value) option.disabled = !Object.hasOwn(prices, option.value);
        }
        if (unit.value && !Object.hasOwn(prices, unit.value)) unit.value = '';
        const validQuantity = updateQuantity(row);
        display.textContent = unit.value && Object.hasOwn(prices, unit.value)
            ? 'Đơn giá: ' + new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 2 }).format(Number(prices[unit.value])) + ' đ / ' + unit.value
            : 'Chọn thuốc và đơn vị đã có giá. Nếu thiếu giá, hãy cập nhật danh mục thuốc.';
        total.textContent = validQuantity && unit.value && Number.isFinite(quantity) && quantity > 0 && Object.hasOwn(prices, unit.value)
            ? 'Thành tiền dự kiến: ' + new Intl.NumberFormat('vi-VN').format(Math.round(Number(prices[unit.value]) * quantity)) + ' đ'
            : '';
    }
    container.addEventListener('change', (event) => {
        const row = event.target.closest('[data-prescription-item]');
        if (!row) return;
        if (event.target.matches('select[name$="[medicine_id]"]')) updatePrice(row, true);
        if (event.target.matches('select[name$="[usage_unit]"]')) updatePrice(row);
    });
    container.addEventListener('input', (event) => {
        if (event.target.matches('input[name$="[quantity]"]')) updatePrice(event.target.closest('[data-prescription-item]'));
    });
    form.addEventListener('submit', (event) => {
        for (const row of container.querySelectorAll('[data-prescription-item]')) {
            if (!updateQuantity(row)) {
                event.preventDefault();
                row.querySelector('input[name$="[quantity]"]').focus();
                break;
            }
        }
    });
    container.querySelectorAll('[data-prescription-item]').forEach((row) => updatePrice(row));
    refresh();
})();
