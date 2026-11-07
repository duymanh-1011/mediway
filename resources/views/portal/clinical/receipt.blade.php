<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phiếu thu {{ $receipt->transaction_code }} - Mediway</title>
    @vite(['resources/css/payment-receipt.css', 'resources/js/employee.js'])
</head>
<body>
    @php
        $payable = $receipt->payable;
        $receivedAt = $receipt->confirmed_at ?? $receipt->paid_at ?? $receipt->started_at ?? $receipt->created_at;
    @endphp
    <div class="screen-wrap">
        <nav class="toolbar" aria-label="Thao tác phiếu thu">
            <a href="{{ route('patient.results.visit', ['visit' => $payable->medical_visit_id, 'tab' => 'giay-to']) }}">← Quay lại giấy tờ</a>
            <button type="button" data-f08-print>In phiếu thu</button>
        </nav>
        <main class="paper">
            <header class="document-head">
                <div class="brand-line"><span class="brand-icon" aria-hidden="true">✦</span><div><p class="brand-name">Mediway</p><p class="brand-sub">Chứng từ thanh toán</p></div></div>
                <div class="document-meta"><span class="meta-label">Mã giao dịch</span> <strong>{{ $receipt->transaction_code }}</strong><br><span class="meta-label">Mã khoản thu</span> <strong>{{ $payable->payable_code }}</strong></div>
            </header>
            <div class="document-title"><h1>PHIẾU THU</h1><p>{{ $payable->displayContent() }}</p><span class="status">Đã thanh toán</span></div>
            <section class="section" aria-labelledby="receipt-info">
                <h2 id="receipt-info" class="section-title">Thông tin giao dịch</h2>
                <dl class="info-grid">
                    <div class="info-item"><dt class="field-label">Bệnh nhân</dt><dd class="field-value">{{ $patient->full_name }} · {{ $patient->patient_code }}</dd></div>
                    <div class="info-item"><dt class="field-label">Mã lượt khám</dt><dd class="field-value">{{ $payable->medicalVisit->visit_code }}</dd></div>
                    <div class="info-item"><dt class="field-label">Thời gian</dt><dd class="field-value">{{ $receivedAt?->format('d/m/Y H:i') ?? 'Chưa cập nhật' }}</dd></div>
                    <div class="info-item"><dt class="field-label">Phương thức</dt><dd class="field-value">{{ \App\Models\AdminDisplay::label($receipt->payment_method) }}</dd></div>
                    @if ($receipt->collector)<div class="info-item"><dt class="field-label">Nhân viên thu tiền</dt><dd class="field-value">{{ $receipt->collector->full_name }}</dd></div>@endif
                </dl>
                <div class="total-box"><span class="total-label">Số tiền đã thu</span> <strong class="total-value">{{ number_format((float) $receipt->amount, 0, ',', '.') }} ₫</strong></div>
            </section>
            <footer class="receipt-footer">Phiếu thu được hiển thị từ giao dịch đã ghi nhận trên Mediway.</footer>
        </main>
    </div>
</body>
</html>
