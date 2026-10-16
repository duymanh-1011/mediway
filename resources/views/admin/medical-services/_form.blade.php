<div class="form-grid">

    <div class="form-group">

        <label
            for="service_code"
            class="form-label"
        >
            Mã dịch vụ
            <span class="required">*</span>
        </label>

        <input
            type="text"
            id="service_code"
            name="service_code"
            class="form-control"
            value="{{ old(
                'service_code',
                $medicalService->service_code ?? ''
            ) }}"
            placeholder="Ví dụ: DVXN001"
            maxlength="30"
            required
        >

        @error('service_code')
            <div class="field-error">
                {{ $message }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="service_group_id"
            class="form-label"
        >
            Nhóm dịch vụ
            <span class="required">*</span>
        </label>

        <select
            id="service_group_id"
            name="service_group_id"
            class="form-control"
            required
        >

            <option value="">
                -- Chọn nhóm dịch vụ --
            </option>

            @foreach ($serviceGroups as $serviceGroup)

                <option
                    value="{{ $serviceGroup->id }}"
                    @selected(
                        (string) old(
                            'service_group_id',
                            $medicalService->service_group_id ?? ''
                        )
                        ===
                        (string) $serviceGroup->id
                    )
                >
                    {{ $serviceGroup->service_group_code }}
                    -
                    {{ $serviceGroup->name }}
                </option>

            @endforeach

        </select>

        @error('service_group_id')
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
            Tên dịch vụ
            <span class="required">*</span>
        </label>

        <input
            type="text"
            id="name"
            name="name"
            class="form-control"
            value="{{ old(
                'name',
                $medicalService->name ?? ''
            ) }}"
            placeholder="Ví dụ: Công thức máu"
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
            for="price"
            class="form-label"
        >
            Giá dịch vụ
            <span class="required">*</span>
        </label>

        <input
            type="number"
            id="price"
            name="price"
            class="form-control"
            value="{{ old(
                'price',
                $medicalService->price ?? ''
            ) }}"
            min="0"
            step="1000"
            placeholder="Ví dụ: 150000"
            required
        >

        @error('price')
            <div class="field-error">
                {{ $message }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="unit"
            class="form-label"
        >
            Đơn vị
            <span class="required">*</span>
        </label>

        <input
            type="text"
            id="unit"
            name="unit"
            class="form-control"
            value="{{ old(
                'unit',
                $medicalService->unit ?? ''
            ) }}"
            placeholder="Ví dụ: Lần"
            maxlength="50"
            required
        >

        @error('unit')
            <div class="field-error">
                {{ $message }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="estimated_duration_minutes"
            class="form-label"
        >
            Thời lượng dự kiến
        </label>

        <input
            type="number"
            id="estimated_duration_minutes"
            name="estimated_duration_minutes"
            class="form-control"
            value="{{ old(
                'estimated_duration_minutes',
                $medicalService->estimated_duration_minutes ?? ''
            ) }}"
            min="1"
            max="1440"
            placeholder="Số phút"
        >

        @error('estimated_duration_minutes')
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
                $medicalService->display_order ?? 0
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
                        $medicalService->status ?? 'ACTIVE'
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
                        $medicalService->status ?? ''
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
        rows="4"
        placeholder="Nhập mô tả dịch vụ..."
    >{{ old(
        'description',
        $medicalService->description ?? ''
    ) }}</textarea>

    @error('description')
        <div class="field-error">
            {{ $message }}
        </div>
    @enderror

</div>

<div class="form-group">

    <label
        for="preparation_instructions"
        class="form-label"
    >
        Hướng dẫn chuẩn bị
    </label>

    <textarea
        id="preparation_instructions"
        name="preparation_instructions"
        class="form-control"
        rows="4"
        placeholder="Ví dụ: Nhịn ăn 8 giờ trước khi xét nghiệm..."
    >{{ old(
        'preparation_instructions',
        $medicalService->preparation_instructions ?? ''
    ) }}</textarea>

    @error('preparation_instructions')
        <div class="field-error">
            {{ $message }}
        </div>
    @enderror

</div>

<div class="form-group">

    <label
        for="precautions"
        class="form-label"
    >
        Lưu ý / Cảnh báo
    </label>

    <textarea
        id="precautions"
        name="precautions"
        class="form-control"
        rows="4"
        placeholder="Nhập các lưu ý hoặc cảnh báo..."
    >{{ old(
        'precautions',
        $medicalService->precautions ?? ''
    ) }}</textarea>

    @error('precautions')
        <div class="field-error">
            {{ $message }}
        </div>
    @enderror

</div>