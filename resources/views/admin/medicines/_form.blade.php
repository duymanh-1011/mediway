<div class="form-grid">

    <div class="form-group">

        <label
            for="medicine_code"
            class="form-label"
        >
            Mã thuốc
            <span class="required">*</span>
        </label>

        @include('admin.layouts.partials.business-code', ['field' => 'medicine_code', 'record' => $medicine ?? null])

        @error('medicine_code')
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
            Tên thuốc
            <span class="required">*</span>
        </label>

        <input
            type="text"
            id="name"
            name="name"
            class="form-control"
            value="{{ old(
                'name',
                $medicine->name ?? ''
            ) }}"
            placeholder="Ví dụ: Paracetamol 500mg"
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
            for="active_ingredient"
            class="form-label"
        >
            Hoạt chất
        </label>

        <input
            type="text"
            id="active_ingredient"
            name="active_ingredient"
            class="form-control"
            value="{{ old(
                'active_ingredient',
                $medicine->active_ingredient ?? ''
            ) }}"
            placeholder="Ví dụ: Paracetamol"
        >

        @error('active_ingredient')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="strength"
            class="form-label"
        >
            Hàm lượng
        </label>

        <input
            type="text"
            id="strength"
            name="strength"
            class="form-control"
            value="{{ old(
                'strength',
                $medicine->strength ?? ''
            ) }}"
            placeholder="Ví dụ: 500mg"
        >

        @error('strength')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="dosage_form"
            class="form-label"
        >
            Dạng bào chế
        </label>

        <input
            type="text"
            id="dosage_form"
            name="dosage_form"
            class="form-control"
            value="{{ old(
                'dosage_form',
                $medicine->dosage_form ?? ''
            ) }}"
            placeholder="Ví dụ: Viên nén"
        >

        @error('dosage_form')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="unit"
            class="form-label"
        >
            Đơn vị
        </label>

        <input
            type="text"
            id="unit"
            name="unit"
            class="form-control"
            value="{{ old(
                'unit',
                $medicine->unit ?? ''
            ) }}"
            placeholder="Ví dụ: Viên"
        >

        @error('unit')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="route_of_administration"
            class="form-label"
        >
            Đường dùng
        </label>

        <input
            type="text"
            id="route_of_administration"
            name="route_of_administration"
            class="form-control"
            value="{{ old(
                'route_of_administration',
                $medicine->route_of_administration ?? ''
            ) }}"
            placeholder="Ví dụ: Uống"
        >

        @error('route_of_administration')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="manufacturer"
            class="form-label"
        >
            Nhà sản xuất
        </label>

        <input
            type="text"
            id="manufacturer"
            name="manufacturer"
            class="form-control"
            value="{{ old(
                'manufacturer',
                $medicine->manufacturer ?? ''
            ) }}"
            placeholder="Tên nhà sản xuất"
        >

        @error('manufacturer')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>

    <div class="form-group">

        <label
            for="country_of_origin"
            class="form-label"
        >
            Nước sản xuất
        </label>

        <input
            type="text"
            id="country_of_origin"
            name="country_of_origin"
            class="form-control"
            value="{{ old(
                'country_of_origin',
                $medicine->country_of_origin ?? ''
            ) }}"
            placeholder="Ví dụ: Việt Nam"
        >

        @error('country_of_origin')
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
                        $medicine->status ?? 'ACTIVE'
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
                        $medicine->status ?? ''
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
        rows="3"
        placeholder="Mô tả thuốc..."
    >{{ old(
        'description',
        $medicine->description ?? ''
    ) }}</textarea>
@error('description')<div class="field-error">{{ $message }}</div>@enderror

</div>

<div class="form-group">

    <label
        for="indications"
        class="form-label"
    >
        Chỉ định
    </label>

    <textarea
        id="indications"
        name="indications"
        class="form-control"
        rows="4"
        placeholder="Các trường hợp sử dụng thuốc..."
    >{{ old(
        'indications',
        $medicine->indications ?? ''
    ) }}</textarea>
@error('indications')<div class="field-error">{{ $message }}</div>@enderror

</div>

<div class="form-group">

    <label
        for="contraindications"
        class="form-label"
    >
        Chống chỉ định
    </label>

    <textarea
        id="contraindications"
        name="contraindications"
        class="form-control"
        rows="4"
        placeholder="Các trường hợp không nên sử dụng..."
    >{{ old(
        'contraindications',
        $medicine->contraindications ?? ''
    ) }}</textarea>
@error('contraindications')<div class="field-error">{{ $message }}</div>@enderror

</div>

<div class="form-group">

    <label
        for="warnings"
        class="form-label"
    >
        Cảnh báo
    </label>

    <textarea
        id="warnings"
        name="warnings"
        class="form-control"
        rows="4"
        placeholder="Các cảnh báo khi sử dụng..."
    >{{ old(
        'warnings',
        $medicine->warnings ?? ''
    ) }}</textarea>
@error('warnings')<div class="field-error">{{ $message }}</div>@enderror

</div>

<div class="form-group">

    <label
        for="common_side_effects"
        class="form-label"
    >
        Tác dụng phụ thường gặp
    </label>

    <textarea
        id="common_side_effects"
        name="common_side_effects"
        class="form-control"
        rows="4"
        placeholder="Nhập tác dụng phụ thường gặp..."
    >{{ old(
        'common_side_effects',
        $medicine->common_side_effects ?? ''
    ) }}</textarea>
@error('common_side_effects')<div class="field-error">{{ $message }}</div>@enderror

</div>

<div class="form-group">

    <label
        for="storage_instructions"
        class="form-label"
    >
        Hướng dẫn bảo quản
    </label>

    <textarea
        id="storage_instructions"
        name="storage_instructions"
        class="form-control"
        rows="3"
        placeholder="Ví dụ: Bảo quản nơi khô ráo, dưới 30°C..."
    >{{ old(
        'storage_instructions',
        $medicine->storage_instructions ?? ''
    ) }}</textarea>
@error('storage_instructions')<div class="field-error">{{ $message }}</div>@enderror

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
        $medicine->notes ?? ''
    ) }}</textarea>
@error('notes')<div class="field-error">{{ $message }}</div>@enderror

</div>
