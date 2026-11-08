@php
    $result = $record->result;
    $order = $record->serviceOrder;
    $visit = $order->medicalVisit;
    $patient = $visit->patient;
    $parameterRows = \App\Models\ServiceResult::editableParameters($result->result_parameters) ?? $result->parameterDisplayRows();
    $details = $result->technicalDetailRows();
    $hasText = filled($result->result_summary) || filled($result->result_conclusion) || $parameterRows || $details;
    $availableAttachments = array_filter($attachmentUrls, fn ($type) => $result->hasDocument($type), ARRAY_FILTER_USE_KEY);
@endphp

<x-print.document
    :title="'Phiếu kết quả '.$order->service_order_code"
    :heading="$title"
    brand-subtitle="Hồ sơ điều trị"
    :facility="[config('mediway.clinic_name'), $visit->examinationRoom?->name, config('mediway.clinic_address'), config('mediway.clinic_phone')]"
    :identifiers="['Mã chỉ định' => $order->service_order_code, 'Mã bệnh nhân' => $patient?->patient_code, 'Mã lượt khám' => $visit->visit_code]"
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

    <section class="print-section" aria-labelledby="result-patient">
        <h2 class="print-heading" id="result-patient"><span>I.</span> Thông tin bệnh nhân</h2>
        <x-print.fields
            :values="[
                'Họ và tên' => $patient?->full_name,
                'Ngày sinh' => $patient?->date_of_birth?->format('d/m/Y'),
                'Giới tính' => $patient?->gender ? \App\Models\AdminDisplay::label($patient->gender) : null,
            ]"
            :wide="['Họ và tên']"
        />
    </section>

    <section class="print-section" aria-labelledby="result-content">
        <h2 class="print-heading" id="result-content"><span>II.</span> Nội dung kết quả</h2>
        <x-print.fields class="print-facts--wide"
            :values="[
                'Dịch vụ' => $record->medicalService?->name,
                'Thời điểm có kết quả' => $result->resulted_at?->format('d/m/Y H:i'),
                'Người thực hiện' => $record->performer?->full_name,
            ]"
            :wide="['Dịch vụ']"
        />

        @if (filled($result->result_summary))
            <h3 class="print-heading print-subheading">Mô tả kết quả</h3>
            <p class="print-document-text">{{ $result->result_summary }}</p>
        @endif

        @if ($parameterRows)
            <h3 class="print-heading print-subheading" id="result-parameters">Chỉ số đã ghi nhận</h3>
            <div class="print-table-wrap" tabindex="0" role="region" aria-labelledby="result-parameters">
                <table class="print-table" aria-labelledby="result-parameters">
                    <thead><tr><th scope="col">Tên chỉ số</th><th scope="col">Giá trị</th><th scope="col">Đơn vị</th></tr></thead>
                    <tbody>
                        @foreach ($parameterRows as $row)
                            <tr><th scope="row">{{ $row['name'] ?? $row['label'] }}</th><td>{{ $row['value'] }}</td><td>{{ $row['unit'] ?? '—' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($details)
            <h3 class="print-heading print-subheading" id="result-details">Thông tin bổ sung</h3>
            <div class="print-table-wrap" tabindex="0" role="region" aria-labelledby="result-details">
                <table class="print-table" aria-labelledby="result-details">
                    <thead><tr><th class="print-table-label" scope="col">Mục</th><th scope="col">Nội dung</th></tr></thead>
                    <tbody>
                        @foreach ($details as $row)
                            <tr><th scope="row">{{ $row['label'] }}</th><td class="print-document-text">{{ $row['value'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if (filled($result->result_conclusion))
            <h3 class="print-heading print-subheading">Kết luận đã lưu</h3>
            <p class="print-document-text">{{ $result->result_conclusion }}</p>
        @endif
        @if (! $hasText && $availableAttachments)
            <p class="print-document-text">Nội dung kết quả nằm trong tệp đính kèm đã lưu. Vui lòng xem tệp trong trang chi tiết.</p>
        @endif
    </section>

    @if ($record->performer?->full_name)
        <section class="print-document-signature" aria-label="Người thực hiện">
            @if ($result->resulted_at)
                <p class="print-sign-date">Ngày {{ $result->resulted_at->format('d') }} tháng {{ $result->resulted_at->format('m') }} năm {{ $result->resulted_at->format('Y') }}</p>
            @endif
            <h2>NHÂN VIÊN THỰC HIỆN</h2>
            <p class="print-sign-note">(Ký, ghi rõ họ tên)</p>
            <div class="print-sign-space" aria-hidden="true"></div>
            <p class="print-sign-name">{{ $record->performer->full_name }}</p>
        </section>
    @endif
    <footer class="receipt-footer">
        <p>Thời điểm in: <span data-result-print-time>{{ now()->format('d/m/Y H:i') }}</span></p>
    </footer>
</x-print.document>
