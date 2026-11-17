@extends('employee.layout')

@section('title', 'Ghi nhận thanh toán')

@push('styles')
    @vite(['resources/css/employee-payments.css', 'resources/css/employee/payments/collect.css', 'resources/js/employee/payments/collect.js'])
@endpush

@section('content')
    @php
        $backUrl = route('employee.payments.show', ['payable' => $payable->id, ...$backQuery]);
        $selectedIds = array_map('intval', (array) old('payable_ids', [$payable->id]));
        $selected = $payables->whereIn('id', $selectedIds)->whereNotIn('id', $reservedIds);
        $total = $selected->sum('amount');
        $isQr = $qrAvailable && old('payment_method', request('method')) === 'QR';
        $paymentType = match ($payable->payment_type) {
            'EXAMINATION_FEE' => 'Phí khám',
            'SERVICE_FEE' => 'Phí dịch vụ',
            'MEDICINE_FEE' => 'Tiền thuốc',
            default => 'Khoản thu',
        };
        $canSelectAll = $payables->whereNotIn('id', $reservedIds)->count() <= 100;
    @endphp

    <x-employee.payment-collection
        :visit="$payable->medicalVisit" :employee="$employee" :back-url="$backUrl" back-label="Quay lại chi tiết khoản thu"
        description="Kiểm tra thông tin bệnh nhân, chọn khoản thu và phương thức thanh toán."
        :payable-code="$selected->count() === 1 ? $selected->first()->payable_code : null"
        :payment-type="$paymentType" :method="$isQr ? 'Chuyển khoản QR' : 'Tiền mặt'"
        :total="$total" :count="$selected->count()" :editable="true"
    >
        <form id="f08-payment-form" method="POST" action="{{ route('employee.payments.collect.store', ['payable' => $payable->id, ...$backQuery]) }}">
            @csrf
            <input type="hidden" name="selection_submitted" value="1">

            @if ($activeQr)
                <div class="f08-card f08-active-qr" role="status">
                    <div>
                        <strong>Lượt khám đang có phiếu QR</strong>
                        <p class="f08-help">Các khoản trong phiếu đang chờ xử lý.</p>
                    </div>
                    <a class="f08-btn f08-btn-secondary" href="{{ route('employee.payments.qr.show', $activeQr) }}">Mở phiếu QR</a>
                </div>
            @endif

            <section class="f08-card">
                <h2 class="f08-card-title"><span class="f08-title-icon"><x-employee-icon name="queue" /></span>Khoản thu</h2>
                @if ($isService && $payables->count() > 1 && $canSelectAll)
                    <label class="f08-select-all" data-payment-all-row hidden>
                        <input type="checkbox" data-payment-all>
                        Chọn tất cả khoản có thể thu
                    </label>
                @elseif (! $canSelectAll)
                    <p class="f08-help">Chọn tối đa 100 khoản mỗi lần thanh toán.</p>
                @endif
                @forelse ($payables as $item)
                    @php($reserved = in_array((int) $item->id, $reservedIds, true))
                    <label class="f08-payment-line">
                        @if ($isService)
                            <input type="checkbox" name="payable_ids[]" value="{{ $item->id }}" data-payment-item
                                data-amount="{{ $item->amount }}" data-code="{{ $item->payable_code }}"
                                @checked($selected->contains('id', $item->id)) @disabled($reserved)>
                        @elseif (! $reserved)
                            <input type="hidden" name="payable_ids[]" value="{{ $item->id }}" data-payment-item
                                data-amount="{{ $item->amount }}" data-code="{{ $item->payable_code }}">
                        @endif
                        <span class="f08-payment-line-body">
                            <strong>{{ $item->displayContent() }}</strong>
                            <small>{{ $item->payable_code }}@if ($reserved) · Trong phiếu QR đang mở @endif</small>
                        </span>
                        <strong class="f08-payment-line-amount">{{ number_format((float) $item->amount, 0, ',', '.') }} đ</strong>
                    </label>
                @empty
                    <p class="f08-help">Không còn khoản phí dịch vụ có thể thu trong lượt khám này.</p>
                @endforelse
            </section>

            <section class="f08-card">
                <h2 class="f08-card-title"><span class="f08-title-icon"><x-employee-icon name="payment" /></span>Thông tin thanh toán</h2>
                <fieldset class="f08-methods">
                    <legend>Phương thức thanh toán <span class="f08-required">*</span></legend>
                    <div class="f08-method-options">
                        <label><input type="radio" name="payment_method" value="CASH" @checked(! $isQr)> <span>Tiền mặt</span></label>
                        @if ($qrAvailable)
                            <label><input type="radio" name="payment_method" value="QR" @checked($isQr)> <span>Chuyển khoản QR</span></label>
                        @endif
                    </div>
                </fieldset>

                <fieldset class="f08-cash-fields" data-cash-fields @if ($isQr) hidden disabled @endif>
                    <div class="f08-field-grid">
                        <div class="f08-field">
                            <label for="provider_transaction_code">Mã tham chiếu</label>
                            <input id="provider_transaction_code" name="provider_transaction_code" maxlength="120" placeholder="Nhập mã nếu có" value="{{ old('provider_transaction_code') }}">
                        </div>
                        <div class="f08-field">
                            <label for="payment_content">Nội dung thanh toán</label>
                            <input id="payment_content" name="payment_content" maxlength="255" placeholder="Ví dụ: Thu phí dịch vụ" value="{{ old('payment_content') }}">
                        </div>
                    </div>
                    <div class="f08-field">
                        <label for="notes">Ghi chú</label>
                        <textarea id="notes" name="notes" placeholder="Ghi chú bổ sung nếu có">{{ old('notes') }}</textarea>
                    </div>
                    <label class="f08-confirm">
                        <input type="checkbox" name="confirm_payment" value="1" @checked(old('confirm_payment'))>
                        <span class="f08-confirm-content">
                            <strong class="f08-confirm-title">Tôi xác nhận đã nhận đủ <span data-payment-total>{{ number_format($total, 0, ',', '.') }} đ</span> tiền mặt</strong>
                            <span class="f08-confirm-desc">Kiểm đếm đủ tiền trước khi xác nhận.</span>
                        </span>
                    </label>
                </fieldset>

                <p class="f08-qr-instructions" data-qr-fields @if (! $isQr) hidden @endif>Mã QR dùng tổng số tiền của các khoản đã chọn. Bệnh nhân có thể quét mã ngay tại quầy.</p>

                <div class="f08-actions">
                    <button type="submit" class="f08-btn f08-btn-primary" id="f08-submit" @disabled($selected->isEmpty())>{{ $isQr ? 'Tạo mã QR' : 'Xác nhận thu tiền' }}</button>
                    <a class="f08-btn f08-btn-secondary" href="{{ $backUrl }}">Quay lại</a>
                </div>
            </section>
        </form>
    </x-employee.payment-collection>
@endsection