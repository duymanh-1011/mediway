<div class="form-grid">

    <div class="form-group">

        <label
            for="specialty_code"
            class="form-label"
        >
            Mã chuyên khoa
            <span class="required">*</span>
        </label>

        <input
            type="text"
            id="specialty_code"
            name="specialty_code"
            class="form-control"
            value="{{ old(
                'specialty_code',
                $specialty->specialty_code ?? ''
            ) }}"
            placeholder="Ví dụ: CKTMH"
            maxlength="30"
            required
        >

        @error('specialty_code')
            <div class="field-error">
                {{ $message }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="name"
            class="form-label"
        >
            Tên chuyên khoa
            <span class="required">*</span>
        </label>

        <input
            type="text"
            id="name"
            name="name"
            class="form-control"
            value="{{ old(
                'name',
                $specialty->name ?? ''
            ) }}"
            placeholder="Ví dụ: Tai Mũi Họng"
            maxlength="150"
            required
        >

        @error('name')
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
            <option value="">
                -- Chọn trạng thái --
            </option>

            <option
                value="ACTIVE"
                @selected(
                    old(
                        'status',
                        $specialty->status ?? 'ACTIVE'
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
                        $specialty->status ?? ''
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

    <div class="form-group">

        <label
            for="image"
            class="form-label"
        >
            Ảnh chuyên khoa
        </label>

        <input
            type="file"
            id="image"
            name="image"
            class="form-control"
            accept=".jpg,.jpeg,.png,.webp"
        >

        <div class="form-help">
            JPG, JPEG, PNG hoặc WEBP. Tối đa 2MB.
        </div>

        @error('image')
            <div class="field-error">
                {{ $message }}
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
        rows="5"
        placeholder="Nhập mô tả chuyên khoa..."
    >{{ old(
        'description',
        $specialty->description ?? ''
    ) }}</textarea>

    @error('description')
        <div class="field-error">
            {{ $message }}
        </div>
    @enderror

</div>

<div class="form-group">

    <label
        for="pre_visit_instructions"
        class="form-label"
    >
        Hướng dẫn trước khám
    </label>

    <textarea
        id="pre_visit_instructions"
        name="pre_visit_instructions"
        class="form-control"
        rows="4"
        placeholder="Nhập hướng dẫn dành cho bệnh nhân trước khi khám..."
    >{{ old(
        'pre_visit_instructions',
        $specialty->pre_visit_instructions ?? ''
    ) }}</textarea>

    @error('pre_visit_instructions')
        <div class="field-error">
            {{ $message }}
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
        $specialty->notes ?? ''
    ) }}</textarea>

    @error('notes')
        <div class="field-error">
            {{ $message }}
        </div>
    @enderror

</div>

@if (
    isset($specialty)
    && $specialty->image_path
)

    <div class="current-image-section">

        <div class="form-label">
            Ảnh hiện tại
        </div>

        <img
            src="{{ asset(
                'storage/' . $specialty->image_path
            ) }}"
            alt="{{ $specialty->name }}"
            class="specialty-preview"
        >

        <label class="remove-image">

            <input
                type="checkbox"
                name="remove_image"
                value="1"
                @checked(
                    old('remove_image')
                )
            >

            Xóa ảnh hiện tại

        </label>

    </div>

@endif