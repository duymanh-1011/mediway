@extends('employee.layout')

@section('title', 'Ghi nhận thanh toán')

@push('styles')
    @vite(['resources/css/employee-payments.css', 'resources/css/employee/payments/collect.css', 'resources/js/employee/payments/qr.js'])
@endpush

@section('content')
    @php
        $firstPayable = $payment->items->first()->payable;
        $awaitingPayment = in_array($payment->status, ['CREATING', 'PENDING'], true);
        $expired = $payment->expires_at->isPast();
        $hasCode = $payment->status === 'PENDING' && $payment->qr_payload && ! $expired;
        $backUrl = route('employee.payments.show', $firstPayable);
    @endphp

    <x-employee.payment-collection
        :visit="$payment->medicalVisit" :employee="$payment->creator"
        :back-url="$backUrl" back-label="Quay lại chi tiết khoản thu"
        description="Đối chiếu số tiền và theo dõi giao dịch chuyển khoản QR."
        :total="$payment->amount"
        :received="$payment->status === 'PAID' ? $payment->amount : 0"
    >
        <section class="f08-qr-fees">
            <h2 class="f08-card-title"><span class="f08-title-icon"><x-employee-icon name="queue" /></span>Khoản thu</h2>
            <ul class="f08-qr-fee-list">
                @foreach ($payment->items as $item)
                    <li class="f08-payment-line">
                        <span class="f08-payment-line-body">
                            <strong>{{ $item->description }}</strong>
                            <small>{{ $item->payable->payable_code }}</small>
                        </span>
                        <strong class="f08-payment-line-amount">{{ number_format($item->amount, 0, ',', '.') }} đ</strong>
                    </li>
                @endforeach
            </ul>
        </section>

        <x-slot:paymentPanel>
            <div class="f08-qr-panel"
                @if ($awaitingPayment)
                    data-qr-status-url="{{ route('employee.payments.qr.status', $payment) }}"
                    data-qr-status="{{ $payment->status }}"
                    data-qr-expired="{{ (int) $expired }}"
                    data-qr-review="{{ (int) (bool) $payment->review_reason }}"
                    data-qr-expires="{{ $payment->expires_at->timestamp }}"
                @endif
            >
                <div class="f08-qr-meta">
                    <div>
                        <span class="f08-info-label">Mã phiếu QR</span>
                        <strong class="f08-info-value">{{ $payment->order_code }}</strong>
                    </div>
                    <span @class(['f08-qr-status', 'is-paid' => $payment->status === 'PAID', 'is-warning' => ($expired && $awaitingPayment) || $payment->review_reason])>{{ $payment->displayStatus() }}</span>
                </div>

                @if ($hasCode)
                    <div class="f08-qr-code">
                        <canvas data-qr-payload="{{ $payment->qr_payload }}" aria-label="Mã QR cho khoản phải thu" role="img"></canvas>
                        <p data-qr-render-error hidden>Chưa hiển thị được mã QR. Vui lòng tải lại trang.</p>
                        <noscript><p>Vui lòng bật JavaScript để hiển thị mã QR.</p></noscript>
                        @if ($payment->provider === 'payos')
                            <p>Mở ứng dụng ngân hàng để quét mã.</p>
                        @endif
                        <small>Hết hạn lúc {{ $payment->expires_at->copy()->timezone('Asia/Ho_Chi_Minh')->format('H:i:s d/m/Y') }}</small>
                    </div>
                @endif

                @if ($payment->review_reason)
                    <div class="f08-notice" role="status">
                        <strong>Cần đối soát</strong>
                        <div>{{ $payment->review_reason }}</div>
                        <div>Đã nhận {{ number_format($payment->review_amount, 0, ',', '.') }} đ @if ($payment->review_reference) · {{ $payment->review_reference }} @endif</div>
                    </div>
                @endif

                @if ($payment->status === 'PAID')
                    <div class="f08-qr-actions">
                        <a class="f08-btn f08-btn-primary" href="{{ route('employee.payments.qr.receipt', $payment) }}">Xem hóa đơn</a>
                    </div>
                @elseif ($awaitingPayment)
                    <div class="f08-qr-actions">
                        @if ($payment->provider === 'demo' && ! $expired)
                            <form method="post" action="{{ route('employee.payments.qr.simulate', $payment) }}">
                                @csrf
                                <button class="f08-btn f08-btn-primary" type="submit">Mô phỏng đã nhận tiền</button>
                            </form>
                        @elseif ($payment->provider === 'payos')
                            <form method="post" action="{{ route('employee.payments.qr.verify', $payment) }}">
                                @csrf
                                <button class="f08-btn f08-btn-primary" type="submit">Kiểm tra giao dịch</button>
                            </form>
                        @endif
                        <form method="post" action="{{ route('employee.payments.qr.cancel', $payment) }}">
                            @csrf
                            <button class="f08-btn f08-btn-secondary" type="submit">Hủy mã QR</button>
                        </form>
                    </div>
                @endif
            </div>
        </x-slot:paymentPanel>
    </x-employee.payment-collection>
@endsection
