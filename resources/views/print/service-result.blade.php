@php
    $result = $record->result;
    $order = $record->serviceOrder;
    $visit = $order->medicalVisit;
    $patient = $visit->patient;
    $parameterRows = $result->parameterDisplayRows();
    $availableAttachments = array_filter($attachmentUrls, fn ($type) => $result->hasDocument($type), ARRAY_FILTER_USE_KEY);
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
        <section aria-labelledby="result-technique">
            <h3 class="print-heading print-subheading" id="result-technique">1. Kỹ thuật</h3>
            <p class="print-document-text">{{ $record->medicalService?->name ?: 'Chưa ghi nhận kỹ thuật.' }}</p>
            @foreach ($result->technicalDetailRows() as $row)
                <p class="print-document-text"><strong>{{ $row['label'] }}:</strong> <span>{{ $row['value'] }}</span></p>
            @endforeach
        </section>

        <section aria-labelledby="result-conclusion">
            <h3 class="print-heading print-subheading" id="result-conclusion">2. Kết luận</h3>
            @if (filled($result->result_conclusion))
                <p class="print-document-text">{{ $result->result_conclusion }}</p>
            @elseif (! filled($result->result_summary) && ! $parameterRows && $availableAttachments)
                <p class="print-document-text">Nội dung kết quả nằm trong tệp đính kèm đã lưu. Vui lòng xem tệp trong trang chi tiết.</p>
            @else
                <p class="print-document-text">Chưa ghi nhận kết luận.</p>
            @endif

            @if (filled($result->result_summary) && $result->result_summary !== $result->result_conclusion)
                <p class="print-document-text"><strong>Mô tả kết quả:</strong> {{ $result->result_summary }}</p>
            @endif
            @foreach ($parameterRows as $row)
                <p class="print-document-text"><strong>{{ $row['label'] }}:</strong> {{ $row['value'] }}</p>
            @endforeach
        </section>
    </section>

    <x-print.signature label="NHÂN VIÊN THỰC HIỆN" :name="$record->performer?->full_name"
        :date="$result->resulted_at" fallback="Chưa ghi nhận người thực hiện" />
</x-print.document>
