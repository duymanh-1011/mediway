@php
    $conclusion = $record->conclusion;
    $doctorName = $conclusion->doctor?->full_name ?? $record->doctor?->full_name;
    $diagnoses = $record->diagnoses->map(function ($diagnosis) {
        $name = $diagnosis->disease?->name ?: $diagnosis->diagnosis_description;
        $code = $diagnosis->disease?->disease_code;

        return $name && $code ? $code.' – '.$name : ($name ?: $code);
    })->filter()->unique()->join('; ');
@endphp

<x-print.document
    :title="'Phiếu kết quả khám '.$record->visit_code"
    heading="PHIẾU KẾT QUẢ KHÁM BỆNH"
    brand-subtitle="Hồ sơ điều trị"
    :facility="[$record->examinationRoom?->name]"
    :identifiers="['Mã lượt khám' => $record->visit_code, 'Mã bệnh nhân' => $patient->patient_code]"
    :back-url="route('patient.results.visit', $record)"
    back-label="Quay lại kết quả khám"
>
    <section class="print-section" aria-labelledby="visit-print-patient">
        <h2 class="print-heading" id="visit-print-patient"><span>I.</span> Thông tin bệnh nhân</h2>
        <x-print.fields
            :values="[
                'Họ và tên' => $patient->full_name,
                'Ngày sinh' => $patient->date_of_birth?->format('d/m/Y'),
                'Giới tính' => $patient->gender ? \App\Models\AdminDisplay::label($patient->gender) : null,
                'Chẩn đoán' => $diagnoses,
            ]"
            :wide="['Họ và tên', 'Chẩn đoán']"
            divider="Chẩn đoán"
        />
    </section>

    <section class="print-section" aria-labelledby="visit-print-result">
        <h2 class="print-heading" id="visit-print-result"><span>II.</span> Kết quả khám</h2>
        <x-print.fields class="print-facts--wide"
            :values="[
                'Chuyên khoa' => $record->specialty?->name,
                'Ngày tiếp nhận' => $record->checked_in_at?->format('d/m/Y H:i'),
            ]"
        />
        @foreach (['professional_conclusion' => 'Kết luận', 'disposition' => 'Hướng xử trí',
            'treatment_plan' => 'Kế hoạch điều trị', 'instructions' => 'Hướng dẫn'] as $field => $label)
            @if (filled($conclusion->{$field}))
                <h3 class="print-heading print-subheading">{{ $label }}</h3>
                <p class="print-document-text">{{ $field === 'disposition' ? \App\Models\AdminDisplay::label($conclusion->{$field}) : $conclusion->{$field} }}</p>
            @endif
        @endforeach
        @if ($conclusion->has_follow_up)
            <h3 class="print-heading print-subheading">Tái khám</h3>
            <p class="print-document-text">{{ $conclusion->follow_up_date?->format('d/m/Y') ?? 'Theo hướng dẫn của cơ sở y tế' }}</p>
            @if ($conclusion->follow_up_reason)<p class="print-document-text">{{ $conclusion->follow_up_reason }}</p>@endif
        @endif
    </section>

    <section class="print-document-signature" aria-label="Khu vực chữ ký của bác sĩ">
        @if ($conclusion->concluded_at)
            <p class="print-sign-date">Ngày {{ $conclusion->concluded_at->format('d') }} tháng {{ $conclusion->concluded_at->format('m') }} năm {{ $conclusion->concluded_at->format('Y') }}</p>
        @endif
        <h2>BÁC SĨ KẾT LUẬN</h2>
        <p class="print-sign-note">(Ký, ghi rõ họ tên)</p>
        <div class="print-sign-space" aria-hidden="true"></div>
        <p class="print-sign-name">{{ $doctorName ?? 'Chưa ghi nhận' }}</p>
    </section>
</x-print.document>
