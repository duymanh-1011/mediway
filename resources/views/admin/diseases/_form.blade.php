<div class="form-grid">

    <div class="form-group">

        <label
            for="disease_code"
            class="form-label"
        >
            Mã bệnh
            <span class="required">*</span>
        </label>

        @include('admin.layouts.partials.business-code', ['field' => 'disease_code', 'record' => $disease ?? null])

        @error('disease_code')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="disease_group_id"
            class="form-label"
        >
            Nhóm bệnh
            <span class="required">*</span>
        </label>

        <select
            id="disease_group_id"
            name="disease_group_id"
            class="form-control"
            required
        >

            <option value="">
                -- Chọn nhóm bệnh --
            </option>

            @foreach ($diseaseGroups as $group)

                <option
                    value="{{ $group->id }}"
                    @selected(
                        (string) old(
                            'disease_group_id',
                            $disease->disease_group_id ?? $selectedDiseaseGroup->id ?? ''
                        )
                        ===
                        (string) $group->id
                    )
                >
                    {{ $group->disease_group_code }}
                    -
                    {{ $group->name }}
                </option>

            @endforeach

        </select>

        @error('disease_group_id')
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
            Tên bệnh
            <span class="required">*</span>
        </label>

        <input
            type="text"
            id="name"
            name="name"
            class="form-control"
            value="{{ old(
                'name',
                $disease->name ?? ''
            ) }}"
            placeholder="Ví dụ: Viêm mũi họng cấp"
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
            for="alternative_name"
            class="form-label"
        >
            Tên thay thế
        </label>

        <input
            type="text"
            id="alternative_name"
            name="alternative_name"
            class="form-control"
            value="{{ old(
                'alternative_name',
                $disease->alternative_name ?? ''
            ) }}"
            placeholder="Tên khác của bệnh nếu có"
        >

        @error('alternative_name')
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
                        $disease->status ?? 'ACTIVE'
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
                        $disease->status ?? ''
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
        placeholder="Nhập mô tả bệnh..."
    >{{ old(
        'description',
        $disease->description ?? ''
    ) }}</textarea>

    @error('description')
        <div class="field-error">
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

</div>

<div class="form-group">

    <label
        for="common_symptoms"
        class="form-label"
    >
        Triệu chứng thường gặp
    </label>

    <textarea
        id="common_symptoms"
        name="common_symptoms"
        class="form-control"
        rows="4"
        placeholder="Ví dụ: Sốt, đau họng, nghẹt mũi, ho..."
    >{{ old(
        'common_symptoms',
        $disease->common_symptoms ?? ''
    ) }}</textarea>

    @error('common_symptoms')
        <div class="field-error">
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

</div>

<div class="form-group">

    <label
        for="professional_notes"
        class="form-label"
    >
        Ghi chú chuyên môn
    </label>

    <textarea
        id="professional_notes"
        name="professional_notes"
        class="form-control"
        rows="4"
        placeholder="Thông tin chuyên môn dành cho nhân viên y tế..."
    >{{ old(
        'professional_notes',
        $disease->professional_notes ?? ''
    ) }}</textarea>

    @error('professional_notes')
        <div class="field-error">
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

</div>
