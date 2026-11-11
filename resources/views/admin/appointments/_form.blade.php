@php
    $selectedPatientId = old('patient_id', $appointment->patient_id ?? '');
    $selectedSlotId = old('appointment_time_slot_id', $appointment->appointment_time_slot_id ?? '');
    $criteria = [
        'doctor' => ['label' => 'Bác sĩ', 'field' => 'booking_doctor_id', 'code' => 'doctor_code', 'name' => 'full_name', 'items' => $schedules->pluck('doctor')->unique('id')->sortBy('full_name')],
        'room' => ['label' => 'Phòng khám', 'field' => 'booking_room_id', 'code' => 'room_code', 'name' => 'name', 'items' => $schedules->pluck('examinationRoom')->unique('id')->sortBy('name')],
        'specialty' => ['label' => 'Chuyên khoa', 'field' => 'booking_specialty_id', 'code' => 'specialty_code', 'name' => 'name', 'items' => $schedules->pluck('specialty')->unique('id')->sortBy('name')],
    ];
    $bookingSchedules = $schedules->map(fn ($schedule) => [
        'id' => (string) $schedule->id, 'date' => $schedule->work_date->format('Y-m-d'),
        'dateLabel' => $schedule->work_date->format('d/m/Y'),
        'start' => substr($schedule->start_time, 0, 5), 'end' => substr($schedule->end_time, 0, 5),
        'doctor' => ['id' => (string) $schedule->doctor_id, 'code' => $schedule->doctor->doctor_code, 'name' => $schedule->doctor->full_name],
        'room' => ['id' => (string) $schedule->examination_room_id, 'code' => $schedule->examinationRoom->room_code, 'name' => $schedule->examinationRoom->name],
        'specialty' => ['id' => (string) $schedule->specialty_id, 'code' => $schedule->specialty->specialty_code, 'name' => $schedule->specialty->name],
        'slots' => $schedule->appointmentTimeSlots->filter(fn ($slot) => $slot->is_selectable)->map(fn ($slot) => [
            'id' => (string) $slot->id, 'start' => substr($slot->start_time, 0, 5), 'end' => substr($slot->end_time, 0, 5),
            'remaining' => max(0, (int) $slot->max_patients - (int) $slot->booked_count),
        ])->values()->all(),
    ])->values()->all();
@endphp

<div data-appointment-booking data-selected-slot="{{ $selectedSlotId }}">
    <div class="form-group">
        <label for="patient_id" class="form-label">Bệnh nhân <span class="required">*</span></label>
        <select id="patient_id" name="patient_id" class="form-control" required>
            <option value="">-- Chọn bệnh nhân --</option>
            @foreach ($patients as $patient)
                <option value="{{ $patient->id }}" @selected((string) $selectedPatientId === (string) $patient->id)>{{ $patient->patient_code }} - {{ $patient->full_name }}</option>
            @endforeach
        </select>
        @error('patient_id')<div class="field-error">{{ $message }}</div>@enderror
    </div>

    <fieldset class="admin-booking-method">
        <legend>Đặt lịch theo <span class="required">*</span></legend>
        <div class="admin-booking-choices">
            @foreach ($criteria as $kind => $criterion)
                <div class="form-group" data-booking-choice>
                    <label for="{{ $criterion['field'] }}">{{ $criterion['label'] }}</label>
                    <select id="{{ $criterion['field'] }}" name="{{ $criterion['field'] }}" class="form-control" data-booking-kind="{{ $kind }}" data-lookup-native>
                        <option value="">-- Chọn {{ mb_strtolower($criterion['label']) }} --</option>
                        @foreach ($criterion['items'] as $entity)
                            <option value="{{ $entity->id }}" @selected((string) old($criterion['field'], '') === (string) $entity->id)>{{ $entity->{$criterion['code']} }} - {{ $entity->{$criterion['name']} }}</option>
                        @endforeach
                    </select>
                    @error($criterion['field'])<div class="field-error">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>
        <div class="field-error" data-booking-error hidden aria-live="polite"></div>
        <button type="button" class="admin-btn admin-btn-secondary" data-booking-reset>Đổi cách chọn</button>
    </fieldset>

    <div class="form-grid">
        <div class="form-group" data-booking-schedule-field>
            <label for="doctor_schedule_id"><span data-booking-schedule-title>Ca làm việc</span> <span class="required">*</span></label>
            <select id="doctor_schedule_id" class="form-control" required data-lookup-native>
                <option value="">-- Chọn bác sĩ, phòng khám hoặc chuyên khoa --</option>
            </select>
        </div>
        <div class="form-group" data-booking-slot-field>
            <label for="appointment_time_slot_id">Khung giờ khám <span class="required">*</span></label>
            <select id="appointment_time_slot_id" name="appointment_time_slot_id" class="form-control" required data-lookup-native>
                <option value="">-- Chọn lịch khám trước --</option>
            </select>
            @error('appointment_time_slot_id')<div class="field-error">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="form-group">
        <label for="reason_for_visit">Lý do khám <span class="required">*</span></label>
        <textarea id="reason_for_visit" name="reason_for_visit" class="form-control" rows="4" maxlength="2000" required>{{ old('reason_for_visit', $appointment->reason_for_visit ?? '') }}</textarea>
        @error('reason_for_visit')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="form-group">
        <label for="patient_notes">Ghi chú bệnh nhân</label>
        <textarea id="patient_notes" name="patient_notes" class="form-control" rows="3" maxlength="2000">{{ old('patient_notes', $appointment->patient_notes ?? '') }}</textarea>
        @error('patient_notes')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <script type="application/json" data-booking-schedules>@json($bookingSchedules)</script>
</div>
