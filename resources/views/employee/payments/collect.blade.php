@extends('employee.layout')

@section('title', 'Ghi nhận thanh toán')

@push('styles')
    @vite('resources/css/employee-payments.css')
@endpush

@section('content')

    @php
        $visit = $payable->medicalVisit;
        $patient = $visit?->patient;
        $employeeInfo = $employee ?? null;

        $status = $payable->paymentStatus?->name;

        $received = (float) $payable->transactions
            ->where('transaction_result', 'SUCCESS')
            ->sum('amount');

        $total = (float) $payable->amount;
        $remaining = max(0, $total - $received);

        $paymentType = match ($payable->payment_type) {
            'EXAMINATION_FEE' => 'Phí khám',
            'SERVICE_FEE' => 'Phí dịch vụ',
            'MEDICINE_FEE' => 'Tiền thuốc',
            default => $payable->payment_type ?? 'Khoản thu',
        };

        $query = $backQuery ?? [];

        $canCollect = in_array(
            $status,
            ['UNPAID', 'PENDING'],
            true
        )
            && $total > 0
            && $received == 0
            && $payable->serviceOrderItem?->serviceStatus?->name !== 'CANCELLED';
    @endphp



    <div class="f08-collect">

        {{-- HEADER XANH DƯƠNG --}}
        <section class="f08-hero">

            <a class="f08-back" href="{{ route(
        'employee.payments.show',
        array_merge(
            ['payable' => $payable->id],
            $query
        )
    ) }}">
                ← Quay lại chi tiết khoản thu
            </a>

            <span class="f08-eyebrow">
                MEDIWAY · THU NGÂN
            </span>

            <h1>Ghi nhận thanh toán</h1>

            <p>
                Kiểm tra thông tin bệnh nhân,
                đối chiếu số tiền và xác nhận
                thanh toán tiền mặt.
            </p>

        </section>

        {{--
        KHÔNG HIỂN THỊ LẠI $errors->any() Ở ĐÂY.

        employee.layout đã hiển thị
        thông báo validation toàn cục.

        Nhờ vậy lỗi chỉ xuất hiện một lần.
        --}}

        <div class="f08-layout">

            <div class="f08-column">

                {{-- THÔNG TIN BỆNH NHÂN --}}
                <section class="f08-card">

                    <h2 class="f08-card-title">
                        <span class="f08-title-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M4 21v-2a8 8 0 0 1 16 0v2" />
                            </svg>
                        </span>

                        Thông tin bệnh nhân
                    </h2>

                    <div class="f08-patient-grid">

                        <div>
                            <span class="f08-info-label">
                                Họ và tên
                            </span>
                            <strong class="f08-info-value">
                                {{ $patient?->full_name ?? 'Chưa ghi nhận' }}
                            </strong>
                        </div>

                        <div>
                            <span class="f08-info-label">
                                Mã bệnh nhân
                            </span>
                            <strong class="f08-info-value">
                                {{ $patient?->patient_code ?? '—' }}
                            </strong>
                        </div>

                        <div>
                            <span class="f08-info-label">
                                Mã lượt khám
                            </span>
                            <strong class="f08-info-value">
                                {{ $visit?->visit_code ?? '—' }}
                            </strong>
                        </div>

                        <div>
                            <span class="f08-info-label">
                                Mã khoản phải thu
                            </span>
                            <strong class="f08-info-value">
                                {{ $payable->payable_code }}
                            </strong>
                        </div>

                    </div>

                </section>

                {{-- FORM THANH TOÁN --}}
                <section class="f08-card">

                    <h2 class="f08-card-title">
                        <span class="f08-title-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <rect x="3" y="6" width="18" height="13" rx="2" />
                                <path d="M3 10h18" />
                                <path d="M7 15h5" />
                            </svg>
                        </span>

                        Thông tin thanh toán
                    </h2>

                    <form id="f08-payment-form" method="POST" action="{{ route(
        'employee.payments.collect.store',
        ['payable' => $payable->id]
    ) }}">
                        @csrf

                        <div class="f08-field">

                            <label for="payment_method">
                                Phương thức thanh toán
                                <span class="f08-required">*</span>
                            </label>

                            <select name="payment_method" id="payment_method" required>
                                <option value="CASH" selected>
                                    Tiền mặt
                                </option>
                            </select>

                            <p class="f08-help">
                                Hiện tại chỉ hỗ trợ thanh toán
                                bằng tiền mặt.
                            </p>

                        </div>

                        <div class="f08-field-grid">

                            <div class="f08-field">

                                <label for="provider_transaction_code">
                                    Mã tham chiếu
                                </label>

                                <input type="text" id="provider_transaction_code" name="provider_transaction_code"
                                    maxlength="100" value="{{ old('provider_transaction_code') }}"
                                    placeholder="Nhập mã nếu có">

                            </div>

                            <div class="f08-field">

                                <label for="payment_content">
                                    Nội dung thanh toán
                                </label>

                                <input type="text" id="payment_content" name="payment_content" maxlength="255"
                                    value="{{ old('payment_content') }}" placeholder="Ví dụ: Thu phí dịch vụ">

                            </div>

                        </div>

                        <div class="f08-field">

                            <label for="notes">
                                Ghi chú
                            </label>

                            <textarea name="notes" id="notes" maxlength="1000"
                                placeholder="Ghi chú bổ sung nếu có...">{{ old('notes') }}</textarea>

                        </div>

                        {{--
                        Backend yêu cầu:
                        confirm_payment = accepted

                        Không dùng confirm_received.
                        --}}
                        <label class="f08-confirm">

                            <input type="checkbox" id="confirm_payment" name="confirm_payment" value="1" required
                                @checked(old('confirm_payment'))>

                            <span class="f08-confirm-content">

                                <strong class="f08-confirm-title">
                                    Tôi xác nhận đã nhận đủ
                                    {{ number_format(
        $remaining,
        0,
        ',',
        '.'
    ) }} đ tiền mặt
                                </strong>

                                <span class="f08-confirm-desc">
                                    Chỉ xác nhận khi đã nhận và
                                    kiểm đếm đủ tiền thực tế
                                    từ bệnh nhân.
                                </span>

                            </span>

                        </label>

                        <div class="f08-actions">

                            <button type="submit" class="f08-btn f08-btn-primary" id="f08-submit" data-pending-label="Đang ghi nhận…" @disabled(!$canCollect)>
                                Xác nhận thu tiền
                            </button>

                            <a class="f08-btn f08-btn-secondary" href="{{ route(
        'employee.payments.show',
        array_merge(
            ['payable' => $payable->id],
            $query
        )
    ) }}">
                                Quay lại
                            </a>

                        </div>

                    </form>

                </section>

            </div>

            {{-- CỘT PHẢI --}}
            <aside class="f08-column">

                <section class="f08-card">

                    <h2 class="f08-card-title">

                        <span class="f08-title-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <rect x="4" y="6" width="16" height="14" rx="2" />
                                <path d="M8 6V4h8v2" />
                                <path d="M8 11h8" />
                                <path d="M8 15h5" />
                            </svg>
                        </span>

                        Tổng kết khoản thu
                    </h2>

                    <div class="f08-money-box">

                        <span>
                            SỐ TIỀN CẦN THANH TOÁN
                        </span>

                        <strong>
                            {{ number_format(
        $remaining,
        0,
        ',',
        '.'
    ) }} đ
                        </strong>

                        <small>
                            Số tiền được lấy từ khoản phải thu
                            đã lưu trong hệ thống.
                        </small>

                    </div>

                    <div class="f08-summary-row">
                        <span>Loại khoản thu</span>
                        <strong>{{ $paymentType }}</strong>
                    </div>

                    <div class="f08-summary-row">
                        <span>Tổng phải thu</span>
                        <strong>
                            {{ number_format($total, 0, ',', '.') }} đ
                        </strong>
                    </div>

                    <div class="f08-summary-row">
                        <span>Đã thu thành công</span>
                        <strong>
                            {{ number_format($received, 0, ',', '.') }} đ
                        </strong>
                    </div>

                    <div class="f08-summary-row is-total">
                        <span>Còn phải thu</span>
                        <strong>
                            {{ number_format($remaining, 0, ',', '.') }} đ
                        </strong>
                    </div>

                    <div class="f08-summary-row">
                        <span>Phương thức</span>
                        <strong>Tiền mặt</strong>
                    </div>

                    <div class="f08-summary-row">
                        <span>Người thu tiền</span>
                        <strong>
                            {{ $employeeInfo?->full_name
        ?? 'Nhân viên đang đăng nhập' }}
                        </strong>
                    </div>

                    <hr class="f08-divider">

                    <div class="f08-summary-row">
                        <span>Mã khoản thu</span>
                        <strong>{{ $payable->payable_code }}</strong>
                    </div>

                    <div class="f08-summary-row">
                        <span>Nội dung</span>
                        <strong>
                            {{ $payable->description ?: $paymentType }}
                        </strong>
                    </div>

                </section>

                <div class="f08-notice">
                    <strong>Lưu ý an toàn thanh toán</strong>

                    <div class="f08-collect-note">
                        Không xác nhận thu tiền trước khi
                        bệnh nhân đã thanh toán đầy đủ.
                        Hệ thống sẽ ghi nhận giao dịch
                        và lưu lịch sử người thực hiện.
                    </div>
                </div>

            </aside>

        </div>

    </div>



@endsection
