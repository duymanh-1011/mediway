@extends('portal.layouts.app')

@section('title', 'Hủy lịch khám - Mediway')

@push('portal_page_styles')
    @vite('resources/css/appointments-cancel.css')
@endpush

@section('content')
    <div class="mw-appointment-action mw-appointment-action--cancel">

        <section class="mw-action-hero" aria-labelledby="mw-page-title">
            <div class="portal-shell">

                <nav class="mw-action-breadcrumbs" aria-label="Đường dẫn trang">
                    <a href="{{ route('patient.appointments.show', $appointment) }}">
                        Chi tiết lịch hẹn
                    </a>

                    <span class="mw-separator" aria-hidden="true">/</span>
                    <span aria-current="page">Hủy lịch khám</span>
                </nav>

                <p class="mw-action-eyebrow">Quản lý lịch khám</p>

                <h1 id="mw-page-title">Hủy lịch khám</h1>

                <p class="mw-action-intro">
                    Xem lại lịch khám đã đặt và cho biết lý do hủy.
                    Hãy kiểm tra kỹ thông tin trước khi xác nhận.
                </p>

            </div>
        </section>

        <section class="mw-action-body" aria-label="Thông tin và biểu mẫu hủy lịch">

            <div class="portal-shell mw-action-layout">

                <article class="mw-action-panel">

                    <div class="mw-action-panel-heading">
                        <span class="mw-action-icon" aria-hidden="true">×</span>

                        <div>
                            <h2>Gửi yêu cầu hủy lịch</h2>
                            <p>Vui lòng điền lý do chính xác để hoàn tất yêu cầu.</p>
                        </div>
                    </div>

                    @if ($problem)
                        <div class="mw-alert" role="status">
                            <strong>Chưa thể xác nhận hủy lịch.</strong>
                            <p>{{ $problem }}</p>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mw-alert mw-alert--error" role="alert">
                            <strong>Vui lòng kiểm tra lại thông tin.</strong>
                            <p>{{ $errors->first() }}</p>
                        </div>
                    @endif

                    <form class="mw-action-form" action="{{ route('patient.appointments.cancel', $appointment) }}"
                        method="POST" data-mw-cancel-form>
                        @csrf

                        <label class="mw-form-label" for="cancellation_reason">
                            Lý do hủy lịch
                            <span class="mw-form-required" aria-hidden="true">*</span>
                        </label>

                        <textarea id="cancellation_reason" class="mw-textarea" name="cancellation_reason" rows="5"
                            minlength="5" maxlength="2000" required aria-describedby="mw-reason-help mw-reason-counter"
                            data-mw-cancellation-reason placeholder="Ví dụ: Tôi không thể đến khám theo lịch đã đặt..."
                            @disabled($problem !== null)>{{ old('cancellation_reason') }}</textarea>

                        <div class="mw-form-helper-row">
                            <span class="mw-form-help" id="mw-reason-help">
                                Vui lòng nhập ít nhất 5 ký tự.
                            </span>

                            <span class="mw-form-counter" id="mw-reason-counter" data-mw-reason-counter>
                                0 / 2.000 ký tự
                            </span>
                        </div>

                        <div class="mw-action-buttons">

                            <x-portal.button type="submit" :disabled="$problem !== null" class="mw-action-button--danger">
                                Xác nhận hủy lịch
                            </x-portal.button>

                            <x-portal.button :href="route('patient.appointments.show', $appointment)" variant="secondary">
                                Quay lại chi tiết
                            </x-portal.button>

                        </div>

                    </form>

                </article>

                <aside class="mw-action-side" aria-label="Thông tin lịch khám đang hủy">

                    <div class="mw-action-summary">

                        <span class="mw-side-eyebrow">Thông tin lịch khám</span>
                        <h2>Lịch hẹn hiện tại</h2>

                        <div class="mw-appointment-code">
                            {{ $appointment->appointment_code }}
                        </div>

                        <dl class="mw-summary-list">

                            <div class="mw-summary-row">
                                <dt>Ngày khám</dt>
                                <dd>
                                    {{ $appointment->appointment_date?->format('d/m/Y') ?? 'Chưa cập nhật' }}
                                </dd>
                            </div>

                            <div class="mw-summary-row">
                                <dt>Khung giờ</dt>
                                <dd>
                                    {{ substr((string) $appointment->appointmentTimeSlot?->start_time, 0, 5) }}
                                    –
                                    {{ substr((string) $appointment->appointmentTimeSlot?->end_time, 0, 5) }}
                                </dd>
                            </div>

                            <div class="mw-summary-row">
                                <dt>Trạng thái</dt>
                                <dd>
                                    {{ $appointment->appointmentStatus?->description ?? $appointment->appointmentStatus?->name ?? 'Chưa cập nhật' }}
                                </dd>
                            </div>

                        </dl>
                    </div>

                    <div class="mw-action-tip">
                        <strong>Lưu ý trước khi xác nhận</strong>

                        <p>
                            Hành động hủy lịch sẽ được xử lý theo quy định
                            của cơ sở khám. Nếu có khoản thanh toán liên quan,
                            vui lòng kiểm tra riêng thông tin thanh toán.
                        </p>
                    </div>

                </aside>

            </div>

        </section>

    </div>
@endsection