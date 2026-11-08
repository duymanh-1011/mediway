@php
    $visit = $record->medicalVisit;
    $doctorName = $record->doctor?->full_name;
    $prescribedAt = $record->prescribed_at;
    $detail = $patient->detail;
    $address = $detail?->current_address ?: $detail?->permanent_address;
    $diagnoses = $visit->diagnoses->map(function ($diagnosis) {
        $name = $diagnosis->disease?->name ?: $diagnosis->diagnosis_description;
        $code = $diagnosis->disease?->disease_code;

        return $name && $code ? $code.' – '.$name : ($name ?: $code);
    })->filter()->unique()->join('; ');
    $formatNumber = fn ($value) => str_replace('.', ',', (string) (float) $value);
@endphp
<x-print.document
    :title="'Đơn thuốc '.$record->prescription_code"
    heading="ĐƠN THUỐC"
    brand-subtitle="Hồ sơ điều trị"
    :facility="[$visit->examinationRoom?->name]"
    :identifiers="['Mã đơn' => $record->prescription_code, 'Mã bệnh nhân' => $patient->patient_code]"
    :back-url="route('patient.results.visit', ['visit' => $visit->id, 'tab' => 'don-thuoc'])"
    back-label="Quay lại đơn thuốc lượt khám"
    print-label="In đơn thuốc"
>
    @if ($record->hasPdf())
        <x-slot:actions>
            <a href="{{ route('patient.prescriptions.document', $record->id) }}">Xem PDF gốc</a>
        </x-slot:actions>
    @endif

    @if ($record->status !== 'ACTIVE')
        <x-slot:titleDetails>
            <p class="prescription-status">Trạng thái đơn thuốc {{ \App\Models\AdminDisplay::label($record->status) }}</p>
        </x-slot:titleDetails>
    @endif

    <section class="print-section" aria-labelledby="prescription-patient">
        <h2 class="print-heading" id="prescription-patient"><span>I.</span> Thông tin bệnh nhân</h2>
        <x-print.fields
            :values="[
                'Họ và tên' => $patient->full_name,
                'Ngày sinh' => $patient->date_of_birth?->format('d/m/Y'),
                'Giới tính' => $patient->gender ? \App\Models\AdminDisplay::label($patient->gender) : null,
                'Số CCCD' => $detail?->citizen_id_number,
                'Số điện thoại' => $phone,
                'Địa chỉ' => $address,
                'Chẩn đoán' => $diagnoses ?: 'Chưa ghi nhận',
            ]"
            :wide="['Họ và tên', 'Địa chỉ', 'Chẩn đoán']"
            divider="Chẩn đoán"
            fallback="Chưa cập nhật"
        />
    </section>

    <section class="print-section" aria-labelledby="prescribed-medicines">
        <div class="print-section-head">
            <h2 class="print-heading" id="prescribed-medicines"><span>II.</span> Thông tin đơn thuốc</h2>
            <span class="prescription-count">{{ $record->items->count() }} loại thuốc</span>
        </div>
        <table class="prescription-table" role="table" aria-labelledby="prescribed-medicines">
            <thead role="rowgroup">
                <tr role="row">
                    <th class="prescription-cell-index" scope="col" role="columnheader">STT</th>
                    <th class="prescription-cell-name" scope="col" role="columnheader">Tên thuốc</th>
                    <th class="prescription-cell-unit" scope="col" role="columnheader">Đơn vị</th>
                    <th class="prescription-cell-quantity" scope="col" role="columnheader">Số lượng</th>
                    <th scope="col" role="columnheader">Cách dùng</th>
                </tr>
            </thead>
            <tbody role="rowgroup">
                @forelse ($record->items as $item)
                    @php
                        $dosage = collect([
                            $item->dose_per_use ? 'Liều mỗi lần '.$item->dose_per_use : null,
                            $item->times_per_day !== null ? $formatNumber($item->times_per_day).' lần/ngày' : null,
                            $item->duration_days !== null ? $item->duration_days.' ngày' : null,
                        ])->filter()->join(' · ');
                        $method = collect([$item->usage_method, $item->administration_time])->filter()->unique()->join(' · ');
                    @endphp
                    <tr role="row">
                        <td class="prescription-cell-index" role="cell">{{ $loop->iteration }}</td>
                        <th class="prescription-cell-name" scope="row" role="rowheader">
                            <strong>{{ $item->medicine?->name ?? 'Thuốc' }} {{ $item->medicine?->strength }}</strong>
                            @if ($item->medicine?->active_ingredient)
                                <span class="prescription-ingredient">Hoạt chất {{ $item->medicine->active_ingredient }}</span>
                            @endif
                        </th>
                        <td class="prescription-cell-unit" role="cell"><span class="prescription-mobile-label" aria-hidden="true">Đơn vị</span>{{ $item->usage_unit ?: '—' }}</td>
                        <td class="prescription-cell-quantity" role="cell"><span class="prescription-mobile-label" aria-hidden="true">Số lượng</span>{{ $item->quantity !== null ? $formatNumber($item->quantity) : '—' }}</td>
                        <td class="prescription-cell-directions" role="cell">
                            <span class="prescription-mobile-label" aria-hidden="true">Cách dùng</span>
                            @if ($dosage)<div>{{ $dosage }}</div>@endif
                            @if ($method)<div>{{ $method }}</div>@endif
                            @if (! $dosage && ! $method) — @endif
                        </td>
                    </tr>
                @empty
                    <tr role="row"><td class="prescription-table-empty" colspan="5" role="cell">Chưa có chi tiết thuốc.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <x-print.medication-notes :prescription="$record" />

    <section class="prescription-doctor-signature print-document-signature" aria-label="Khu vực ký của bác sĩ kê đơn">
        @if ($prescribedAt)
            <p class="print-sign-date">Ngày {{ $prescribedAt->format('d') }} tháng {{ $prescribedAt->format('m') }} năm {{ $prescribedAt->format('Y') }}</p>
        @endif
        <h2>BÁC SĨ KÊ ĐƠN</h2>
        <p class="print-sign-note">(Ký, ghi rõ họ tên)</p>
        <div class="print-sign-space" aria-hidden="true"></div>
        <p class="print-sign-name">{{ $doctorName ?: 'Chưa cập nhật bác sĩ kê đơn' }}</p>
    </section>
</x-print.document>
