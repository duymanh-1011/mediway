@extends('employee.layout')
@section('title', 'Lịch hẹn tiếp đón')
@section('content')
<section class="employee-section reception-work">
    <div class="employee-section__heading"><div><p class="portal-eyebrow">TIẾP ĐÓN</p><h1>Lịch hẹn tiếp đón</h1><p>Tra cứu lịch hẹn và kiểm tra thông tin hành chính.</p></div></div>
    <form class="employee-card workspace-filter workspace-filter--three-columns" method="post" action="{{ route('employee.appointments.filter') }}" aria-label="Lọc lịch hẹn tiếp đón">
        @csrf
        <div class="workspace-filter__field workspace-filter__field--search"><label for="appointment-q">Tìm lịch hẹn</label><input type="search" id="appointment-q" name="q" maxlength="120" placeholder="Mã lịch, mã hoặc tên bệnh nhân" value="{{ $filters['q'] ?? '' }}"></div>
        <div class="workspace-filter__field"><label for="appointment-date">Ngày hẹn</label><input type="date" id="appointment-date" name="date" value="{{ $filters['date'] ?? '' }}"></div>
        <div class="workspace-filter__field"><label for="appointment-status">Trạng thái</label><select id="appointment-status" name="status"><option value="">Tất cả</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected((string) ($filters['status'] ?? '') === (string) $status->id)>{{ \App\Models\AdminDisplay::label($status->name) }}</option>@endforeach</select></div>
        <div class="workspace-filter__field"><label for="appointment-doctor">Bác sĩ</label><select id="appointment-doctor" name="doctor"><option value="">Tất cả</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}" @selected((string) ($filters['doctor'] ?? '') === (string) $doctor->id)>{{ $doctor->full_name }}</option>@endforeach</select></div>
        <div class="workspace-filter__field"><label for="appointment-specialty">Chuyên khoa</label><select id="appointment-specialty" name="specialty"><option value="">Tất cả</option>@foreach($specialties as $specialty)<option value="{{ $specialty->id }}" @selected((string) ($filters['specialty'] ?? '') === (string) $specialty->id)>{{ $specialty->name }}</option>@endforeach</select></div>
        <x-workspace-filter-actions />
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
