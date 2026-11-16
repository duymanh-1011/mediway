import test from 'node:test';
import assert from 'node:assert/strict';
import { bookingChangeKind, bookingOptionMatches, bookingSlotMatches } from '../resources/js/patient-booking-flow.js';

test('tìm chuyên khoa, bác sĩ và phòng khám không phân biệt dấu tiếng Việt', () => {
    assert.equal(bookingOptionMatches({ name: 'Da Liễu', detail: 'Chuyên khoa' }, 'da lieu'), true);
    assert.equal(bookingOptionMatches({ name: 'Phòng khám Nội 01', detail: 'Tòa A' }, 'noi 01'), true);
    assert.equal(bookingOptionMatches({ name: 'Da Liễu', detail: 'Xem bác sĩ', search: 'ngứa, mụn, dị ứng' }, 'di ung'), true);
    assert.equal(bookingOptionMatches({ kind: 'specialty', name: 'Tiêu hóa', detail: 'Khám đường ruột' }, 'đại tràng'), true);
    assert.equal(bookingOptionMatches({ kind: 'specialty', name: 'Tiêu hóa', detail: 'Khám đường ruột' }, 'ruột già'), true);
    assert.equal(bookingOptionMatches({ name: 'Bác sĩ Nguyễn An', detail: 'Tai Mũi Họng' }, 'tim mach'), false);
});

test('đổi lựa chọn mở lại đúng phương thức đã chọn trước đó', () => {
    assert.equal(bookingChangeKind({ kind: 'specialty', id: '2' }), 'specialty');
    assert.equal(bookingChangeKind({ kind: 'doctor', id: '7' }), 'doctor');
    assert.equal(bookingChangeKind({ kind: 'room', id: '9' }), 'room');
    assert.equal(bookingChangeKind(null), null);
});

test('lọc khung giờ theo phương thức và lựa chọn đã chọn', () => {
    const slot = { doctorId: '7', specialtyId: '4', roomId: '9', date: '2026-11-18' };
    assert.equal(bookingSlotMatches(slot, { kind: 'doctor', id: '7' }), true);
    assert.equal(bookingSlotMatches(slot, { kind: 'specialty', id: '4' }), true);
    assert.equal(bookingSlotMatches(slot, { kind: 'room', id: '9' }), true);
    assert.equal(bookingSlotMatches(slot, { kind: 'other', id: '9' }), false);
});
