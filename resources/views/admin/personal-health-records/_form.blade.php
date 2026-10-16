@php
    $editing = isset($record);
    $selectedPatientId = old(
        'patient_id',
        $editing ? $record->patient_id : ($selectedPatient?->id ?? '')
    );

    $recordedAt = $editing
        ? $record->recorded_at?->format('Y-m-d\TH:i')
        : now()->format('Y-m-d\TH:i');

    $fields = [
        ['name' => 'height_cm', 'label' => 'Chiều cao (cm)', 'min' => '20', 'max' => '250', 'step' => '0.01'],
        ['name' => 'weight_kg', 'label' => 'Cân nặng (kg)', 'min' => '0.5', 'max' => '500', 'step' => '0.01'],
        ['name' => 'systolic_blood_pressure', 'label' => 'Huyết áp tâm thu (mmHg)', 'min' => '40', 'max' => '300', 'step' => '1'],
        ['name' => 'diastolic_blood_pressure', 'label' => 'Huyết áp tâm trương (mmHg)', 'min' => '20', 'max' => '200', 'step' => '1'],
        ['name' => 'heart_rate', 'label' => 'Nhịp tim (lần/phút)', 'min' => '20', 'max' => '250', 'step' => '1'],
        ['name' => 'body_temperature', 'label' => 'Nhiệt độ (°C)', 'min' => '30', 'max' => '45', 'step' => '0.1'],
        ['name' => 'spo2', 'label' => 'SpO2 (%)', 'min' => '0', 'max' => '100', 'step' => '0.01'],
        ['name' => 'respiratory_rate', 'label' => 'Nhịp thở (lần/phút)', 'min' => '5', 'max' => '100', 'step' => '1'],
        ['name' => 'blood_glucose', 'label' => 'Đường huyết (đơn vị theo quy ước hệ thống)', 'min' => '0', 'max' => '1000', 'step' => '0.01'],
    ];
@endphp

<div class="hr-section">
    <h2>Thông tin lần đo</h2>
    <p>Chọn bệnh nhân và thời điểm thực hiện đo sức khỏe.</p>
</div>

<div class="hr-grid">
    <div class="hr-field hr-full">
        <label for="patient_id">Bệnh nhân <span>*</span></label>

        @if($editing)
            <input
                type="text"
                value="{{ $record->patient?->patient_code }} — {{ $record->patient?->full_name }}"
                disabled
            >
            <small class="hr-help">
                Không thể chuyển bản ghi sức khỏe sang bệnh nhân khác.
            </small>
        @else
            <select id="patient_id" name="patient_id" required>
                <option value="">-- Chọn bệnh nhân --</option>

                @foreach($patients as $patientOption)
                    <option value="{{ $patientOption->id }}"
                        @selected((string) $selectedPatientId === (string) $patientOption->id)>
                        {{ $patientOption->patient_code }} — {{ $patientOption->full_name }}
                    </option>
                @endforeach
            </select>
            @error('patient_id')
                <small class="hr-error">{{ $message }}</small>
            @enderror
        @endif
    </div>

    <div class="hr-field hr-full">
        <label for="recorded_at">Thời điểm ghi nhận <span>*</span></label>
        <input
            type="datetime-local"
            id="recorded_at"
            name="recorded_at"
            value="{{ old('recorded_at', $recordedAt) }}"
            max="{{ now()->format('Y-m-d\TH:i') }}"
            required
        >
        @error('recorded_at')
            <small class="hr-error">{{ $message }}</small>
        @enderror
    </div>
</div>

<div class="hr-section hr-section-gap">
    <h2>Chỉ số cơ thể và sinh hiệu</h2>
    <p>BMI được hệ thống tự động tính khi có đủ chiều cao và cân nặng.</p>
</div>

<div class="hr-grid">
    @foreach($fields as $field)
        <div class="hr-field">
            <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
            <input
                type="number"
                id="{{ $field['name'] }}"
                name="{{ $field['name'] }}"
                value="{{ old($field['name'], $editing ? $record->{$field['name']} : '') }}"
                min="{{ $field['min'] }}"
                max="{{ $field['max'] }}"
                step="{{ $field['step'] }}"
                placeholder="Chưa ghi nhận"
            >
            @error($field['name'])
                <small class="hr-error">{{ $message }}</small>
            @enderror
        </div>
    @endforeach

    <div class="hr-field">
        <label for="bmi_preview">BMI (tự tính)</label>
        <input
            type="text"
            id="bmi_preview"
            value="{{ $editing ? ($record->bmi ?? '') : '' }}"
            placeholder="Nhập chiều cao và cân nặng"
            readonly
        >
    </div>

    <div class="hr-field hr-full">
        <label for="notes">Ghi chú</label>
        <textarea
            id="notes"
            name="notes"
            rows="4"
            maxlength="5000"
            placeholder="Ghi chú về lần đo sức khỏe..."
        >{{ old('notes', $editing ? $record->notes : '') }}</textarea>
        @error('notes')
            <small class="hr-error">{{ $message }}</small>
        @enderror
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const heightInput = document.getElementById('height_cm');
    const weightInput = document.getElementById('weight_kg');
    const bmiInput = document.getElementById('bmi_preview');

    function calculateBmi() {
        const height = parseFloat(heightInput.value);
        const weight = parseFloat(weightInput.value);

        if (
            Number.isFinite(height) &&
            Number.isFinite(weight) &&
            height > 0 &&
            weight > 0
        ) {
            const meters = height / 100;
            bmiInput.value = (weight / (meters * meters)).toFixed(2);
        } else {
            bmiInput.value = '';
        }
    }

    heightInput.addEventListener('input', calculateBmi);
    weightInput.addEventListener('input', calculateBmi);
    calculateBmi();
});
</script>