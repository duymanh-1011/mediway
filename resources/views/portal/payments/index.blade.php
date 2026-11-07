@extends('portal.layouts.app')

@push('portal_page_styles')
    @vite('resources/css/payments-index.css')
@endpush

@section('title', 'Khoản cần thanh toán - Mediway')

@section('content')
    <div class="payment-page">
        <section class="portal-page-head">
            <div class="portal-shell">
                <a class="portal-back" href="{{ route('patient.home') }}">← Quay lại tài khoản</a>
                <p class="portal-eyebrow">TÀI KHOẢN BỆNH NHÂN</p>
                <h1>Khoản cần thanh toán</h1>
                <p class="portal-lead">Vui lòng thanh toán tại quầy. Bạn có thể tra cứu các khoản đã thu trong mục Viện phí của từng lượt khám.</p>
            </div>
        </section>

        <section class="payment-summary-section" aria-label="Tổng quan các khoản cần thanh toán">
            <div class="portal-shell">
                <div class="payment-summary-grid payment-summary-grid--outstanding">
                    <div class="payment-summary-card">
                        <div class="payment-summary-label">Khoản cần thanh toán</div>
                        <div class="payment-summary-value">{{ $payables->count() }}</div>
                    </div>
                    <div class="payment-summary-card">
                        <div class="payment-summary-label">Tổng số tiền</div>
                        <div class="payment-summary-value">{{ number_format((float) $amount, 0, ',', '.') }} ₫</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="payment-content-section">
            <div class="portal-shell">
                <div class="payment-section-head">
                    <h2>Danh sách khoản phải thanh toán</h2>
                </div>

                <div class="payment-list">
                    @forelse ($payables as $payable)
                        @php($unavailable = app(\App\Services\AppointmentPaymentDateGuard::class)->unavailableReason($payable))
                        <article class="payment-card" id="khoan-thu-{{ $payable->id }}">
                            <div class="payment-card-top">
                                <div>
                                    <h3 class="payment-code">{{ $payable->payable_code }}</h3>
                                    <p class="payment-description">{{ $payable->displayContent() }}</p>
                                </div>
                                <span class="payment-status is-pending">{{ $payable->paymentStatus?->name === 'PENDING' ? 'Chờ xác nhận' : 'Chưa thanh toán' }}</span>
                            </div>

                            <div class="payment-detail-grid">
                                <div class="payment-info-item">
                                    <span class="payment-info-label">Mã lượt khám</span>
                                    <a class="payment-info-value" href="{{ route('patient.results.visit', $payable->medical_visit_id) }}">{{ $payable->medicalVisit?->visit_code ?? 'Chi tiết lượt khám' }}</a>
                                </div>
                                <div class="payment-info-item">
                                    <span class="payment-info-label">Ngày phát sinh</span>
                                    <span class="payment-info-value">{{ $payable->generated_at?->format('H:i d/m/Y') ?? 'Chưa cập nhật' }}</span>
                                </div>
                                <div class="payment-info-item">
                                    <span class="payment-info-label">Số tiền</span>
                                    <span class="payment-info-value payment-amount">{{ number_format((float) $payable->amount, 0, ',', '.') }} ₫</span>
                                </div>
                            </div>
                            @if ($unavailable)
                                <p class="payment-description">{{ $unavailable }}</p>
                            @endif
                        </article>
                    @empty
                        <x-portal.empty-state title="Bạn không có khoản cần thanh toán">
                            Các giao dịch đã thanh toán có thể xem trong mục Viện phí của từng lượt khám.
                        </x-portal.empty-state>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection
