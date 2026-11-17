@props([
    'visit', 'employee', 'backUrl', 'backLabel', 'description', 'total',
    'payableCode' => null, 'paymentType' => null, 'method' => null, 'count' => 1, 'received' => 0, 'editable' => false,
])

@php
    $patient = $visit?->patient;
    $formatMoney = fn ($amount) => number_format((float) $amount, 0, ',', '.') . ' đ';
    $remaining = max(0, $total - $received);
    $hasPaymentPanel = isset($paymentPanel);
    $patientDetails = [
        'Họ và tên' => $patient?->full_name,
        'Mã bệnh nhân' => $patient?->patient_code,
        'Mã lượt khám' => $visit?->visit_code,
    ];
    $patientDetails += $hasPaymentPanel
        ? ['Người lập phiếu' => $employee?->full_name]
        : ['Mã khoản phải thu' => $payableCode];
@endphp

<div @class(['f08-collect', 'f08-collect--with-panel' => $hasPaymentPanel])>
    <header class="f08-hero">
        <a class="f08-back" href="{{ $backUrl }}">← {{ $backLabel }}</a>
        <span class="f08-eyebrow">MEDIWAY · THU NGÂN</span>
        <h1>Ghi nhận thanh toán</h1>
        <p>{{ $description }}</p>
    </header>

    <div class="f08-layout">
        <div @class(['f08-column', 'f08-card' => $hasPaymentPanel])>
            <section @class(['f08-card' => ! $hasPaymentPanel])>
                <h2 class="f08-card-title"><span class="f08-title-icon"><x-employee-icon name="user" /></span>Thông tin bệnh nhân</h2>
                <div class="f08-patient-grid">
                    @foreach ($patientDetails as $label => $value)
                        <div>
                            <span class="f08-info-label">{{ $label }}</span>
                            <strong class="f08-info-value" @if ($loop->last && $editable) data-payment-code @endif>{{ $value ?: '—' }}</strong>
                        </div>
                    @endforeach
                </div>
            </section>

            {{ $slot }}
        </div>

        <aside @class(['f08-column', 'f08-card' => $hasPaymentPanel])>
            <section @class(['f08-card' => ! $hasPaymentPanel])>
                <h2 class="f08-card-title"><span class="f08-title-icon"><x-employee-icon name="briefcase" /></span>Tổng kết khoản thu</h2>
                <div class="f08-money-box">
                    <span>{{ $received > 0 ? 'SỐ TIỀN ĐÃ THANH TOÁN' : 'SỐ TIỀN CẦN THANH TOÁN' }}</span>
                    <strong @if ($editable) data-payment-total aria-live="polite" @endif>{{ $formatMoney($received > 0 ? $received : $remaining) }}</strong>
                </div>
                @if ($hasPaymentPanel)
                    {{ $paymentPanel }}
                @else
                    <div class="f08-summary-row"><span>Loại khoản thu</span><strong>{{ $paymentType }}</strong></div>
                    <div class="f08-summary-row"><span>Số khoản thu</span><strong @if ($editable) data-payment-count @endif>{{ $count }}</strong></div>
                    <div class="f08-summary-row"><span>Tổng phải thu</span><strong @if ($editable) data-payment-total @endif>{{ $formatMoney($total) }}</strong></div>
                    <div class="f08-summary-row"><span>Đã thu thành công</span><strong>{{ $formatMoney($received) }}</strong></div>
                    <div class="f08-summary-row is-total"><span>Còn phải thu</span><strong @if ($editable) data-payment-total @endif>{{ $formatMoney($remaining) }}</strong></div>
                    <div class="f08-summary-row"><span>Phương thức</span><strong @if ($editable) data-payment-method @endif>{{ $method }}</strong></div>
                    <div class="f08-summary-row"><span>Người thu tiền</span><strong>{{ $employee?->full_name ?? '—' }}</strong></div>
                @endif
            </section>
        </aside>
    </div>
</div>
