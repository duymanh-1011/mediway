import { initializeResponsiveTables } from './responsive-tables.js';

initializeResponsiveTables('.f08 table[data-mobile-records]');

const menu = document.querySelector('.employee-menu');
if (menu) {
    const wide = window.matchMedia('(min-width: 901px)');
    menu.open = wide.matches;
    wide.addEventListener('change', (event) => { menu.open = event.matches; });
}

const walkInForm = document.querySelector('[data-walk-in-form]');
if (walkInForm) {
    const patientMode = walkInForm.elements.namedItem('patient_mode');
    const patientSelect = walkInForm.elements.namedItem('patient_id');
    const profileFields = walkInForm.querySelectorAll('[data-patient-field]');
    const confirmSearch = walkInForm.elements.namedItem('confirm_search');
    const newPatientDraft = new Map();
    let showingExisting = patientMode.value === 'existing';

    function syncPatientSelection() {
        const existing = patientMode.value === 'existing';
        const profile = patientSelect.selectedOptions[0]?.dataset;

        profileFields.forEach((field) => {
            if (existing) {
                if (!showingExisting) newPatientDraft.set(field, field.value);
                field.value = profile?.[field.dataset.patientField] ?? '';
            } else if (showingExisting) {
                field.value = newPatientDraft.get(field) ?? '';
            }

            if (field instanceof HTMLSelectElement) field.disabled = existing;
            else field.readOnly = existing;
        });
        patientSelect.disabled = !existing;
        confirmSearch.disabled = existing;
        showingExisting = existing;
    }

    patientSelect.required = true;
    walkInForm.addEventListener('change', (event) => {
        if (event.target.name === 'patient_mode' || event.target === patientSelect) syncPatientSelection();
    });
    window.addEventListener('pageshow', syncPatientSelection);
    document.querySelector('[data-walk-in-choose]')?.addEventListener('click', () => {
        patientMode.value = 'existing';
        syncPatientSelection();
        patientSelect.focus({ preventScroll: true });
    });
    syncPatientSelection();
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
