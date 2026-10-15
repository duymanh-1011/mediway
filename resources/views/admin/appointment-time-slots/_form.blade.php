@if ($errors->has('slot_conflict'))

    <div class="slot-error">
        {{ $errors->first('slot_conflict') }}
    </div>

@endif


<div class="schedule-summary">

    <strong>Lịch làm việc:</strong>

    {{ $doctorSchedule->doctor?->doctor_code }}
    -
    {{ $doctorSchedule->doctor?->full_name }}

    |

    {{ $doctorSchedule->work_date->format('d/m/Y') }}

    |

    {{ substr($doctorSchedule->start_time, 0, 5) }}
    -
    {{ substr($doctorSchedule->end_time, 0, 5) }}

</div>


<div class="form-grid">

    <div class="form-group">

        <label
            for="start_time"
            class="form-label"
        >
            Giờ bắt đầu
            <span class="required">*</span>
        </label>

        <input
            type="time"
            id="start_time"
            name="start_time"
            class="form-control"
            value="{{ old(
                'start_time',
                isset($appointmentTimeSlot)
                    ? substr(
                        $appointmentTimeSlot->start_time,
                        0,
                        5
                    )
                    : ''
            ) }}"
            required
        >

        @error('start_time')
            <div class="field-error">
                {{ $message }}
            </div>
        @enderror

    </div>


    <div class="form-group">

        <label
            for="end_time"
            class="form-label"
        >
            Giờ kết thúc
            <span class="required">*</span>
        </label>

        <input
            type="time"
            id="end_time"
            name="end_time"
            class="form-control"
            value="{{ old(
                'end_time',
                isset($appointmentTimeSlot)
                    ? substr(
                        $appointmentTimeSlot->end_time,
                        0,
                        5
                    )
                    : ''
            ) }}"
            required
        >

        @error('end_time')
            <div class="field-error">
                {{ $message }}
            </div>
        @enderror

    </div>


    <div class="form-group">

        <label
            for="max_patients"
            class="form-label"
        >
            Số bệnh nhân tối đa
            <span class="required">*</span>
        </label>

        <input
            type="number"
            id="max_patients"
            name="max_patients"
            class="form-control"
            min="1"
            value="{{ old(
                'max_patients',
                $appointmentTimeSlot->max_patients ?? 1
            ) }}"
            required
        >

        @error('max_patients')
            <div class="field-error">
                {{ $message }}
            </div>
        @enderror

    </div>


    <div class="form-group">

        <label
            for="status"
            class="form-label"
        >
            Trạng thái
            <span class="required">*</span>
        </label>

        <select
            id="status"
            name="status"
            class="form-control"
            required
        >

            <option
                value="ACTIVE"
                @selected(
                    old(
                        'status',
                        $appointmentTimeSlot->status ?? 'ACTIVE'
                    ) === 'ACTIVE'
                )
            >
                ACTIVE
            </option>

            <option
                value="INACTIVE"
                @selected(
                    old(
                        'status',
                        $appointmentTimeSlot->status ?? ''
                    ) === 'INACTIVE'
                )
            >
                INACTIVE
            </option>

        </select>

        @error('status')
            <div class="field-error">
                {{ $message }}
            </div>
        @enderror

    </div>

</div>


<div class="form-group">

    <label
        for="notes"
        class="form-label"
    >
        Ghi chú
    </label>

    <textarea
        id="notes"
        name="notes"
        class="form-control"
        rows="4"
        placeholder="Ghi chú cho khung giờ..."
    >{{ old(
        'notes',
        $appointmentTimeSlot->notes ?? ''
    ) }}</textarea>

    @error('notes')
        <div class="field-error">
            {{ $message }}
        </div>
    @enderror

</div>