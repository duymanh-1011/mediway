@extends('employee.layout')
@section('title', 'Lịch hẹn tiếp đón')
@section('content')
<section class="employee-section reception-work">
    <div class="employee-section__heading"><div><p class="portal-eyebrow">TIẾP ĐÓN</p><h1>Lịch hẹn tiếp đón</h1><p>Tra cứu lịch hẹn và kiểm tra thông tin hành chính.</p></div></div>
    <form class="employee-card employee-filter" method="post" action="{{ route('employee.appointments.filter') }}">
        @csrf
        <div class="employee-filter__field"><label for="appointment-q">Mã lịch, mã hoặc tên bệnh nhân</label><input class="form-control" id="appointment-q" name="q" maxlength="120" value="{{ $filters['q'] ?? '' }}"></div>
        <div class="employee-filter__field"><label for="appointment-date">Ngày hẹn</label><input class="form-control" type="date" id="appointment-date" name="date" value="{{ $filters['date'] ?? '' }}"></div>
        <div class="employee-filter__field"><label for="appointment-status">Trạng thái</label><select class="form-control" id="appointment-status" name="status"><option value="">Tất cả</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected((string) ($filters['status'] ?? '') === (string) $status->id)>{{ \App\Models\AdminDisplay::label($status->name) }}</option>@endforeach</select></div>
        <div class="employee-filter__field"><label for="appointment-doctor">Bác sĩ</label><select class="form-control" id="appointment-doctor" name="doctor"><option value="">Tất cả</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}" @selected((string) ($filters['doctor'] ?? '') === (string) $doctor->id)>{{ $doctor->full_name }}</option>@endforeach</select></div>
        <div class="employee-filter__field"><label for="appointment-specialty">Chuyên khoa</label><select class="form-control" id="appointment-specialty" name="specialty"><option value="">Tất cả</option>@foreach($specialties as $specialty)<option value="{{ $specialty->id }}" @selected((string) ($filters['specialty'] ?? '') === (string) $specialty->id)>{{ $specialty->name }}</option>@endforeach</select></div>
        <div class="reception-actions"><button class="portal-btn portal-btn--primary" type="submit">Tìm kiếm</button><button class="portal-btn portal-btn--secondary" type="submit" name="reset" value="1" formnovalidate>Xóa bộ lọc</button></div>
    </form>
    <div class="employee-section reception-records">
        @forelse($appointments as $appointment)
            <article class="employee-card"><h2>{{ $appointment->appointment_code }} · {{ $appointment->patient?->full_name ?? 'Chưa xác định bệnh nhân' }}</h2>
                <p>{{ $appointment->patient?->patient_code ?? '—' }} · {{ $appointment->appointment_date?->format('d/m/Y') ?? '—' }} · {{ $appointment->appointmentTimeSlot?->start_time ?? '—' }}</p>
                <p>Bác sĩ: {{ $appointment->doctor?->full_name ?? '—' }} · Chuyên khoa: {{ $appointment->specialty?->name ?? '—' }} · Phòng: {{ $appointment->examinationRoom?->name ?? '—' }}</p>
                <p>Trạng thái: {{ \App\Models\AdminDisplay::label($appointment->displayStatus()) }} @if($appointment->medicalVisit) · Đã có lượt tiếp nhận @endif</p>
                <a class="employee-card__link" href="{{ route('employee.appointments.show', $appointment) }}">Xem chi tiết →</a>
            </article>
        @empty <div class="employee-card">Không tìm thấy lịch hẹn phù hợp.</div> @endforelse
        {{ $appointments->links() }}
    </div>
</section>
@endsection
