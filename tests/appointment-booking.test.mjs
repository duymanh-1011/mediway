import test from 'node:test';
import assert from 'node:assert/strict';
import { bookingSchedulesFor, bookingScheduleLabel, bookingSlotsFor } from '../resources/js/appointment-booking.js';

const schedule = (id, doctor, room, date = '2026-10-24', start = '09:00') => ({
    id, date, dateLabel: date.split('-').reverse().join('/'), start, end: '11:00',
    doctor: { id: doctor, code: `BS${doctor}`, name: doctor === '1' ? 'An' : 'Bình' },
    room: { id: room, code: `PK${room}`, name: `Phòng ${room}` },
    specialty: { id: '1', code: 'CK1', name: 'Nội khoa' },
    slots: [{ id: `slot-${id}`, start, end: '09:30', remaining: 2 }],
});
const schedules = [schedule('1', '2', '1'), schedule('2', '1', '2', '2026-10-25'), schedule('3', '1', '1')];

test('ba hướng chỉ lấy lịch thuộc bác sĩ, phòng hoặc khoa đã chọn', () => {
    assert.deepEqual(bookingSchedulesFor(schedules, 'doctor', '1').map(s => s.id), ['3', '2']);
    assert.deepEqual(bookingSchedulesFor(schedules, 'room', '1').map(s => s.id), ['3', '1']);
    assert.deepEqual(bookingSchedulesFor(schedules, 'specialty', '1').map(s => s.id), ['3', '1', '2']);
    assert.deepEqual(bookingSchedulesFor(schedules, 'room', 'missing'), []);
    assert.deepEqual(schedules.map(s => s.id), ['1', '2', '3']);
});

test('nhãn ca làm việc ưu tiên thông tin đúng hướng chọn', () => {
    assert.ok(bookingScheduleLabel(schedules[0], 'doctor').startsWith('BS2 - Bình |'));
    assert.ok(bookingScheduleLabel(schedules[0], 'room').startsWith('PK1 - Phòng 1 | BS2 - Bình | Nội khoa |'));
    assert.ok(bookingScheduleLabel(schedules[0], 'specialty').startsWith('CK1 - Nội khoa | PK1 - Phòng 1 |'));
});

test('hướng bác sĩ chọn thẳng khung giờ còn hai hướng khác phải chọn ca trước', () => {
    const doctor = bookingSchedulesFor(schedules, 'doctor', '1');
    assert.deepEqual(bookingSlotsFor(doctor, 'doctor').map(s => s.value), ['slot-3', 'slot-2']);
    assert.match(bookingSlotsFor(doctor, 'doctor')[0].label, /^24\/10\/2026 \| 09:00 - 09:30/);
    assert.deepEqual(bookingSlotsFor(schedules, 'room'), []);
    assert.deepEqual(bookingSlotsFor(schedules, 'specialty', '3').map(s => s.value), ['slot-3']);
    assert.deepEqual(bookingSlotsFor(doctor, 'room', '1'), []);
});

test('thứ tự theo thời gian của bác sĩ không phụ thuộc thứ tự dữ liệu đầu vào', () => {
    const before = schedule('early', '1', '1', '2026-10-24', '08:00');
    assert.equal(bookingSchedulesFor([...schedules, before], 'doctor', '1')[0].id, 'early');
});
