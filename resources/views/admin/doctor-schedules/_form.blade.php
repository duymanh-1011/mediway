@if ($errors->has('schedule_conflict'))

    <div class="schedule-error">
        {{ $errors->first('schedule_conflict') }}
    </div>

@endif


<div class="form-grid">

    <div class="form-group">

        <label
            for="doctor_id"
            class="form-label"
        >
            Bác sĩ
            <span class="required">*</span>
        </label>

        <select
            id="doctor_id"
            name="doctor_id"
            class="form-control"
            required
        >

            <option value="">
                -- Chọn bác sĩ --
            </option>

            @foreach ($doctors as $doctor)

                <option
                    value="{{ $doctor->id }}"
                    data-specialty-id="{{ $doctor->specialty_id }}"
                    data-is-general="{{ $doctor->specialty?->is_general ? '1' : '0' }}"
                    @selected(
                        (string) old(
                            'doctor_id',
                            $doctorSchedule->doctor_id ?? ''
                        )
                        ===
                        (string) $doctor->id
                    )
                >
                    {{ $doctor->doctor_code }}
                    -
                    {{ $doctor->full_name }}
                </option>

            @endforeach

        </select>

        @error('doctor_id')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>


    <div class="form-group">

        <label
            for="specialty_id"
            class="form-label"
        >
            Chuyên khoa
            <span class="required">*</span>
        </label>

        <select
            id="specialty_id"
            name="specialty_id"
            class="form-control"
            required
        >

            <option value="">
                -- Chọn chuyên khoa --
            </option>

            @foreach ($specialties as $specialty)

                <option
                    value="{{ $specialty->id }}"
                    @selected(
                        (string) old(
                            'specialty_id',
                            $doctorSchedule->specialty_id ?? ''
                        )
                        ===
                        (string) $specialty->id
                    )
                >
                    {{ $specialty->specialty_code }}
                    -
                    {{ $specialty->name }}
                </option>

            @endforeach

        </select>

        @error('specialty_id')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>


    <div class="form-group">

        <label
            for="examination_room_id"
            class="form-label"
        >
            Phòng khám
            <span class="required">*</span>
        </label>

        <select
            id="examination_room_id"
            name="examination_room_id"
            class="form-control"
            required
        >

            <option value="">
                -- Chọn phòng khám --
            </option>

            @foreach (
                $examinationRooms
                as $room
            )

                <option
                    value="{{ $room->id }}"
                    @selected(
                        (string) old(
                            'examination_room_id',
                            $doctorSchedule->examination_room_id ?? ''
                        )
                        ===
                        (string) $room->id
                    )
                >
                    {{ $room->room_code }}
                    -
                    {{ $room->name }}

                    @if ($room->room_number)
                        (Phòng {{ $room->room_number }})
                    @endif
                </option>

            @endforeach

        </select>

        @error('examination_room_id')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>


    <div class="form-group">

        <label
            for="work_date"
            class="form-label"
        >
            Ngày làm việc
            <span class="required">*</span>
        </label>

        <input
            type="date"
            id="work_date"
            name="work_date"
            class="form-control"
            value="{{ old(
                'work_date',
                isset($doctorSchedule)
                    ? $doctorSchedule
                        ->work_date
                        ->format('Y-m-d')
                    : ''
            ) }}"
            required
        >

        @error('work_date')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>


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
                isset($doctorSchedule)
                    ? substr(
                        $doctorSchedule->start_time,
                        0,
                        5
                    )
                    : ''
            ) }}"
            required
        >

        @error('start_time')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
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
                isset($doctorSchedule)
                    ? substr(
                        $doctorSchedule->end_time,
                        0,
                        5
                    )
                    : ''
            ) }}"
            required
        >

        @error('end_time')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
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
                        $doctorSchedule->status ?? 'ACTIVE'
                    ) === 'ACTIVE'
                )
            >
                Đang hoạt động
            </option>

            <option
                value="INACTIVE"
                @selected(
                    old(
                        'status',
                        $doctorSchedule->status ?? ''
                    ) === 'INACTIVE'
                )
            >
                Ngừng hoạt động
            </option>

        </select>

        @error('status')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
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
        placeholder="Ghi chú về lịch làm việc..."
    >{{ old(
        'notes',
        $doctorSchedule->notes ?? ''
    ) }}</textarea>

    @error('notes')
        <div class="field-error">
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

</div>
