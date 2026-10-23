import test from 'node:test';
import assert from 'node:assert/strict';
import { validationMessage } from '../resources/js/admin-validation.js';

const field = (failure, values = {}) => ({ validity: { [failure]: true }, type: 'number', name: 'quantity', ...values });

test('giá và số nguyên có hướng dẫn cụ thể thay cho từ bước hoặc bội số', () => {
    assert.equal(validationMessage(field('stepMismatch', { name: 'price' }), 'Giá dịch vụ'), 'Giá dịch vụ phải chia hết cho 1.000 đồng, ví dụ 0, 1000 hoặc 2000.');
    assert.match(validationMessage(field('stepMismatch', { step: '1', min: '1' }), 'Số lượng'), /số nguyên, không nhập phần thập phân/);
    assert.match(validationMessage(field('stepMismatch', { step: '0.1', min: '35' }), 'Nhiệt độ'), /tối đa 1 chữ số sau dấu thập phân/);
    assert.match(validationMessage(field('stepMismatch', { step: '0.5', min: '35' }), 'Nhiệt độ'), /Ví dụ giá trị hợp lệ: 35/);
});

test('ngày và giờ được diễn đạt theo thời gian, không so sánh lớn nhỏ kiểu số', () => {
    assert.equal(validationMessage(field('rangeUnderflow', { type: 'date', min: '2026-10-22' }), 'Ngày tái khám'), 'Ngày tái khám không được trước 22/10/2026. Vui lòng chọn lại.');
    assert.equal(validationMessage(field('rangeOverflow', { type: 'datetime-local', max: '2026-10-22T14:30' }), 'Thời điểm đo'), 'Thời điểm đo không được sau 22/10/2026 lúc 14:30. Vui lòng chọn lại.');
    assert.match(validationMessage(field('stepMismatch', { type: 'time', step: '300' }), 'Giờ khám'), /chọn lại/);
    assert.match(validationMessage(field('rangeUnderflow', { type: 'month', min: '2026-10' }), 'Tháng'), /trước 10\/2026/);
});

test('ô bắt buộc phân biệt nhập liệu, chọn lựa và xác nhận', () => {
    assert.equal(validationMessage(field('valueMissing', { type: 'checkbox' }), 'Xác nhận xác nhận'), 'Vui lòng đánh dấu ô này trước khi tiếp tục.');
    assert.equal(validationMessage(field('valueMissing', { tagName: 'SELECT' }), 'Bác sĩ'), 'Vui lòng chọn bác sĩ.');
    assert.match(validationMessage(field('valueMissing', { type: 'file' }), 'PDF kết quả'), /chọn tệp/);
});

test('email, điện thoại và căn cước có ví dụ hoặc số chữ số cần nhập', () => {
    assert.match(validationMessage(field('typeMismatch', { type: 'email' })), /ten@example.com/);
    assert.match(validationMessage(field('patternMismatch', { name: 'phone' })), /10 chữ số.*0912345678/);
    assert.match(validationMessage(field('patternMismatch', { name: 'citizen_id_number' })), /12 chữ số/);
});

test('giới hạn và trường hợp hợp lệ không bị đổi quy tắc', () => {
    assert.equal(validationMessage(field('rangeUnderflow', { min: '0' }), 'Giá'), 'Giá không được nhỏ hơn 0.');
    assert.equal(validationMessage(field('rangeOverflow', { max: '100' }), 'Số lượng'), 'Số lượng không được vượt quá 100.');
    assert.match(validationMessage(field('tooLong', { maxLength: 120 }), 'Họ tên'), /120 ký tự/);
    assert.match(validationMessage(field('tooShort', { minLength: 8 }), 'Mật khẩu'), /8 ký tự/);
    assert.equal(validationMessage({ validity: {} }), '');
});
