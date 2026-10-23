import test from 'node:test';
import assert from 'node:assert/strict';
import { doctorMatchesSpecialty, initializeDoctorSpecialtyPairs } from '../resources/js/doctor-specialty.js';

test('specialists match only their assigned specialty', () => {
    assert.equal(doctorMatchesSpecialty({specialtyId:'2', isGeneral:'0'}, '2'), true);
    assert.equal(doctorMatchesSpecialty({specialtyId:'2', isGeneral:'0'}, '3'), false);
});

test('general doctors match every specialty but unassigned doctors match none', () => {
    for (const id of ['1', '2', '300']) assert.equal(doctorMatchesSpecialty({specialtyId:'1', isGeneral:'1'}, id), true);
    assert.equal(doctorMatchesSpecialty({specialtyId:'', isGeneral:'0'}, '2'), false);
    assert.equal(doctorMatchesSpecialty(null, '2'), false);
});

class Select extends EventTarget {
    constructor(options) {
        super();
        this.options = [{value:'', dataset:{}}, ...options];
        this.value = '';
    }
    get selectedOptions() { return this.options.filter(option => option.value === this.value); }
    choose(value) { this.value = value; this.dispatchEvent(new Event('change')); }
    get visible() { return this.options.filter(option => option.value && !option.hidden && !option.disabled).map(option => option.value); }
}

function pair(method = 'post') {
    const doctor = new Select([
        {value:'d1', dataset:{specialtyId:'s1', isGeneral:'0'}},
        {value:'d2', dataset:{specialtyId:'s2', isGeneral:'0'}},
        {value:'dg', dataset:{specialtyId:'sg', isGeneral:'1'}},
        {value:'unknown', dataset:{specialtyId:'', isGeneral:'0'}},
    ]);
    const specialty = new Select(['s1','s2','sg'].map(value => ({value, dataset:{}})));
    const form = new EventTarget();
    form.dataset = {};
    form.method = method;
    form.querySelector = selector => selector.includes('doctor_id') ? doctor : specialty;
    initializeDoctorSpecialtyPairs({querySelectorAll: () => [form]});
    return {doctor, specialty};
}

test('choosing a specialty narrows doctors and clears a mismatched previous doctor', () => {
    const {doctor, specialty} = pair();
    specialty.choose('s1');
    assert.deepEqual(doctor.visible, ['d1','dg']);
    doctor.choose('d1');
    assert.equal(specialty.value, 's1');
    specialty.choose('s2');
    assert.equal(doctor.value, '');
    assert.deepEqual(doctor.visible, ['d2','dg']);
});

test('choosing a doctor selects their specialty and general practice unlocks every specialty', () => {
    const {doctor, specialty} = pair();
    doctor.choose('d2');
    assert.equal(specialty.value, 's2');
    assert.deepEqual(specialty.visible, ['s2']);
    doctor.choose('dg');
    assert.deepEqual(specialty.visible, ['s1','s2','sg']);
    specialty.choose('s1');
    assert.equal(doctor.value, 'dg');
    doctor.choose('');
    specialty.choose('');
    assert.deepEqual(doctor.visible, ['d1','d2','dg','unknown']);
});

test('history filters do not silently add a specialty when selecting only a doctor', () => {
    const {doctor, specialty} = pair('get');
    doctor.choose('d1');
    assert.equal(specialty.value, '');
    assert.deepEqual(specialty.visible, ['s1']);
    specialty.choose('s1');
    doctor.choose('d2');
    assert.equal(specialty.value, '');
    assert.deepEqual(specialty.visible, ['s2']);
});
