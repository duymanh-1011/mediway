<div class="form-grid">

    <div class="form-group">

        <label
            for="disease_group_code"
            class="form-label"
        >
            Mã nhóm bệnh
            <span class="required">*</span>
        </label>

        @include('admin.layouts.partials.business-code', ['field' => 'disease_group_code', 'record' => $diseaseGroup ?? null])

        @error('disease_group_code')
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
            Tên nhóm bệnh
            <span class="required">*</span>
        </label>

        <input
            type="text"
            id="name"
            name="name"
            class="form-control"
            value="{{ old(
                'name',
                $diseaseGroup->name ?? ''
            ) }}"
            placeholder="Ví dụ: Bệnh Tai Mũi Họng"
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
                        $diseaseGroup->status ?? 'ACTIVE'
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
                        $diseaseGroup->status ?? ''
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
        rows="5"
        placeholder="Nhập mô tả nhóm bệnh..."
    >{{ old(
        'description',
        $diseaseGroup->description ?? ''
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
        $diseaseGroup->notes ?? ''
    ) }}</textarea>

    @error('notes')
        <div class="field-error">
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

</div>
