@props(['patient', 'visit', 'includeProfileDetails' => false, 'phone' => null])

@php
    $values = [
        'Họ và tên' => $patient?->full_name,
        'Ngày sinh' => $patient?->date_of_birth?->format('d/m/Y'),
        'Giới tính' => $patient?->gender ? \App\Models\AdminDisplay::label($patient->gender) : null,
    ];

    if ($includeProfileDetails) {
        $detail = $patient?->detail;
        $diagnoses = $visit->diagnoses->map(function ($diagnosis) {
            $name = $diagnosis->disease?->name ?: $diagnosis->diagnosis_description;
            $code = $diagnosis->disease?->disease_code;

            return $name && $code ? $code.' – '.$name : ($name ?: $code);
        })->filter()->unique()->join('; ');

        $values += [
            'Số CCCD' => $detail?->citizen_id_number,
            'Số điện thoại' => $phone,
            'Địa chỉ' => $detail?->current_address ?: $detail?->permanent_address,
            'Chẩn đoán' => $diagnoses ?: 'Chưa ghi nhận',
        ];
    }
@endphp

<section class="print-section" aria-labelledby="print-patient-heading">
    <h2 class="print-heading" id="print-patient-heading"><span>I.</span> Thông tin bệnh nhân</h2>
    <x-print.fields :values="$values" :wide="['Họ và tên', 'Địa chỉ', 'Chẩn đoán']"
        divider="Chẩn đoán" fallback="Chưa cập nhật" />
</section>
