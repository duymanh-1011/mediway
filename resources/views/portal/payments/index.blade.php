@extends('portal.layouts.app')

@section('title', 'Khoản cần thanh toán - Mediway')

@section('content')
    <style>
        .payment-page {
            --payment-text: #0f172a;
            --payment-soft: #475569;
            --payment-muted: #64748b;
            --payment-border: #dce6ef;
            --payment-border-soft: #e8eef4;
            --payment-primary-soft: #eff6ff;
            --payment-hero: #f1f6fd;
            --payment-summary: #eef7fb;
            --payment-white: #ffffff;
            --payment-green-soft: #ecfdf5;
            --payment-green: #047857;
            --payment-yellow-soft: #fffbeb;
            --payment-yellow: #a16207;
            --payment-red-soft: #fef2f2;
            --payment-red: #b91c1c;
            --payment-radius: 18px;
            --payment-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        }

        .payment-page .portal-page-head {
            padding: 42px 0 38px;
            background: var(--payment-hero);
            border-bottom: 1px solid #e1eaf3;
        }

        .payment-page .portal-page-head h1 {
            margin: 8px 0 10px;
            color: var(--payment-text);
            font-size: clamp(32px, 4vw, 52px);
            line-height: 1.15;
            font-weight: 800;
        }

        .payment-page .portal-lead {
            max-width: 780px;
            margin: 0;
            color: var(--payment-muted);
            font-size: 15px;
            line-height: 1.7;
        }

        .payment-summary-section {
            padding: 28px 0;
            background: var(--payment-summary);
            border-bottom: 1px solid #dfebf2;
        }

        .payment-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .payment-summary-card {
            min-width: 0;
            padding: 20px;
            background: #fff;
            border: 1px solid var(--payment-border);
            border-radius: 16px;
            box-shadow: var(--payment-shadow);
        }

        .payment-summary-label {
            margin-bottom: 8px;
            color: var(--payment-muted);
            font-size: 13px;
            font-weight: 700;
        }

        .payment-summary-value {
            color: var(--payment-text);
            font-size: 25px;
            font-weight: 800;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .payment-content-section {
            padding: 44px 0 72px;
            background: var(--payment-white);
        }

        .payment-section-head {
            margin-bottom: 20px;
        }

        .payment-section-head h2 {
            margin: 0 0 5px;
            color: var(--payment-text);
            font-size: 27px;
            font-weight: 800;
        }

        .payment-section-head p {
            margin: 0;
            color: var(--payment-muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .payment-list {
            display: grid;
            gap: 18px;
        }

        .payment-card {
            padding: 22px;
            background: #fff;
            border: 1px solid var(--payment-border);
            border-radius: var(--payment-radius);
            box-shadow: var(--payment-shadow);
        }

        .payment-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--payment-border-soft);
        }

        .payment-code {
            margin: 0 0 5px;
            color: var(--payment-text);
            font-size: 20px;
            font-weight: 800;
        }

        .payment-description {
            margin: 0;
            color: var(--payment-muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .payment-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .payment-status.is-paid {
            background: var(--payment-green-soft);
            color: var(--payment-green);
        }

        .payment-status.is-pending {
            background: var(--payment-yellow-soft);
            color: var(--payment-yellow);
        }

        .payment-status.is-cancelled {
            background: var(--payment-red-soft);
            color: var(--payment-red);
        }

        .payment-status.is-default {
            background: var(--payment-primary-soft);
            color: #1d4ed8;
        }

        .payment-detail-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0 24px;
            margin-top: 18px;
        }

        .payment-info-item {
            min-width: 0;
            padding: 12px 0;
            border-bottom: 1px solid var(--payment-border-soft);
        }

        .payment-info-label {
            display: block;
            margin-bottom: 4px;
            color: var(--payment-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .payment-info-value {
            display: block;
            color: var(--payment-text);
            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .payment-amount {
            color: #0f766e;
            font-size: 17px;
            font-weight: 800;
        }

        .transaction-block {
            margin-top: 20px;
        }

        .transaction-title {
            margin: 0 0 12px;
            color: var(--payment-text);
            font-size: 16px;
            font-weight: 800;
        }

        .transaction-list {
            display: grid;
            gap: 10px;
        }

        .transaction-item {
            display: grid;
            grid-template-columns:
                minmax(120px, 1.1fr) minmax(110px, 1fr) minmax(110px, 1fr) minmax(110px, 1fr) minmax(150px, 1.2fr);
            gap: 12px;
            align-items: center;
            padding: 14px;
            border: 1px solid var(--payment-border-soft);
            border-radius: 12px;
            background: #f8fbff;
        }

        .transaction-cell {
            min-width: 0;
        }

        .transaction-label {
            display: block;
            margin-bottom: 3px;
            color: var(--payment-muted);
            font-size: 11px;
            font-weight: 700;
        }

        .transaction-value {
            display: block;
            color: var(--payment-text);
            font-size: 13px;
            font-weight: 600;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .payment-readonly-notice {
            margin-top: 18px;
            padding: 14px 16px;
            border-radius: 12px;
            background: #f8fafc;
            color: var(--payment-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .payment-empty {
            padding: 30px;
            background: #fff;
            border: 1px dashed #cbd8e6;
            border-radius: var(--payment-radius);
            text-align: center;
        }

        .payment-empty h3 {
            margin: 0 0 7px;
            color: var(--payment-text);
            font-size: 18px;
            font-weight: 800;
        }

        .payment-empty p {
            margin: 0;
            color: var(--payment-muted);
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 900px) {
            .payment-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .payment-detail-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .transaction-item {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .payment-page .portal-page-head {
                padding: 30px 0 28px;
            }

            .payment-page .portal-page-head h1 {
                font-size: 32px;
            }

            .payment-summary-section {
                padding: 20px 0;
            }

            .payment-summary-grid,
            .payment-detail-grid,
            .transaction-item {
                grid-template-columns: 1fr;
            }

            .payment-content-section {
                padding: 30px 0 50px;
            }

            .payment-card {
                padding: 16px;
            }

            .payment-card-top {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

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