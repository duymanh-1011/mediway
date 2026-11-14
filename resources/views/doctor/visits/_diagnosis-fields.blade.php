<label>Bệnh <span aria-hidden="true">*</span>
    <select name="disease_id" required>
        <option value="">Chọn bệnh</option>
        @if($currentDisease && $currentDisease->status !== 'ACTIVE')<option value="{{ $currentDisease->id }}" @selected((string) $selectedDisease === (string) $currentDisease->id)>{{ $currentDisease->disease_code }} · {{ $currentDisease->name }} (đã ngừng hoạt động)</option>@endif
        @foreach($diseases as $disease)<option value="{{ $disease->id }}" @selected((string) $selectedDisease === (string) $disease->id)>{{ $disease->disease_code }} · {{ $disease->name }}</option>@endforeach
    </select>
</label>
<label>Loại chẩn đoán <span aria-hidden="true">*</span><select name="diagnosis_type" required><option value="">Chọn loại chẩn đoán</option>@foreach($diagnosisTypes as $value => $label)<option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>@endforeach</select></label>
<label>Mức độ nếu có<input name="severity" maxlength="40" value="{{ $severity }}"></label>
<label>Mô tả<textarea name="diagnosis_description" rows="2" maxlength="5000">{{ $description }}</textarea></label>
<label>Ghi chú<textarea name="notes" rows="2" maxlength="5000">{{ $notes }}</textarea></label>
