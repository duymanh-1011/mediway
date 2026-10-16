<div class="form-grid">

    <div class="form-group">

        <label
            for="service_group_code"
            class="form-label"
        >
            Mã nhóm dịch vụ
            <span class="required">*</span>
        </label>

        <input
            type="text"
            id="service_group_code"
            name="service_group_code"
            class="form-control"
            value="{{ old(
                'service_group_code',
                $serviceGroup->service_group_code ?? ''
            ) }}"
            placeholder="Ví dụ: XN"
            maxlength="30"
            required
        >

        @error('service_group_code')
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
            Tên nhóm dịch vụ
            <span class="required">*</span>
        </label>

        <input
            type="text"
            id="name"
            name="name"
            class="form-control"
            value="{{ old(
                'name',
                $serviceGroup->name ?? ''
            ) }}"
            placeholder="Ví dụ: Xét nghiệm"
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
            for="display_order"
            class="form-label"
        >
            Thứ tự hiển thị
            <span class="required">*</span>
        </label>

        <input
            type="number"
            id="display_order"
            name="display_order"
            class="form-control"
            value="{{ old(
                'display_order',
                $serviceGroup->display_order ?? 0
            ) }}"
            min="0"
            max="9999"
            required
        >

        @error('display_order')
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
                        $serviceGroup->status ?? 'ACTIVE'
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
                        $serviceGroup->status ?? ''
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
        placeholder="Nhập mô tả nhóm dịch vụ..."
    >{{ old(
        'description',
        $serviceGroup->description ?? ''
    ) }}</textarea>

    @error('description')
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
        $serviceGroup->notes ?? ''
    ) }}</textarea>

    @error('notes')
        <div class="field-error">
            {{ $message }}
        </div>
    @enderror

</div>