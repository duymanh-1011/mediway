<div class="form-grid">

    <div class="form-group">

        <label
            for="room_code"
            class="form-label"
        >
            Mã phòng khám
            <span class="required">*</span>
        </label>

        @include('admin.layouts.partials.business-code', ['field' => 'room_code', 'record' => $room ?? null])

        @error('room_code')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="name"
            class="form-label"
        >
            Tên phòng khám
            <span class="required">*</span>
        </label>

        <input
            type="text"
            id="name"
            name="name"
            class="form-control"
            value="{{ old(
                'name',
                $examinationRoom->name ?? ''
            ) }}"
            placeholder="Ví dụ: Phòng khám Tai Mũi Họng 1"
            maxlength="150"
            required
        >

        @error('name')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="room_number"
            class="form-label"
        >
            Số phòng
            <span class="required">*</span>
        </label>

        <input
            type="text"
            id="room_number"
            name="room_number"
            class="form-control"
            value="{{ old(
                'room_number',
                $examinationRoom->room_number ?? ''
            ) }}"
            placeholder="Ví dụ: 301"
            maxlength="50"
            required
        >

        @error('room_number')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="floor"
            class="form-label"
        >
            Tầng
        </label>

        <input
            type="text"
            id="floor"
            name="floor"
            class="form-control"
            value="{{ old(
                'floor',
                $examinationRoom->floor ?? ''
            ) }}"
            placeholder="Ví dụ: Tầng 3"
            maxlength="50"
        >

        @error('floor')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="building"
            class="form-label"
        >
            Tòa nhà
        </label>

        <input
            type="text"
            id="building"
            name="building"
            class="form-control"
            value="{{ old(
                'building',
                $examinationRoom->building ?? ''
            ) }}"
            placeholder="Ví dụ: Tòa khám bệnh"
            maxlength="150"
        >

        @error('building')
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

            <option value="">
                -- Chọn trạng thái --
            </option>

            <option
                value="ACTIVE"
                @selected(
                    old(
                        'status',
                        $examinationRoom->status ?? 'ACTIVE'
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
                        $examinationRoom->status ?? ''
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
        for="location_instructions"
        class="form-label"
    >
        Hướng dẫn vị trí
    </label>

    <textarea
        id="location_instructions"
        name="location_instructions"
        class="form-control"
        rows="3"
        placeholder="Ví dụ: Đi thang máy lên tầng 3, rẽ phải..."
    >{{ old(
        'location_instructions',
        $examinationRoom->location_instructions ?? ''
    ) }}</textarea>

    @error('location_instructions')
        <div class="field-error">
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

</div>

<div class="form-group">

    <label
        for="description"
        class="form-label"
    >
        Mô tả
    </label>

    <textarea
        id="description"
        name="description"
        class="form-control"
        rows="4"
        placeholder="Nhập mô tả phòng khám..."
    >{{ old(
        'description',
        $examinationRoom->description ?? ''
    ) }}</textarea>

    @error('description')
        <div class="field-error">
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

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
        rows="3"
        placeholder="Ghi chú nội bộ..."
    >{{ old(
        'notes',
        $examinationRoom->notes ?? ''
    ) }}</textarea>

    @error('notes')
        <div class="field-error">
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

</div>
