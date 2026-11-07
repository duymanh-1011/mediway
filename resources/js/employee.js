import { initializeResponsiveTables } from './responsive-tables.js';

initializeResponsiveTables('.f08 table[data-mobile-records]');

const menu = document.querySelector('.employee-menu');
if (menu) {
    const wide = window.matchMedia('(min-width: 901px)');
    menu.open = wide.matches;
    wide.addEventListener('change', (event) => { menu.open = event.matches; });
}

document.querySelector('[data-f08-print]')?.addEventListener('click', () => window.print());

// Cập nhật thời điểm khi in, kể cả khi trang xem trước đã mở từ trước.
window.addEventListener('beforeprint', () => {
    const time = document.querySelector('[data-result-print-time]');
    if (time) time.textContent = new Intl.DateTimeFormat('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh', dateStyle: 'short', timeStyle: 'short' }).format(new Date());
});

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

// Mỗi lần thêm hoặc xóa, đánh lại tên trường để máy chủ nhận đủ các dòng.
document.querySelectorAll('[data-detail-editor]').forEach((editor) => {
    const list = editor.querySelector('[data-detail-list]');
    const template = editor.querySelector('[data-detail-template]');
    const add = editor.querySelector('[data-add-detail]');
    const renumber = () => {
        const rows = [...list.querySelectorAll('[data-detail-row]')];
        rows.forEach((row, index) => row.querySelectorAll('input, textarea').forEach((input) => {
            const field = input.dataset.field || input.name.match(/\[(name|value)\]$/)?.[1];
            if (field) input.name = `details[${index}][${field}]`;
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
        const button = event.target.closest('[data-remove-detail]');
        if (!button) return;
        button.closest('[data-detail-row]').remove();
        renumber();
        add.focus();
    });
    renumber();
});
