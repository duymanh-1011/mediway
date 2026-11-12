import test from 'node:test';
import assert from 'node:assert/strict';
import { initializeServiceOrderPicker } from '../resources/js/service-order-picker.js';

function serviceCard(name, codeAndGroup) {
    const checkbox = { name: 'medical_service_ids[]', checked: false };
    const children = {
        '.service-checkbox': checkbox,
        '.service-name': { textContent: name },
        '.service-code': { textContent: codeAndGroup },
    };
    return { checkbox, hidden: false, querySelector: (selector) => children[selector] };
}

test('lọc dịch vụ theo từ khóa và giữ các dịch vụ đã chọn', () => {
    const first = serviceCard('Điện tâm đồ', 'DVTDCN001 • Thăm dò chức năng');
    const second = serviceCard('Công thức máu', 'DVXN001 • Xét nghiệm');
    const search = Object.assign(new EventTarget(), { value: '' });
    const field = { hidden: true, querySelector: () => search };
    const empty = { hidden: true };
    const counter = {};
    const children = {
        '[data-service-search-field]': field,
        '[data-service-search-empty]': empty,
        '#selected-count': counter,
    };
    const picker = Object.assign(new EventTarget(), {
        querySelector: (selector) => children[selector],
        querySelectorAll: () => [first, second],
    });

    first.checkbox.checked = true;
    initializeServiceOrderPicker({ querySelector: () => picker });
    assert.equal(field.hidden, false);
    assert.equal(counter.textContent, '1');

    const find = (value) => {
        search.value = value;
        search.dispatchEvent(new Event('input'));
    };

    find('DVXN001 xet nghiem');
    assert.equal(first.hidden, true);
    assert.equal(second.hidden, false);
    assert.equal(first.checkbox.checked, true);
    assert.equal(counter.textContent, '1');

    find('dien tam do');
    assert.equal(first.hidden, false);
    assert.equal(second.hidden, true);

    find('khong ton tai');
    assert.equal(empty.hidden, false);
    find('');
    assert.equal(empty.hidden, true);
    assert.equal(first.hidden, false);
    assert.equal(second.hidden, false);

    const enter = Object.assign(new Event('keydown', { cancelable: true }), { key: 'Enter' });
    search.dispatchEvent(enter);
    assert.equal(enter.defaultPrevented, true);
});
