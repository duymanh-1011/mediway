@extends('portal.layouts.app')

@push('portal_page_styles')
    @vite('resources/css/payments-index.css')
@endpush

@section('title', 'Khoản cần thanh toán - Mediway')

@section('content')


    <div class="payment-page">
        <section class="portal-page-head">
            <div class="portal-shell">
                <p class="portal-eyebrow">
                    PATIENT PORTAL
                </p>

                <h1>
                    Khoản cần thanh toán
                </h1>

                <p class="portal-lead">
                    Theo dõi các khoản phải thanh toán và lịch sử giao dịch đã được Mediway ghi nhận.
                    Trang này chỉ cung cấp thông tin, chưa thực hiện thanh toán trực tuyến.
                </p>
            </div>
        </section>

        <section class="payment-summary-section">
            <div class="portal-shell">
                <div class="payment-summary-grid">
                    <div class="payment-summary-card">
                        <div class="payment-summary-label">
                            Tổng khoản thu
                        </div>

                        <div class="payment-summary-value">
                            {{ $summary['payable_count'] }}
                        </div>
                    </div>

                    <div class="payment-summary-card">
                        <div class="payment-summary-label">
                            Tổng giá trị
                        </div>

                        <div class="payment-summary-value">
                            {{ number_format($summary['total_amount'], 0, ',', '.') }} ₫
                        </div>
                    </div>

                    <div class="payment-summary-card">
                        <div class="payment-summary-label">
                            Chưa hoàn tất
                        </div>

                        <div class="payment-summary-value">
                            {{ $summary['outstanding_count'] }}
                            khoản
                        </div>
                    </div>

                    <div class="payment-summary-card">
                        <div class="payment-summary-label">
                            Số tiền chưa hoàn tất
                        </div>

                        <div class="payment-summary-value">
                            {{ number_format($summary['outstanding_amount'], 0, ',', '.') }} ₫
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="payment-content-section">
            <div class="portal-shell">
                <div class="payment-section-head">
                    <h2>
                        Danh sách khoản phải thanh toán
                    </h2>

                    <p>
                        Chỉ hiển thị dữ liệu thuộc các lượt khám của tài khoản bệnh nhân hiện tại.
                    </p>
                </div>

                <div class="payment-list">
                    @forelse ($payables as $payable)
                        @php
                            $statusName = $payable->paymentStatus?->name ?? 'UNKNOWN';

                            $statusClass = match ($statusName) {
                                'PAID' => 'is-paid',
                                'PENDING', 'UNPAID' => 'is-pending',
                                'CANCELLED' => 'is-cancelled',
                                default => 'is-default',
                            };

                            $statusLabel = match ($statusName) {
                                'PAID' => 'Đã thanh toán',
                                'PENDING' => 'Đang chờ xác nhận',
                                'UNPAID' => 'Chưa thanh toán',
                                'CANCELLED' => 'Đã hủy',
                                default => $statusName,
                            };

                            $paymentTypeLabel = match ($payable->payment_type) {
                                'EXAMINATION_FEE' => 'Phí khám',
                                'SERVICE_FEE' => 'Phí dịch vụ',
                                default => $payable->payment_type ?: 'Chưa cập nhật',
                            };
                        @endphp

                        <article class="payment-card">
                            <div class="payment-card-top">
                                <div>
                                    <h3 class="payment-code">
                                        {{ $payable->payable_code }}
                                    </h3>

                                    <p class="payment-description">
                                        {{ $payable->description }}
                                    </p>
                                </div>

                                <span class="payment-status {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <div class="payment-detail-grid">
                                <div class="payment-info-item">
                                    <span class="payment-info-label">
                                        Mã lượt khám
                                    </span>

                                    <span class="payment-info-value">
                                        {{ $payable->medicalVisit?->visit_code ?? 'Chưa cập nhật' }}
                                    </span>
                                </div>

                                <div class="payment-info-item">
                                    <span class="payment-info-label">
                                        Loại khoản thu
                                    </span>

                                    <span class="payment-info-value">
                                        {{ $paymentTypeLabel }}
                                    </span>
                                </div>

                                <div class="payment-info-item">
                                    <span class="payment-info-label">
                                        Số tiền
                                    </span>

                                    <span class="payment-info-value payment-amount">
                                        {{ number_format((float) $payable->amount, 0, ',', '.') }} ₫
                                    </span>
                                </div>

                                <div class="payment-info-item">
                                    <span class="payment-info-label">
                                        Ngày phát sinh
                                    </span>

                                    <span class="payment-info-value">
                                        {{ $payable->generated_at?->format('H:i d/m/Y') ?? 'Chưa cập nhật' }}
                                    </span>
                                </div>

                                <div class="payment-info-item">
                                    <span class="payment-info-label">
                                        Hạn thanh toán
                                    </span>

                                    <span class="payment-info-value">
                                        {{ $payable->due_at?->format('H:i d/m/Y') ?? 'Không quy định' }}
                                    </span>
                                </div>

                                <div class="payment-info-item">
                                    <span class="payment-info-label">
                                        Thanh toán lúc
                                    </span>

                                    <span class="payment-info-value">
                                        {{ $payable->paid_at?->format('H:i d/m/Y') ?? 'Chưa thanh toán' }}
                                    </span>
                                </div>
                            </div>

                            <div class="transaction-block">
                                <h4 class="transaction-title">
                                    Lịch sử giao dịch
                                </h4>

                                @if ($payable->transactions->isNotEmpty())
                                    <div class="transaction-list">
                                        @foreach ($payable->transactions as $transaction)
                                                        @php
                                                            $resultLabel = match ($transaction->transaction_result) {
                                                                'SUCCESS' => 'Thành công',
                                                                'FAILED' => 'Thất bại',
                                                                'PENDING' => 'Đang xử lý',
                                                                default => $transaction->transaction_result ?: 'Chưa cập nhật',
                                                            };

                                                            $methodLabel = match ($transaction->payment_method) {
                                                                'CASH' => 'Tiền mặt',
                                                                'QR' => 'QR',
                                                                'BANK_TRANSFER' => 'Chuyển khoản',
                                                                'CARD' => 'Thẻ',
                                                                default => $transaction->payment_method ?: 'Chưa cập nhật',
                                                            };
                                                        @endphp

                                                        <div class="transaction-item">
                                                            <div class="transaction-cell">
                                                                <span class="transaction-label">
                                                                    Mã giao dịch
                                                                </span>

                                                                <span class="transaction-value">
                                                                    {{ $transaction->transaction_code }}
                                                                </span>
                                                            </div>

                                                            <div class="transaction-cell">
                                                                <span class="transaction-label">
                                                                    Số tiền
                                                                </span>

                                                                <span class="transaction-value">
                                                                    {{ number_format((float) $transaction->amount, 0, ',', '.') }} ₫
                                                                </span>
                                                            </div>

                                                            <div class="transaction-cell">
                                                                <span class="transaction-label">
                                                                    Phương thức
                                                                </span>

                                                                <span class="transaction-value">
                                                                    {{ $methodLabel }}
                                                                </span>
                                                            </div>

                                                            <div class="transaction-cell">
                                                                <span class="transaction-label">
                                                                    Kết quả
                                                                </span>

                                                                <span class="transaction-value">
                                                                    {{ $resultLabel }}
                                                                </span>
                                                            </div>

                                                            <div class="transaction-cell">
                                                                <span class="transaction-label">
                                                                    Thời gian xác nhận
                                                                </span>

                                                                <span class="transaction-value">
                                                                    {{ $transaction->confirmed_at?->format('H:i d/m/Y')
                                            ?? $transaction->paid_at?->format('H:i d/m/Y')
                                            ?? $transaction->started_at?->format('H:i d/m/Y')
                                            ?? 'Chưa cập nhật' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="payment-empty">
                                        <h3>
                                            Chưa có giao dịch
                                        </h3>

                                        <p>
                                            Khoản thu này chưa có giao dịch thanh toán được ghi nhận.
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <div class="payment-readonly-notice">
                                Patient Portal chỉ hiển thị thông tin.
                                Bạn không thể thay đổi số tiền, trạng thái khoản thu hoặc tạo giao dịch thanh toán từ trang này.
                            </div>
                        </article>
                    @empty
                        <div class="payment-empty">
                            <h3>
                                Chưa có khoản cần thanh toán
                            </h3>

                            <p>
                                Tài khoản của bạn hiện chưa có khoản phải thanh toán nào được Mediway ghi nhận.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection