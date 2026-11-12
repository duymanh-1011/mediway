<fieldset class="doctor-prescription-item" data-prescription-row>
    <legend>Thuốc <span data-prescription-number>{{ is_int($index) ? $index + 1 : '' }}</span></legend>
    <div class="doctor-prescription-grid">
        <label>Thuốc <span aria-hidden="true">*</span><select name="items[{{ $index }}][medicine_id]" required>
            <option value="">Chọn thuốc</option>
            @foreach($medicines as $medicine)<option value="{{ $medicine->id }}" @selected((string) ($values['medicine_id'] ?? '') === (string) $medicine->id)>{{ $medicine->medicine_code }} · {{ $medicine->name }} {{ $medicine->strength }} ({{ implode(', ', array_keys($medicine->salePrices())) ?: 'chưa có giá' }})</option>@endforeach
        </select></label>
        <label>Số lượng <span aria-hidden="true">*</span><input type="number" name="items[{{ $index }}][quantity]" min="0.01" step="0.01" value="{{ $values['quantity'] ?? '' }}" required></label>
        <label>Đơn vị <span aria-hidden="true">*</span><select name="items[{{ $index }}][usage_unit]" required><option value="">Chọn đơn vị</option>@foreach($units as $unit)<option value="{{ $unit }}" @selected(($values['usage_unit'] ?? '') === $unit)>{{ $unit }}</option>@endforeach</select></label>
        <label>Liều mỗi lần <span aria-hidden="true">*</span><input name="items[{{ $index }}][dose_per_use]" maxlength="80" value="{{ $values['dose_per_use'] ?? '' }}" required></label>
        <label>Số lần mỗi ngày <span aria-hidden="true">*</span><input type="number" name="items[{{ $index }}][times_per_day]" min="0.01" step="0.01" value="{{ $values['times_per_day'] ?? '' }}" required></label>
        <label>Số ngày dùng <span aria-hidden="true">*</span><input type="number" name="items[{{ $index }}][duration_days]" min="1" step="1" value="{{ $values['duration_days'] ?? '' }}" required></label>
        <label>Cách dùng <span aria-hidden="true">*</span><input name="items[{{ $index }}][usage_method]" maxlength="120" value="{{ $values['usage_method'] ?? '' }}" required></label>
        <label>Thời điểm dùng<input name="items[{{ $index }}][administration_time]" maxlength="120" value="{{ $values['administration_time'] ?? '' }}"></label>
        <label class="doctor-prescription-grid__wide">Hướng dẫn riêng<textarea name="items[{{ $index }}][usage_instructions]" maxlength="5000" rows="2">{{ $values['usage_instructions'] ?? '' }}</textarea></label>
    </div>
    <button type="button" class="portal-btn portal-btn--secondary" data-prescription-remove>Xóa thuốc</button>
</fieldset>
