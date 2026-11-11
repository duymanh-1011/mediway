const collator = new Intl.Collator('vi', { numeric: true, sensitivity: 'base' });
const entityText = (entity) => `${entity.code} - ${entity.name}`;

export function bookingSchedulesFor(schedules, kind, value) {
    const keys = kind === 'room' ? ['doctor', 'date', 'start', 'specialty']
        : kind === 'specialty' ? ['room', 'doctor', 'date', 'start'] : ['date', 'start', 'specialty', 'room'];
    return schedules.filter((schedule) => String(schedule[kind]?.id) === String(value)).sort((a, b) => {
        for (const key of keys) {
            const compared = collator.compare(a[key]?.name ?? a[key], b[key]?.name ?? b[key]);
            if (compared) return compared;
        }
        return collator.compare(a.id, b.id);
    });
}

export function bookingScheduleLabel(schedule, kind) {
    const time = `${schedule.dateLabel} | ${schedule.start} - ${schedule.end}`;
    if (kind === 'room') return [entityText(schedule.room), entityText(schedule.doctor), schedule.specialty.name, time].join(' | ');
    if (kind === 'specialty') return [entityText(schedule.specialty), entityText(schedule.room), entityText(schedule.doctor), time].join(' | ');
    return [entityText(schedule.doctor), time, entityText(schedule.room), schedule.specialty.name].join(' | ');
}

export function bookingSlotsFor(schedules, kind, scheduleId = '') {
    return schedules.filter((schedule) => kind === 'doctor' || schedule.id === String(scheduleId)).flatMap((schedule) => schedule.slots.map((slot) => ({
        value: String(slot.id), scheduleId: schedule.id,
        label: `${kind === 'doctor' ? `${schedule.dateLabel} | ` : ''}${slot.start} - ${slot.end} | Còn ${slot.remaining} chỗ${kind === 'doctor' ? ` | ${entityText(schedule.room)} | ${schedule.specialty.name}` : ''}`,
    })));
}

export function initializeAppointmentBooking(root = document) {
    root.querySelectorAll('[data-appointment-booking]:not([data-booking-ready])').forEach((widget) => {
        widget.dataset.bookingReady = 'true';
        const schedules = JSON.parse(widget.querySelector('[data-booking-schedules]').textContent);
        const filters = [...widget.querySelectorAll('[data-booking-kind]')];
        const scheduleSelect = widget.querySelector('#doctor_schedule_id');
        const slotSelect = widget.querySelector('#appointment_time_slot_id');
        const scheduleField = widget.querySelector('[data-booking-schedule-field]');
        const scheduleLabel = widget.querySelector('[data-booking-schedule-title]');
        const reset = widget.querySelector('[data-booking-reset]');
        const error = widget.querySelector('[data-booking-error]');
        let initialized = false;
        const setOptions = (select, choices, placeholder, selected = '') => {
            select.replaceChildren(new Option(placeholder, ''), ...choices.map((choice) => new Option(choice.label, choice.value)));
            select.value = choices.some((choice) => choice.value === String(selected)) ? String(selected) : '';
        };
        const clearError = (select) => {
            if (!initialized) return;
            if (select.nextElementSibling?.classList.contains('field-error')) select.nextElementSibling.hidden = true;
            select.removeAttribute('aria-invalid');
        };
        const render = (selectedSlot = '', selectedSchedule = '') => {
            const active = filters.find((select) => select.value);
            const kind = active?.dataset.bookingKind || '';
            widget.dataset.bookingMode = kind;
            filters.forEach((select) => {
                select.disabled = !!active && select !== active;
                select.required = select === active;
                select.closest('[data-booking-choice]').classList.toggle('is-disabled', select.disabled);
                clearError(select);
            });
            reset.disabled = !active;
            scheduleField.hidden = kind === 'doctor';
            scheduleSelect.disabled = !active || kind === 'doctor';
            scheduleSelect.required = !!active && kind !== 'doctor';
            scheduleLabel.textContent = kind === 'room' ? 'Ca làm việc tại phòng khám'
                : kind === 'specialty' ? 'Lịch làm việc của chuyên khoa' : 'Ca làm việc';
            const matches = active ? bookingSchedulesFor(schedules, kind, active.value) : [];
            setOptions(scheduleSelect, matches.map((schedule) => ({ value: schedule.id, label: bookingScheduleLabel(schedule, kind) })),
                active ? '-- Chọn ca làm việc --' : '-- Chọn bác sĩ, phòng khám hoặc chuyên khoa --', selectedSchedule);
            const slots = bookingSlotsFor(matches, kind, scheduleSelect.value);
            const canSelectSlot = !!active && (kind === 'doctor' || !!scheduleSelect.value);
            setOptions(slotSelect, slots, !active ? '-- Chọn lịch khám trước --'
                : !canSelectSlot ? '-- Chọn ca làm việc trước --' : slots.length ? '-- Chọn khung giờ khám --' : '-- Không còn khung giờ có thể đặt --', selectedSlot);
            slotSelect.disabled = !canSelectSlot || !slots.length;
            clearError(scheduleSelect); clearError(slotSelect);
            error.hidden = true;
            initialized = true;
        };
        filters.forEach((select) => select.addEventListener('change', () => {
            filters.forEach((other) => { if (other !== select) other.value = ''; });
            render();
        }));
        scheduleSelect.addEventListener('change', () => render('', scheduleSelect.value));
        reset.addEventListener('click', () => {
            filters.forEach((select) => { select.value = ''; });
            render(); filters[0].focus();
        });
        widget.closest('form').addEventListener('submit', (event) => {
            const active = filters.filter((select) => select.value);
            const alreadyPrevented = event.defaultPrevented;
            if (active.length !== 1) {
                event.preventDefault(); error.hidden = false;
                error.textContent = 'Vui lòng chọn một bác sĩ, phòng khám hoặc chuyên khoa.';
                if (!alreadyPrevented) filters[0].focus();
            } else if (!slotSelect.value) {
                event.preventDefault();
                if (!scheduleSelect.disabled && !scheduleSelect.value) {
                    if (!alreadyPrevented) scheduleSelect.focus();
                } else if (!slotSelect.disabled) {
                    if (!alreadyPrevented) slotSelect.focus();
                } else {
                    error.hidden = false;
                    error.textContent = 'Không còn khung giờ có thể đặt cho lựa chọn hiện tại.';
                    if (!alreadyPrevented) active[0].focus();
                }
            }
        });
        const selectedSlot = widget.dataset.selectedSlot;
        const previousSchedule = schedules.find((schedule) => schedule.slots.some((slot) => slot.id === selectedSlot));
        const restored = filters.find((select) => select.value);
        if (!restored && previousSchedule) filters.find((select) => select.dataset.bookingKind === 'doctor').value = previousSchedule.doctor.id;
        const active = filters.find((select) => select.value);
        filters.forEach((select) => { if (select !== active) select.value = ''; });
        render(selectedSlot, previousSchedule?.id || '');
    });
}
