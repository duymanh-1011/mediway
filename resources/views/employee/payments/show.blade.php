@extends('employee.layout')

@section('title', 'Chi tiết khoản phải thu')

@push('styles')
    @vite(['resources/css/employee-payments.css', 'resources/css/employee/payments/show.css'])
@endpush

@section('content')

@php
    $visit = $payable->medicalVisit;
    $patient = $visit?->patient;
    $status = $payable->paymentStatus?->name;
    $latestTransaction = $payable->transactions->first(fn ($transaction) => in_array($transaction->transaction_result, ['SUCCESS', 'FAILED', 'PENDING'], true)) ?? $payable->transactions->first();
    $dateReason = app(\App\Services\AppointmentPaymentDateGuard::class)->unavailableReason($payable);

    $successful = $payable->transactions
        ->where('transaction_result', 'SUCCESS');

    $received = (float) $successful->sum('amount');

    $remaining = max(
        0,
        (float) $payable->amount - $received
    );

    $hasReceiptRoute = \Illuminate\Support\Facades\Route::has(
        'employee.payments.receipt'
    );

    $paymentType = match ($payable->payment_type) {
        'EXAMINATION_FEE' => 'Phí khám',
        'SERVICE_FEE' => 'Phí dịch vụ',
        'MEDICINE_FEE' => 'Tiền thuốc',
        default => $payable->payment_type ?? 'Chưa xác định',
    };

    $statusLabel = match ($status) {
        'UNPAID' => 'Chưa thanh toán',
        'PENDING' => 'Chờ xác nhận',
        'PAID' => 'Đã thanh toán',
        'CANCELLED' => 'Đã hủy',
        default => $status ?? 'Chưa xác định',
    };

    $statusClass = match ($status) {
        'PAID' => 'is-success',
        'UNPAID' => 'is-warning',
        'PENDING' => 'is-pending',
        'CANCELLED' => 'is-danger',
        default => 'is-neutral',
    };

    $canCollect = in_array(
        $status,
        ['UNPAID', 'PENDING'],
        true
    )
        && $payable->serviceOrderItem?->serviceStatus?->name !== 'CANCELLED'
        && $received == 0
        && (float) $payable->amount > 0;
@endphp



<div class="f08-detail">

    {{-- HEADER --}}
    <section class="header">

        <div class="header-top">

            <a
                href="{{ route('employee.payments.index', $backQuery) }}"
                class="header-back"
            >
                <span aria-hidden="true">←</span>
                Quay lại danh sách đã lọc
            </a>

            <span class="header-label">
                MEDIWAY · THU NGÂN
            </span>

        </div>

        <h1>Chi tiết khoản phải thu</h1>

        <p>
            {{ $payable->payable_code }}
            · Thông tin đối soát và lịch sử giao dịch
        </p>

    </section>

    {{-- NÚT THU TIỀN --}}
    @if ($canCollect && ! $dateReason)
        <div class="actions">

            <a
                class="collect-btn"
                href="{{ route(
                    'employee.payments.collect.create',
                    array_merge(
                        ['payable' => $payable->id],
                        $backQuery
                    )
                ) }}"
            >
                <span aria-hidden="true">＋</span>
                Ghi nhận thanh toán
            </a>

        </div>
    @elseif ($canCollect && $dateReason && ! $errors->has('payment_date'))
        <p class="f08-payment-warning" role="status">{{ $dateReason }}</p>
    @elseif ($status === 'PAID')
        <p class="f08-payment-state is-success" role="status"><span aria-hidden="true">✓</span> Đã thu tiền</p>
    @elseif ($status === 'CANCELLED')
        <p class="f08-payment-state is-danger" role="status">Đã hủy</p>
    @endif

    {{-- TỔNG QUAN TIỀN --}}
    <div class="summary">

        <div class="amount">

            <span class="amount-label">
                Tổng phải thu
            </span>

            <strong>
                {{ number_format(
                    (float) $payable->amount,
                    0,
                    ',',
                    '.'
                ) }} đ
            </strong>

        </div>

        <div class="amount">

            <span class="amount-label">
                Đã thu thành công
            </span>

            <strong>
                {{ number_format(
                    $received,
                    0,
                    ',',
                    '.'
                ) }} đ
            </strong>

        </div>

        <div class="amount">

            <span class="amount-label">
                Còn phải thu
            </span>

            <strong>
                {{ number_format(
                    $remaining,
                    0,
                    ',',
                    '.'
                ) }} đ
            </strong>

        </div>

    </div>

    {{-- THÔNG TIN KHOẢN THU VÀ GIAO DỊCH --}}
    <section class="panel">
        <div class="panel-heading">
            <h2>Thông tin khoản thu và thanh toán</h2>
            <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
        </div>
        @php
            $facts = [
                'Mã khoản thu' => $payable->payable_code,
                'Họ tên bệnh nhân' => $patient?->full_name,
                'Mã bệnh nhân' => $patient?->patient_code,
                'Loại khoản thu' => $paymentType,
                'Số tiền khoản thu' => number_format((float) $payable->amount, 0, ',', '.').' đ',
            ];
            if ($latestTransaction) {
                $facts += [
                    'Mã giao dịch' => $latestTransaction->transaction_code,
                    'Phương thức thanh toán' => match ($latestTransaction->payment_method) { 'CASH' => 'Tiền mặt', 'QR' => 'Chuyển khoản QR', default => $latestTransaction->payment_method },
                    'Người thu tiền' => $latestTransaction->collector ? trim($latestTransaction->collector->full_name.' · '.$latestTransaction->collector->employee_code, ' ·') : 'Không ghi nhận / tự động',
                    'Kết quả giao dịch' => match ($latestTransaction->transaction_result) { 'SUCCESS' => 'Thành công', 'FAILED' => 'Thất bại', 'PENDING' => 'Chờ xử lý', default => $latestTransaction->transaction_result },
                    'Số tiền giao dịch' => number_format((float) $latestTransaction->amount, 0, ',', '.').' đ',
                    'Ngày giờ thanh toán' => $latestTransaction->paid_at?->format('d/m/Y H:i:s'),
                    'Tham chiếu' => $latestTransaction->provider_transaction_code,
                ];
            }
        @endphp
        <dl class="facts">
            @foreach ($facts as $label => $value)
                @if (filled($value))
                    <div class="fact"><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                @endif
            @endforeach
        </dl>
        <div class="subsection">
            <h3>Nội dung khoản thu</h3>
            <p>{{ $payable->displayContent() }}</p>
            @if ($latestTransaction && $latestTransaction->transaction_result === 'SUCCESS' && $latestTransaction->payment_method === 'CASH' && $hasReceiptRoute && app(\App\Support\EmployeeAccess::class)->allows(auth()->user(), 'payment.view'))
                <p><a class="receipt-btn" href="{{ route('employee.payments.receipt', $latestTransaction->id) }}" target="_blank" rel="noopener noreferrer">Xem hóa đơn</a></p>
            @endif
        </div>
        @if (filled($payable->notes))
            <div class="subsection"><h3>Ghi chú</h3><p>{{ $payable->notes }}</p></div>
        @endif
        @if ($latestTransaction && ($latestTransaction->notes || $latestTransaction->failure_reason))
            <div class="subsection"><h3>Ghi chú giao dịch</h3>@if ($latestTransaction->failure_reason)<p>Lý do lỗi: {{ $latestTransaction->failure_reason }}</p>@endif @if ($latestTransaction->notes)<p>{{ $latestTransaction->notes }}</p>@endif</div>
        @endif
    </section>

    @if ($payable->transactions->count() > 1)
        <section class="panel">
            <details>
                <summary>Lịch sử giao dịch ({{ $payable->transactions->count() }})</summary>
                <div class="history-list">
                    @foreach ($payable->transactions as $transaction)
                        <article class="history-entry">
                            <p><strong>Mã giao dịch:</strong> {{ $transaction->transaction_code }}</p>
                            <p><strong>Kết quả:</strong> {{ match ($transaction->transaction_result) { 'SUCCESS' => 'Thành công', 'FAILED' => 'Thất bại', 'PENDING' => 'Chờ xử lý', default => $transaction->transaction_result ?? 'Chưa xác định' } }}</p>
                            <p><strong>Phương thức:</strong> {{ match ($transaction->payment_method) { 'CASH' => 'Tiền mặt', 'QR' => 'Chuyển khoản QR', default => $transaction->payment_method ?? '—' } }}</p>
                            <p><strong>Số tiền:</strong> {{ number_format((float) $transaction->amount, 0, ',', '.') }} đ</p>
                            <p><strong>Người thu:</strong> {{ $transaction->collector?->full_name ?? 'Không ghi nhận / tự động' }}@if ($transaction->collector?->employee_code) · {{ $transaction->collector->employee_code }}@endif</p>
                            @if ($transaction->provider_transaction_code)<p><strong>Tham chiếu:</strong> {{ $transaction->provider_transaction_code }}</p>@endif
                            @if ($transaction->started_at)<p><strong>Bắt đầu:</strong> {{ $transaction->started_at->format('d/m/Y H:i:s') }}</p>@endif
                            @if ($transaction->paid_at)<p><strong>Thanh toán:</strong> {{ $transaction->paid_at->format('d/m/Y H:i:s') }}</p>@endif
                            @if ($transaction->confirmed_at)<p><strong>Xác nhận:</strong> {{ $transaction->confirmed_at->format('d/m/Y H:i:s') }}</p>@endif
                            @if ($transaction->payment_content)<p><strong>Nội dung:</strong> {{ $transaction->payment_content }}</p>@endif
                            @if ($transaction->failure_reason)<p><strong>Lý do lỗi:</strong> {{ $transaction->failure_reason }}</p>@endif
                            @if ($transaction->notes)<p><strong>Ghi chú:</strong> {{ $transaction->notes }}</p>@endif
                            @if ($transaction->transaction_result === 'SUCCESS' && $transaction->payment_method === 'CASH' && $hasReceiptRoute && app(\App\Support\EmployeeAccess::class)->allows(auth()->user(), 'payment.view'))
                                <a class="receipt-btn" href="{{ route('employee.payments.receipt', $transaction->id) }}" target="_blank" rel="noopener noreferrer">Xem hóa đơn</a>
                            @endif
                        </article>
                    @endforeach
                </div>
            </details>
        </section>
    @endif
    {{-- QUAY LẠI --}}
    <a
        class="back"
        href="{{ route('employee.payments.index', $backQuery) }}"
    >
        <span aria-hidden="true">←</span>
        Trở lại danh sách đã lọc
    </a>

</div>

@endsection
