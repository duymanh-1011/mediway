import { initializeResponsiveTables } from './responsive-tables.js';

initializeResponsiveTables('.f08 table[data-mobile-records]');

const menu = document.querySelector('.employee-menu');
if (menu) {
    const wide = window.matchMedia('(min-width: 901px)');
    menu.open = wide.matches;
    wide.addEventListener('change', (event) => { menu.open = event.matches; });
}

document.querySelector('[data-f08-print]')?.addEventListener('click', () => window.print());

document.querySelectorAll('.paper table').forEach((table) => {
    let region = table.closest('.medicine-table-wrap, .ms-table-wrap');
    if (!region) {
        region = document.createElement('div');
        region.className = 'receipt-table-scroll';
        table.before(region);
        region.append(table);
    }
    region.tabIndex = 0;
    region.setAttribute('role', 'region');
    region.setAttribute('aria-label', 'Bảng chi tiết phiếu thu, có thể cuộn ngang');
});

// Thêm hoặc xóa dòng chỉ số; đánh số lại để máy chủ nhận đúng danh sách.
document.querySelectorAll('[data-parameter-editor]').forEach((editor) => {
    const list = editor.querySelector('[data-parameter-list]');
    const template = editor.querySelector('[data-parameter-template]');
    const add = editor.querySelector('[data-add-parameter]');
    const renumber = () => {
        const rows = [...list.querySelectorAll('[data-parameter-row]')];
        rows.forEach((row, index) => row.querySelectorAll('input').forEach((input) => {
            const field = input.dataset.field || input.name.match(/\[(name|value|unit)\]$/)?.[1];
            if (field) input.name = `parameters[${index}][${field}]`;
        }));
        add.disabled = rows.length >= 30;
    };
    add.addEventListener('click', () => {
        if (list.children.length >= 30) return;
        list.append(template.content.cloneNode(true));
        renumber();
        list.lastElementChild.querySelector('input')?.focus();
    });
    list.addEventListener('click', (event) => {
        if (!event.target.closest('[data-remove-parameter]')) return;
        event.target.closest('[data-parameter-row]').remove();
        renumber();
        add.focus();
    });
    renumber();
});
