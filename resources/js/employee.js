import { initializeResponsiveTables } from './responsive-tables.js';
import './walk-in-reception.js';
import { initializeServiceOrderPicker } from './service-order-picker.js';

initializeResponsiveTables('.f08 table[data-mobile-records]');
initializeServiceOrderPicker();

// Sau khi lưu, trở lại đúng phần của đúng lượt khám; không lưu nội dung bệnh án trong trình duyệt.
const doctorWorkspace = document.querySelector('[data-doctor-workspace]');
const doctorReturn = document.querySelector('[data-doctor-return]');
const doctorVisit = doctorWorkspace || doctorReturn;
if (doctorVisit) {
    const key = `doctor-visit-section:${doctorVisit.dataset.visitId}`;
    const remember = (section) => {
        try { sessionStorage.setItem(key, section); } catch { /* Liên kết vẫn hoạt động nếu bộ nhớ bị chặn. */ }
    };
    if (doctorWorkspace) {
        doctorWorkspace.querySelectorAll('.doctor-visit__section[id] form').forEach((form) => {
            form.addEventListener('submit', () => remember(form.closest('.doctor-visit__section').id));
        });
        let target = null;
        try { target = sessionStorage.getItem(key); sessionStorage.removeItem(key); } catch { /* Bỏ qua khi không dùng được sessionStorage. */ }
        const section = target && document.getElementById(target);
        // Khi có lỗi, để thông báo ở đầu trang được nhìn thấy trước.
        if (section && !document.querySelector('.employee-main > .portal-alert--error')) {
            const success = document.querySelector('.employee-main > .portal-alert--success');
            if (success) {
                const notice = document.createElement('p');
                notice.className = 'portal-alert portal-alert--success';
                notice.textContent = success.textContent.trim();
                section.querySelector('h2')?.after(notice);
            }
            section.scrollIntoView();
        }
    } else {
        doctorReturn.querySelector('form')?.addEventListener('submit', () => remember(doctorReturn.dataset.doctorReturn));
    }
}

// Đánh lại số thứ tự thuốc để biểu mẫu luôn gửi danh sách liên tiếp lên máy chủ.
document.querySelectorAll('[data-doctor-prescription]').forEach((form) => {
    const list = form.querySelector('[data-prescription-rows]');
    const template = form.querySelector('[data-prescription-template]');
    const add = form.querySelector('[data-prescription-add]');
    // Chỉ hiển thị giá tham khảo; máy chủ vẫn kiểm tra lại thuốc, đơn vị và giá khi lưu.
    const updateUnitPrice = (row) => {
        const medicine = row.querySelector('select[name$="[medicine_id]"]');
        const unit = row.querySelector('select[name$="[usage_unit]"]');
        const hint = row.querySelector('[data-doctor-unit-price]');
        if (!medicine || !unit || !hint) return;
        let prices = {};
        try { prices = JSON.parse(medicine.selectedOptions[0]?.dataset.prices || '{}'); } catch { /* Dữ liệu giá không hợp lệ sẽ được máy chủ từ chối. */ }
        if (!medicine.value || !unit.value) hint.textContent = 'Chọn thuốc và đơn vị có giá trong danh mục.';
        else if (Object.hasOwn(prices, unit.value) && Number.isFinite(Number(prices[unit.value]))) hint.textContent = `Đơn giá tham khảo: ${new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 2 }).format(Number(prices[unit.value]))} đ / ${unit.value}`;
        else hint.textContent = 'Thuốc chưa có giá cho đơn vị này. Vui lòng chọn đơn vị khác.';
    };
    const renumber = () => {
        const rows = [...list.querySelectorAll('[data-prescription-row]')];
        rows.forEach((row, index) => {
            row.querySelector('[data-prescription-number]').textContent = index + 1;
            row.querySelectorAll('[name^="items["]').forEach((input) => { input.name = input.name.replace(/^items\[[^\]]+\]/, `items[${index}]`); });
            row.querySelector('[data-prescription-remove]').disabled = rows.length === 1;
            updateUnitPrice(row);
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
    list.addEventListener('change', (event) => {
        const row = event.target.closest('[data-prescription-row]');
        if (row) updateUnitPrice(row);
    });
    renumber();
});

// Chỉ hiện phần hẹn tái khám khi được chọn; máy chủ vẫn kiểm tra dữ liệu.
document.querySelectorAll('[data-doctor-conclusion]').forEach((form) => {
    const toggle = form.querySelector('[data-follow-up-toggle]');
    const fields = form.querySelector('[data-follow-up-fields]');
    if (!toggle || !fields) return;
    const updateFollowUp = () => { fields.hidden = !toggle.checked; };
    toggle.addEventListener('change', updateFollowUp);
    updateFollowUp();
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
