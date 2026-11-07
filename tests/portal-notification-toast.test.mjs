import assert from 'node:assert/strict';
import test from 'node:test';
import { initializeNotificationToast } from '../resources/js/portal-notification-toast.js';

function fixture() {
    const close = new EventTarget();
    const title = {};
    const category = {};
    const detail = {};
    const key = {};
    const announcement = {};
    const toast = {
        hidden: true,
        querySelector: (selector) => ({
            '[data-toast-close]': close,
            '[data-toast-title]': title,
            '[data-toast-category]': category,
            '[data-toast-detail]': detail,
            '[data-toast-key]': key,
        })[selector],
    };
    const doc = { querySelector: (selector) => selector === '[data-notification-toast]' ? toast : announcement };
    const storage = new Map();
    const win = { localStorage: { getItem: (name) => storage.get(name) ?? null, setItem: (name, value) => storage.set(name, value) } };
    const bell = { dataset: { notificationAccount: '15' }, getClientRects: () => [{}], focus: () => {} };
    const sync = initializeNotificationToast(bell, doc, win);
    return { sync, toast, key, title, detail, close: () => close.dispatchEvent(new Event('click')) };
}

const alert = (key, title, category = 'Kết quả') => ({ key, title, category });

test('popup shows new alerts once and promotes the active exam call', () => {
    const f = fixture();
    const older = alert('older', 'Có đơn thuốc');
    const result = alert('result', 'Có kết quả dịch vụ');
    const payment = alert('payment', 'Khoản cần thanh toán', 'Thanh toán');
    const call = alert('call', 'Đã đến lượt khám', 'Khám bệnh');
    f.sync({ unread: [result, older] });
    assert.equal(f.key.value, 'result');
    assert.equal(f.toast.hidden, false);
    f.sync({ unread: [payment, result, older] });
    assert.equal(f.key.value, 'result');
    f.sync({ unread: [call, payment, result, older], active_call: { key: 'call', message: 'Vui lòng đến phòng khám.' } });
    assert.equal(f.title.textContent, 'Đã đến lượt khám');
    assert.equal(f.detail.textContent, 'Vui lòng đến phòng khám.');
    f.sync({ unread: [payment, result, older] });
    assert.equal(f.key.value, 'payment');
    f.close();
    assert.equal(f.key.value, 'result');
    f.close();
    f.sync({ unread: [payment, result, older] });
    assert.equal(f.toast.hidden, true);
});
