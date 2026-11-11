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
            data-schedule-room data-lookup-native
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
                    data-specialty-id="{{ $room->specialty_id }}"
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

        <p class="form-help" data-room-specialty-help>Chọn chuyên khoa để xem phòng phù hợp. Phòng chưa gán chuyên khoa cần được cập nhật trong danh mục phòng khám.</p>
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


    @if (! isset($doctorSchedule))
    <div class="form-group">
        <label for="shift" class="form-label">Ca làm việc <span class="required">*</span></label>
        <select id="shift" name="shift" class="form-control" required>
            <option value="">-- Chọn ca --</option>
            <option value="MORNING" @selected(old('shift') === 'MORNING')>Sáng (07:00–11:30)</option>
            <option value="AFTERNOON" @selected(old('shift') === 'AFTERNOON')>Chiều (13:30–16:30)</option>
        </select>
        @error('shift')<div class="field-error">{{ \App\Models\AdminDisplay::text($message) }}</div>@enderror
        <p class="muted">Các khung 30 phút được tạo tự động khi lưu.</p>
    </div>
    @else
    <div class="form-group">
        <label for="shift" class="form-label">Ca làm việc</label>
        <select id="shift" name="shift" class="form-control">
            <option value="LEGACY" @selected(old('shift', 'LEGACY') === 'LEGACY')>Giữ giờ hiện tại</option>
            <option value="MORNING" @selected(old('shift') === 'MORNING')>Sáng (07:00–11:30)</option>
            <option value="AFTERNOON" @selected(old('shift') === 'AFTERNOON')>Chiều (13:30–16:30)</option>
        </select>
        <p class="muted">Nếu đổi ca, hệ thống sử dụng giờ của ca đã chọn. Lịch đã có hẹn không được đổi ca.</p>
    </div>
    <p class="muted">Giờ hiện tại: {{ substr($doctorSchedule->start_time, 0, 5) }}–{{ substr($doctorSchedule->end_time, 0, 5) }}.</p>
    @error('shift')<div class="field-error">{{ \App\Models\AdminDisplay::text($message) }}</div>@enderror

    @endif
    <div class="form-group">

        <label
            for="status"
            class="form-label"
        >
            Trạng thái quản trị
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

        <p class="muted">Lịch hết giờ sẽ hiển thị ngừng hoạt động, kể cả khi trạng thái quản trị đang bật.</p>

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
