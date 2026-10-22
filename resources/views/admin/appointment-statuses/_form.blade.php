<div class="form-grid">

    <div class="form-group">

        <label class="form-label">
            Tên trạng thái
        </label>

        <input
            type="text"
            class="form-control readonly-control"
            value="{{ \App\Models\AdminDisplay::label($appointmentStatus->name) }}"
            readonly
        >

        <div class="form-help">
            Tên trạng thái là khóa nghiệp vụ và không được chỉnh sửa.
        </div>

    </div>

    <div class="form-group">

        <label
            for="process_order"
            class="form-label"
        >
            Thứ tự xử lý
            <span class="required">*</span>
        </label>

        <input
            type="number"
            id="process_order"
            name="process_order"
            class="form-control"
            min="1"
            value="{{ old(
                'process_order',
                $appointmentStatus->process_order
            ) }}"
            required
        >

        @error('process_order')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="allows_transition"
            class="form-label"
        >
            Cho phép chuyển tiếp
            <span class="required">*</span>
        </label>

        <select
            id="allows_transition"
            name="allows_transition"
            class="form-control"
            required
        >

            <option
                value="1"
                @selected(
                    (string) old(
                        'allows_transition',
                        $appointmentStatus->allows_transition
                            ? '1'
                            : '0'
                    ) === '1'
                )
            >
                Có
            </option>

            <option
                value="0"
                @selected(
                    (string) old(
                        'allows_transition',
                        $appointmentStatus->allows_transition
                            ? '1'
                            : '0'
                    ) === '0'
                )
            >
                Không
            </option>

        </select>

        @error('allows_transition')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="is_active"
            class="form-label"
        >
            Trạng thái hoạt động
            <span class="required">*</span>
        </label>

        <select
            id="is_active"
            name="is_active"
            class="form-control"
            required
        >

            <option
                value="1"
                @selected(
                    (string) old(
                        'is_active',
                        $appointmentStatus->is_active
                            ? '1'
                            : '0'
                    ) === '1'
                )
            >
                Đang hoạt động
            </option>

            <option
                value="0"
                @selected(
                    (string) old(
                        'is_active',
                        $appointmentStatus->is_active
                            ? '1'
                            : '0'
                    ) === '0'
                )
            >
                Ngừng hoạt động
            </option>

        </select>

        @error('is_active')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>

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
        placeholder="Mô tả ý nghĩa và cách sử dụng trạng thái..."
    >{{ old(
        'description',
        \App\Models\AdminDisplay::text($appointmentStatus->description)
    ) }}</textarea>

    @error('description')
        <div class="field-error">
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

</div>
