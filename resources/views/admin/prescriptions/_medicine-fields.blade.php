<fieldset class="prescription-medicine" data-prescription-item>
    <legend>Thông tin thuốc</legend>
    <div class="form-group">
        <label for="medicine-{{ $index }}">Thuốc <span aria-hidden="true">*</span></label>
        <select id="medicine-{{ $index }}" name="items[{{ $index }}][medicine_id]" class="form-control" required>
            <option value="">Chọn thuốc</option>
            @foreach ($medicines as $medicine)
                <option value="{{ $medicine->id }}" data-unit="{{ $medicine->unit }}" @selected(is_scalar($item['medicine_id'] ?? null) && (string) $item['medicine_id'] === (string) $medicine->id)>{{ $medicine->medicine_code }} - {{ $medicine->name }}{{ $medicine->strength ? ' · '.$medicine->strength : '' }}</option>
            @endforeach
        </select>
        @error("items.$index.medicine_id")<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div class="form-grid">
        @foreach ([
            ['quantity', 'Số lượng', 'number', true, '0.01', '0.01'],
            ['usage_unit', 'Đơn vị tính', 'text', true, null, null],
            ['dose_per_use', 'Liều mỗi lần', 'text', true, null, null],
            ['times_per_day', 'Số lần mỗi ngày', 'number', true, '0.01', '0.01'],
            ['duration_days', 'Số ngày dùng', 'number', true, '1', '1'],
            ['usage_method', 'Cách dùng', 'text', true, null, null],
            ['administration_time', 'Thời điểm dùng', 'text', false, null, null],
        ] as [$field, $label, $type, $required, $min, $step])
            <div class="form-group">
                <label for="{{ $field }}-{{ $index }}">{{ $label }} @if ($required)<span aria-hidden="true">*</span>@endif</label>
                <input id="{{ $field }}-{{ $index }}" name="items[{{ $index }}][{{ $field }}]" type="{{ $type }}" class="form-control" value="{{ is_scalar($item[$field] ?? null) ? $item[$field] : '' }}" @if ($required) required @endif @if ($min) min="{{ $min }}" @endif @if ($step) step="{{ $step }}" @endif>
                @error("items.$index.$field")<p class="field-error">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>
    <div class="form-group">
        <label for="instructions-{{ $index }}">Hướng dẫn thêm</label>
        <textarea id="instructions-{{ $index }}" name="items[{{ $index }}][usage_instructions]" class="form-control" rows="2">{{ is_scalar($item['usage_instructions'] ?? null) ? $item['usage_instructions'] : '' }}</textarea>
        @error("items.$index.usage_instructions")<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <button type="button" class="admin-btn admin-btn-danger" data-remove-prescription-item>Xóa thuốc</button>
</fieldset>
