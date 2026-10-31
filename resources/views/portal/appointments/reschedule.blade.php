
@extends('portal.layouts.app')

@section('title', 'Đổi ngày/giờ khám - Mediway')

@push('portal_page_styles')
    @vite('resources/css/appointments-reschedule.css')
@endpush

@section('content')
<div class="mw-appointment-action mw-appointment-action--reschedule">

    <section class="mw-action-hero" aria-labelledby="mw-page-title">

        <div class="portal-shell">

            <nav class="mw-action-breadcrumbs" aria-label="Đường dẫn trang">

                <a href="{{ route('patient.appointments.show', $appointment) }}">
                    Chi tiết lịch hẹn
                </a>

                <span class="mw-separator" aria-hidden="true">/</span>

                <span aria-current="page">Đổi ngày/giờ khám</span>

            </nav>

            <p class="mw-action-eyebrow">Quản lý lịch khám</p>

            <h1 id="mw-page-title">Đổi ngày / giờ khám</h1>

            <p class="mw-action-intro">
                Chọn thời gian thuận tiện hơn,
                cùng bác sĩ và chuyên khoa đã đăng ký.
            </p>

        </div>

    </section>

    <section class="mw-action-body" aria-label="Chọn lịch khám mới">

        <div class="portal-shell mw-action-layout">

            <article class="mw-action-panel">

                <div class="mw-action-panel-heading">

                    <span class="mw-action-icon" aria-hidden="true">↗</span>

                    <div>
                        <h2>Chọn khung giờ mới</h2>
                        <p>Các khung giờ được hiển thị dựa trên lịch còn khả dụng.</p>
                    </div>

                </div>

                @if ($problem)
                    <div class="mw-alert" role="status">
                        <strong>Chưa thể xác nhận đổi lịch.</strong>
                        <p>{{ $problem }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mw-alert mw-alert--error" role="alert">
                        <strong>Vui lòng kiểm tra lại thông tin.</strong>
                        <p>{{ $errors->first() }}</p>
                    </div>
                @endif

                <form
                    class="mw-action-form"
                    action="{{ route('patient.appointments.reschedule', $appointment) }}"
                    method="POST"
                    data-mw-reschedule-form
                >
                    @csrf

                    <fieldset class="mw-slot-fieldset" @disabled($problem !== null)>

                        <legend class="mw-slot-legend">
                            Các lựa chọn đang có
                        </legend>

                        <div class="mw-slot-list">

                            @forelse ($slots as $slot)

                                <label class="mw-slot-card">

                                    <input
                                        class="mw-slot-radio"
                                        type="radio"
                                        name="appointment_time_slot_id"
                                        value="{{ $slot->id }}"
                                        @checked((string) old('appointment_time_slot_id') === (string) $slot->id)
                                        required
                                    >

                                    <span class="mw-slot-content">

                                        <span class="mw-slot-primary">
                                            {{ $slot->doctorSchedule->work_date->format('d/m/Y') }}
                                            ·
                                            {{ substr((string) $slot->start_time, 0, 5) }}
                                            –
                                            {{ substr((string) $slot->end_time, 0, 5) }}
                                        </span>

                                        <span class="mw-slot-secondary">
                                            Phòng:
                                            {{ $slot->doctorSchedule->examinationRoom?->name ?? 'Chưa cập nhật' }}
                                        </span>

                                    </span>

                                </label>

                            @empty

                                <p class="mw-slot-empty">
                                    Hiện chưa có khung giờ phù hợp còn chỗ.
                                    Vui lòng quay lại sau.
                                </p>

                            @endforelse

                        </div>

                    </fieldset>

                    <div class="mw-action-buttons">

                        <x-portal.button
                            type="submit"
                            :disabled="$problem !== null || $slots->isEmpty()"
                            class="mw-action-button--primary"
                        >
                            Xác nhận đổi lịch
                        </x-portal.button>

                        <x-portal.button
                            :href="route('patient.appointments.show', $appointment)"
                            variant="secondary"
                        >
                            Quay lại chi tiết
                        </x-portal.button>

                    </div>

                </form>

            </article>

            <aside class="mw-action-side" aria-label="Thông tin lịch khám hiện tại">

                <div class="mw-action-summary">

                    <span class="mw-side-eyebrow">Lịch khám hiện tại</span>

                    <h2>Đang được đặt</h2>

                    <div class="mw-appointment-code">
                        {{ $appointment->appointment_code }}
                    </div>

                    <dl class="mw-summary-list">

                        <div class="mw-summary-row">
                            <dt>Ngày khám đã đặt</dt>
                            <dd>
                                {{ $appointment->appointment_date?->format('d/m/Y') ?? 'Chưa cập nhật' }}
                            </dd>
                        </div>

                        <div class="mw-summary-row">
                            <dt>Khung giờ đã đặt</dt>
                            <dd>
                                {{ substr((string) $appointment->appointmentTimeSlot?->start_time, 0, 5) }}
                                –
                                {{ substr((string) $appointment->appointmentTimeSlot?->end_time, 0, 5) }}
                            </dd>
                        </div>

                    </dl>

                </div>

                <div class="mw-action-tip">

                    <strong>Thông tin hữu ích</strong>

                    <p>
                        Chọn một khung giờ ở bên trái, sau đó xác nhận.
                        Lịch mới chỉ được ghi nhận khi hệ thống xử lý
                        yêu cầu thành công.
                    </p>

                </div>

            </aside>

        </div>

    </section>

</div>
@endsection
