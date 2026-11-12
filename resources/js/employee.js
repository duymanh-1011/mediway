import { initializeResponsiveTables } from './responsive-tables.js';
import './walk-in-reception.js';

initializeResponsiveTables('.f08 table[data-mobile-records]');

// Đánh lại số thứ tự thuốc để biểu mẫu luôn gửi danh sách liên tiếp lên máy chủ.
document.querySelectorAll('[data-doctor-prescription]').forEach((form) => {
    const list = form.querySelector('[data-prescription-rows]');
    const template = form.querySelector('[data-prescription-template]');
    const add = form.querySelector('[data-prescription-add]');
    const renumber = () => {
        const rows = [...list.querySelectorAll('[data-prescription-row]')];
        rows.forEach((row, index) => {
            row.querySelector('[data-prescription-number]').textContent = index + 1;
            row.querySelectorAll('[name^="items["]').forEach((input) => { input.name = input.name.replace(/^items\[[^\]]+\]/, `items[${index}]`); });
            row.querySelector('[data-prescription-remove]').disabled = rows.length === 1;
        });
        add.disabled = rows.length >= 30;
    };
    add.addEventListener('click', () => {
        if (list.querySelectorAll('[data-prescription-row]').length >= 30) return;
        list.append(template.content.cloneNode(true));
        renumber();
    });
    list.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-prescription-remove]');
        if (remove && list.querySelectorAll('[data-prescription-row]').length > 1) { remove.closest('[data-prescription-row]').remove(); renumber(); }
    });
    renumber();
});

const menu = document.querySelector('.employee-menu');
if (menu) {
    const wide = window.matchMedia('(min-width: 901px)');
    menu.open = wide.matches;
    wide.addEventListener('change', (event) => { menu.open = event.matches; });
}

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
