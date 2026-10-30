@extends('portal.layouts.app')
@section('title', 'Lịch khám - Mediway')
@section('content')
<link rel="stylesheet" href="{{ asset('css/patient-appointments.css') }}">
<section class="appointment-page">
    <div class="portal-shell">
        <a class="appointment-back" href="{{ route('patient.home') }}"><span aria-hidden="true">←</span> Quay lại tài khoản</a>
        <header class="appointment-heading">
            <div><p class="portal-eyebrow">TÀI KHOẢN BỆNH NHÂN</p><h1>Lịch khám của bạn</h1><p>Theo dõi thông tin lịch hẹn tại Mediway.</p></div>
            <div class="appointment-heading__icon" aria-hidden="true">✚</div>
        </header>
        @if ($unavailable)
            <x-portal.alert type="error">Không thể tải thông tin khám lúc này. Vui lòng thử lại sau hoặc liên hệ hỗ trợ.</x-portal.alert>
        @else
            <div class="appointment-layout">
                <section class="appointment-panel" aria-labelledby="upcoming-title">
                    <div class="appointment-panel__header">
                        <div class="appointment-panel__title"><span class="appointment-panel__icon" aria-hidden="true">▦</span><div><h2 id="upcoming-title">Lịch khám sắp tới</h2><p>Thông tin lịch hẹn gần nhất của bạn</p></div></div>
                        @if ($appointment)<x-portal.badge type="info">{{ ['BOOKED' => 'Đã đặt lịch', 'CHECKED_IN' => 'Đã tiếp nhận', 'IN_EXAMINATION' => 'Đang khám'][$appointment->appointmentStatus?->name] ?? 'Chưa có thông tin' }}</x-portal.badge>@endif
                    </div>
                    @if ($appointment)
                        <div class="appointment-panel__body">
                            <div class="appointment-ticket"><div><span>LỊCH HẸN CỦA BẠN</span><strong>{{ $appointment->appointment_date?->format('d/m/Y') ?? 'Chưa có thông tin' }}</strong><p>{{ $appointment->appointmentTimeSlot?->start_time ? substr($appointment->appointmentTimeSlot->start_time, 0, 5) : 'Chưa có thông tin' }}@if ($appointment->appointmentTimeSlot?->end_time) – {{ substr($appointment->appointmentTimeSlot->end_time, 0, 5) }}@endif</p></div><div><span>MÃ LỊCH HẸN</span><strong>{{ $appointment->appointment_code }}</strong></div></div>
                            <p class="appointment-section-label">THÔNG TIN LỊCH HẸN</p>
                            <dl class="appointment-fields">
                                <div class="appointment-field"><dt>Mã lịch hẹn</dt><dd>{{ $appointment->appointment_code }}</dd></div>
                                <div class="appointment-field"><dt>Trạng thái</dt><dd>{{ ['BOOKED' => 'Đã đặt lịch', 'CHECKED_IN' => 'Đã tiếp nhận', 'IN_EXAMINATION' => 'Đang khám'][$appointment->appointmentStatus?->name] ?? 'Chưa có thông tin' }}</dd></div>
                                <div class="appointment-field"><dt>Ngày khám</dt><dd>{{ $appointment->appointment_date?->format('d/m/Y') ?? 'Chưa có thông tin' }}</dd></div>
                                <div class="appointment-field"><dt>Giờ khám</dt><dd>{{ $appointment->appointmentTimeSlot?->start_time ? substr($appointment->appointmentTimeSlot->start_time, 0, 5) : 'Chưa có thông tin' }}</dd></div>
                                <div class="appointment-field appointment-field--wide"><dt>Bệnh nhân</dt><dd>{{ $patient->full_name }}</dd></div>
                            </dl>
                            <p class="appointment-section-label">THÔNG TIN KHÁM</p>
                            <dl class="appointment-fields">
                                <div class="appointment-field"><dt>Bác sĩ</dt><dd>{{ $appointment->doctor?->full_name ?? 'Chưa có thông tin' }}</dd></div>
                                <div class="appointment-field"><dt>Chuyên khoa</dt><dd>{{ $appointment->specialty?->name ?? 'Chưa có thông tin' }}</dd></div>
                                <div class="appointment-field appointment-field--wide"><dt>Phòng khám</dt><dd>{{ $appointment->examinationRoom?->name ?? 'Chưa có thông tin' }}</dd></div>
                            </dl>
                        </div>
                    @else
                        <div class="appointment-empty"><span aria-hidden="true">▦</span><h3>Chưa có lịch khám sắp tới</h3><p>Bạn chưa có lịch khám sắp tới.</p></div>
                    @endif
                </section>
                <aside class="appointment-sidebar">
                    <div class="appointment-help"><strong>Cần hỗ trợ?</strong><p>Liên hệ Mediway nếu bạn cần hỗ trợ về lịch khám.</p><a href="{{ route('public.support') }}">Xem thông tin hỗ trợ <span aria-hidden="true">→</span></a></div>
                </aside>
            </div>
        @endif
    </div>
</section>
@endsection
