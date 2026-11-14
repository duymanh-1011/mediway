@php
    $result = $record->result;
    $order = $record->serviceOrder;
    $visit = $order->medicalVisit;
    $patient = $visit->patient;
    $parameterRows = $result->parameterDisplayRows();
    $availableAttachments = array_filter($attachmentUrls, fn ($type) => $result->hasDocument($type), ARRAY_FILTER_USE_KEY);
    [$techniqueRows, $detailRows] = collect($result->technicalDetailRows())->partition(
        fn ($row) => in_array($row['label'], [\App\Models\ServiceResult::TECHNIQUE_DETAIL_NAME, 'Kỹ thuật'], true)
    );
    $technique = $techniqueRows->pluck('value')->filter(fn ($value) => filled($value))->implode("\n");
    if (filled($result->result_summary) && $result->result_summary !== $result->result_conclusion) {
        $detailRows->push(['label' => 'Mô tả kết quả', 'value' => $result->result_summary]);
    }
    $detailRows = $detailRows->concat($parameterRows);
    $conclusion = $result->result_conclusion;
    if (blank($conclusion)) {
        $conclusion = blank($result->result_summary) && ! $parameterRows && $availableAttachments
            ? 'Nội dung kết quả nằm trong tệp đính kèm đã lưu. Vui lòng xem tệp trong trang chi tiết.'
            : 'Chưa ghi nhận kết luận.';
    }
@endphp

<x-print.document
    :title="'Phiếu kết quả '.$order->service_order_code"
    :heading="$title"
    brand-subtitle="Hồ sơ điều trị"
    :facility="[$visit->examinationRoom?->name]"
    :identifiers="['Mã chỉ định' => $order->service_order_code, 'Mã bệnh nhân' => $patient?->patient_code]"
    :back-url="$backUrl"
    back-label="Quay lại kết quả"
>
    @if ($availableAttachments)
        <x-slot:actions>
            @foreach ($availableAttachments as $type => $url)
                <a href="{{ $url }}">{{ $type === 'pdf' ? 'Xem PDF gốc' : 'Xem ảnh kết quả' }}</a>
            @endforeach
        </x-slot:actions>
    @endif

    <x-print.patient-information :patient="$patient" :visit="$visit"
        :include-profile-details="$includeProfileDetails ?? false" :phone="$phone ?? null" />

    <section class="print-section" aria-labelledby="result-content">
        <h2 class="print-heading" id="result-content"><span>II.</span> Nội dung kết quả</h2>
        <h3 class="print-heading print-result-title">Kết quả {{ $record->medicalService?->name ?: 'dịch vụ' }}</h3>
        <section aria-labelledby="result-technique">
            <p class="print-document-text"><strong id="result-technique">Kỹ thuật:</strong> {{ filled($technique) ? $technique : 'Chưa ghi nhận kỹ thuật.' }}</p>
            @if ($detailRows->isNotEmpty())
                <div class="print-result-details">
                    @foreach ($detailRows as $row)
                        <p class="print-document-text"><strong>{{ $row['label'] }}:</strong> <span>{{ $row['value'] }}</span></p>
                    @endforeach
                </div>
            @endif
        </section>

        <section aria-labelledby="result-conclusion">
            <p class="print-document-text"><strong id="result-conclusion">Kết luận:</strong> {{ $conclusion }}</p>
        </section>
    </section>

    <x-print.signature :label="$record->performerDoctor ? 'BÁC SĨ THỰC HIỆN' : 'NHÂN VIÊN THỰC HIỆN'" :name="$record->performerDoctor?->full_name ?? $record->performer?->full_name"
        :date="$result->resulted_at" fallback="Chưa ghi nhận người thực hiện" />
</x-print.document>
