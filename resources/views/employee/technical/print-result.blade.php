<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Phiếu kết quả dịch vụ</title>
    @vite(['resources/css/technical-result-print.css', 'resources/js/employee.js'])
</head>
<body>
    @php
        $result = $record->result;
        $order = $record->serviceOrder;
        $patient = $order?->medicalVisit?->patient;
        $parameters = \App\Models\ServiceResult::editableParameters($result->result_parameters);
        $parameterRows = $parameters === null ? $result->parameterDisplayRows() : $parameters;
        $details = $result->technicalDetailRows();
        $hasText = filled($result->result_summary) || filled($result->result_conclusion) || count($parameterRows) || count($details);
    @endphp
    <nav class="print-toolbar" aria-label="Thao tác phiếu kết quả">
        <a href="{{ $backUrl }}">← Quay lại kết quả</a>
        <button type="button" data-f08-print>In / Lưu PDF</button>
    </nav>
    <main class="result-paper">
        <header class="result-header">
            <div class="result-header__identity">
                <div class="result-brand"><span aria-hidden="true">✦</span><div><strong>Mediway</strong><small>Hồ sơ điều trị</small></div></div>
                @if (config('mediway.clinic_name'))<div>{{ config('mediway.clinic_name') }}</div>@endif
                @if (config('mediway.clinic_address'))<div>{{ config('mediway.clinic_address') }}</div>@endif
                @if (config('mediway.clinic_phone'))<div>{{ config('mediway.clinic_phone') }}</div>@endif
            </div>
            <div class="result-header__codes">
                <div>Mã chỉ định <strong>{{ $order?->service_order_code ?? 'Chưa cập nhật' }}</strong></div>
                <div>Mã lượt khám <strong>{{ $order?->medicalVisit?->visit_code ?? 'Chưa cập nhật' }}</strong></div>
            </div>
        </header>
        <div class="result-title"><h1>{{ $title }}</h1>@if ($result->resulted_at)<p>{{ $result->resulted_at->format('d/m/Y H:i') }}</p>@endif</div>
        <section class="result-section" aria-labelledby="patient-heading">
            <h2 id="patient-heading">Thông tin bệnh nhân và dịch vụ</h2>
            <dl class="result-facts">
                <div><dt>Họ và tên</dt><dd>{{ $patient?->full_name ?? 'Chưa cập nhật' }}</dd></div>
                <div><dt>Mã bệnh nhân</dt><dd>{{ $patient?->patient_code ?? 'Chưa cập nhật' }}</dd></div>
                <div><dt>Dịch vụ</dt><dd>{{ $record->medicalService?->name ?? 'Chưa cập nhật' }}</dd></div>
                @if ($record->performer?->full_name)<div><dt>Nhân viên thực hiện</dt><dd>{{ $record->performer->full_name }}</dd></div>@endif
            </dl>
        </section>
        @if (filled($result->result_summary))
            <section class="result-section"><h2>Nội dung kết quả / mô tả kỹ thuật</h2><p class="result-text">{{ $result->result_summary }}</p></section>
        @endif
        @if (count($parameterRows))
            <section class="result-section"><h2>Chỉ số đã ghi nhận</h2>
                <table><thead><tr><th scope="col">Tên chỉ số</th><th scope="col">Giá trị</th><th scope="col">Đơn vị</th></tr></thead><tbody>
                    @foreach ($parameterRows as $row)
                        <tr><td>{{ $row['name'] ?? $row['label'] }}</td><td>{{ $row['value'] }}</td><td>{{ $row['unit'] ?? '' }}</td></tr>
                    @endforeach
                </tbody></table>
            </section>
        @endif
        @if (count($details))
            <section class="result-section"><h2>Thông tin bổ sung</h2><dl class="result-details">
                @foreach ($details as $row)<div><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>@endforeach
            </dl></section>
        @endif
        @if (filled($result->result_conclusion))
            <section class="result-section"><h2>Kết luận đã lưu</h2><p class="result-text">{{ $result->result_conclusion }}</p></section>
        @endif
        @if (! $hasText && ($result->hasDocument('pdf') || $result->hasDocument('image')))
            <p>Nội dung kết quả nằm trong tệp đính kèm đã lưu. Vui lòng xem tệp trong trang chi tiết.</p>
        @endif
        <footer class="result-footer">
            <p>Thời điểm in: <span data-result-print-time>{{ now()->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</span></p>
            @if ($record->performer?->full_name)<p>Nhân viên thực hiện: <strong>{{ $record->performer->full_name }}</strong></p>@endif
        </footer>
    </main>
</body>
</html>
