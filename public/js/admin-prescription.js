(() => {
    const form = document.querySelector('[data-prescription-form]');
    if (!form) return;
    const container = form.querySelector('[data-prescription-items]');
    const template = form.querySelector('[data-prescription-template]');
    const add = form.querySelector('[data-add-prescription-item]');
    const message = form.querySelector('[data-prescription-limit]');
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
    container.addEventListener('change', (event) => {
        if (!event.target.matches('select[name$="[medicine_id]"]')) return;
        const unit = event.target.closest('[data-prescription-item]').querySelector('input[name$="[usage_unit]"]');
        if (unit && !unit.value.trim()) unit.value = event.target.selectedOptions[0]?.dataset.unit ?? '';
    });
    refresh();
})();
