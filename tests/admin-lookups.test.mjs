import test from 'node:test';
import assert from 'node:assert/strict';
import { matchingOptions, normalizeLookup } from '../resources/js/admin-lookups.js';

test('tìm bệnh ở cuối 500 lựa chọn, hỗ trợ gõ không dấu', () => {
    const options = Array.from({length: 500}, (_, index) => ({value: String(index + 1), label: `Bệnh đường hô hấp ${index + 1}`}));
    assert.equal(matchingOptions(options, 'benh duong ho hap 500')[0].value, '500');
    assert.equal(matchingOptions(options, '').length, 20);
    assert.equal(normalizeLookup('Đường HUYẾT'), 'duong huyet');
});
test('không gợi ý placeholder, mục disabled hoặc đã bị luồng phụ thuộc ẩn', () => {
    const options = [{value: '', label: 'Tất cả'}, {value:'1', label:'Bệnh 1', disabled:true}, {value:'2', label:'Bệnh 2', hidden:true}, {value:'3', label:'Bệnh 3'}];
    assert.deepEqual(matchingOptions(options, '').map(option => option.value), ['3']);
});
