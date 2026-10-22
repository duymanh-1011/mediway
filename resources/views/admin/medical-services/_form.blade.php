@php
    $selectedServiceGroupIds =
        old('service_group_ids');

    if (
        $selectedServiceGroupIds === null
        &&
        isset($medicalService)
    ) {
        $selectedServiceGroupIds =
            $medicalService
                ->serviceGroups
                ->pluck('id')
                ->map(
                    fn ($id) =>
                        (string) $id
                )
                ->all();
    }

    $selectedServiceGroupIds =
        array_map(
            'strval',
            $selectedServiceGroupIds ?? $selectedGroupIds ?? []
        );
@endphp


<div class="form-grid">

    {{-- Mã dịch vụ --}}
    <div class="form-group">

        <label
            for="service_code"
            class="form-label"
        >
            Mã dịch vụ
            <span class="required">*</span>
        </label>

        @include('admin.layouts.partials.business-code', ['field' => 'service_code', 'record' => $medicalService ?? null])

        @error('service_code')
            <div class="field-error">
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>


    {{-- Tên dịch vụ --}}
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
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>


    {{-- Giá --}}
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
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>


    {{-- Đơn vị --}}
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
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>


    {{-- Thời lượng --}}
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
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>


    {{-- Thứ tự --}}
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
                {{ \App\Models\AdminDisplay::text($message) }}
            </div>
        @enderror

    </div>


    {{-- Trạng thái --}}
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
                        $medicalService->status
                            ?? 'ACTIVE'
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
                        $medicalService->status
                            ?? ''
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


{{-- =====================================================
     NHÓM DỊCH VỤ
===================================================== --}}

<div class="form-group">

    <label class="form-label">
        Nhóm dịch vụ
        <span class="required">*</span>
    </label>

    <p class="admin-u-3093b01f"

    >
        Có thể chọn nhiều nhóm cho cùng một dịch vụ.
    </p>

    <div class="admin-u-ac346560"

    >

        @forelse ($serviceGroups as $serviceGroup)

            <label class="admin-u-f8f78645"

            >

                <input
                    type="checkbox"
                    name="service_group_ids[]"
                    value="{{ $serviceGroup->id }}"
                    @checked(
                        in_array(
                            (string) $serviceGroup->id,
                            $selectedServiceGroupIds,
                            true
                        )
                    )
                >

                <span>

                    <strong class="admin-u-b50f5cea"

                    >
                        {{ $serviceGroup->name }}
                    </strong>

                    <span class="admin-u-fe84f0ba"

                    >
                        {{ $serviceGroup->service_group_code }}

                        @if (
                            $serviceGroup->status
                            ===
                            'INACTIVE'
                        )
                            · Ngừng hoạt động
                        @endif
                    </span>

                </span>

            </label>

        @empty

            <div class="admin-u-980de5d0"

            >
                Chưa có nhóm dịch vụ để lựa chọn.
            </div>

        @endforelse

    </div>

    @error('service_group_ids')
        <div class="field-error">
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

    @error('service_group_ids.*')
        <div class="field-error">
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

</div>


{{-- Mô tả --}}
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
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

</div>


{{-- Hướng dẫn chuẩn bị --}}
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
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

</div>


{{-- Lưu ý --}}
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
            {{ \App\Models\AdminDisplay::text($message) }}
        </div>
    @enderror

</div>
