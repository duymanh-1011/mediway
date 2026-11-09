@extends('employee.layout')
@section('title', 'Chi tiết lịch hẹn')
@section('content')
<section class="employee-section reception-work">
    <a class="employee-card__link" href="{{ route('employee.appointments.index') }}">← Quay lại danh sách lịch hẹn</a>
    <div class="employee-section__heading"><div><p class="portal-eyebrow">TIẾP ĐÓN</p><h1>Chi tiết lịch hẹn</h1><p>Kiểm tra thông tin trước khi tiếp nhận.</p></div></div>
    <section class="employee-card"><h2>{{ $appointment->appointment_code }}</h2>
        <dl class="employee-facts"><div><dt>Bệnh nhân</dt><dd>{{ $appointment->patient?->full_name ?? '—' }}</dd></div><div><dt>Mã bệnh nhân</dt><dd>{{ $appointment->patient?->patient_code ?? '—' }}</dd></div>
        <div><dt>Ngày hẹn</dt><dd>{{ $appointment->appointment_date?->format('d/m/Y') ?? '—' }}</dd></div><div><dt>Khung giờ</dt><dd>{{ $appointment->appointmentTimeSlot?->start_time ?? '—' }} – {{ $appointment->appointmentTimeSlot?->end_time ?? '—' }}</dd></div>
        <div><dt>Bác sĩ</dt><dd>{{ $appointment->doctor?->full_name ?? '—' }}</dd></div><div><dt>Chuyên khoa</dt><dd>{{ $appointment->specialty?->name ?? '—' }}</dd></div>
        <div><dt>Phòng khám</dt><dd>{{ $appointment->examinationRoom?->name ?? '—' }}</dd></div><div><dt>Trạng thái lịch</dt><dd>{{ \App\Models\AdminDisplay::label($appointment->displayStatus()) }}</dd></div>
        <div><dt>Lượt khám</dt><dd>{{ $appointment->medicalVisit?->visit_code ?? 'Chưa có lượt khám' }}</dd></div><div><dt>Thời gian tiếp nhận</dt><dd>{{ $appointment->medicalVisit?->checked_in_at?->format('H:i d/m/Y') ?? 'Chưa tiếp nhận' }}</dd></div></dl>
        @if ($appointment->medicalVisit?->checked_in_at)
            <a class="portal-btn portal-btn--secondary" href="{{ route('employee.reception.slip', $appointment->medicalVisit) }}">Xem phiếu khám</a>
        @endif
    </section>
    @if (! $appointment->medicalVisit && $appointment->appointmentStatus?->name === 'BOOKED' && ! $appointment->receptionUnavailableReason())
        <form class="employee-card" method="post" action="{{ route('employee.appointments.check-in', $appointment) }}">@csrf
            <p>Vui lòng xác nhận đúng bệnh nhân đã đến. Số khám được cấp ngay khi tiếp nhận. Bệnh nhân thanh toán phí khám trước khi được gọi.</p>
            <label><input type="checkbox" name="confirm_reception" value="1" required> Tôi xác nhận thông tin tiếp nhận chính xác.</label>
            <button class="portal-btn portal-btn--primary" type="submit" data-pending-label="Đang tiếp nhận…">Xác nhận tiếp nhận</button>
        </form>
    @elseif (! $appointment->medicalVisit && $appointment->appointmentStatus?->name === 'BOOKED')
        <div class="portal-alert portal-alert--info">{{ $appointment->receptionUnavailableReason() }}</div>
    @endif
</section>
@endsection
