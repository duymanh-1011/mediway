@extends('employee.layout')
@section('title', 'Hàng đợi hành chính')
@section('content')
<section class="employee-section reception-work">
    <div class="employee-section__heading"><div><p class="portal-eyebrow">TIẾP ĐÓN</p><h1>Hàng đợi hành chính</h1><p>Theo dõi trạng thái hàng đợi. Bác sĩ thực hiện thao tác gọi và khám.</p></div></div>
    <form class="employee-card employee-filter" method="post" action="{{ route('employee.queue.filter') }}">@csrf
        <div class="employee-filter__field"><label for="queue-q">Mã lượt, mã hoặc tên bệnh nhân</label><input class="form-control" id="queue-q" name="q" maxlength="120" value="{{ $filters['q'] ?? '' }}"></div>
        <div class="employee-filter__field"><label for="queue-date">Ngày vào hàng đợi</label><input class="form-control" type="date" id="queue-date" name="date" value="{{ $filters['date'] ?? '' }}"></div>
        <div class="employee-filter__field"><label for="queue-status">Trạng thái</label><select class="form-control" id="queue-status" name="status"><option value="">Tất cả</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected((string) ($filters['status'] ?? '') === (string) $status->id)>{{ \App\Models\AdminDisplay::label($status->name) }}</option>@endforeach</select></div>
        <div class="employee-filter__field"><label for="queue-doctor">Bác sĩ</label><select class="form-control" id="queue-doctor" name="doctor"><option value="">Tất cả</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}" @selected((string) ($filters['doctor'] ?? '') === (string) $doctor->id)>{{ $doctor->full_name }}</option>@endforeach</select></div>
        <div class="employee-filter__field"><label for="queue-room">Phòng khám</label><select class="form-control" id="queue-room" name="room"><option value="">Tất cả</option>@foreach($rooms as $room)<option value="{{ $room->id }}" @selected((string) ($filters['room'] ?? '') === (string) $room->id)>{{ $room->name }}</option>@endforeach</select></div>
        <div class="reception-actions"><button class="portal-btn portal-btn--primary" type="submit">Tìm kiếm</button><button class="portal-btn portal-btn--secondary" type="submit" name="reset" value="1" formnovalidate>Xóa bộ lọc</button><a class="portal-btn portal-btn--secondary" href="{{ route('employee.queue.index') }}">Làm mới</a></div>
    </form>
    <div class="employee-section reception-records">@forelse($queues as $queue)
        <article class="employee-card"><h2>Số {{ $queue->queue_number }} · {{ $queue->medicalVisit?->patient?->full_name ?? 'Chưa xác định bệnh nhân' }}</h2>
            <p>{{ $queue->medicalVisit?->patient?->patient_code ?? '—' }} · Lượt khám {{ $queue->medicalVisit?->visit_code ?? '—' }}</p>
            <p>Phòng: {{ $queue->medicalVisit?->examinationRoom?->name ?? '—' }} · Bác sĩ: {{ $queue->medicalVisit?->doctor?->full_name ?? '—' }}</p>
            <p>{{ \App\Models\AdminDisplay::label($queue->queue_type) }} · {{ \App\Models\AdminDisplay::label($queue->status?->name) }} · {{ $queue->queued_at?->format('H:i d/m/Y') ?? '—' }}</p>
        </article>
    @empty <div class="employee-card">Không tìm thấy hàng đợi phù hợp.</div> @endforelse {{ $queues->links() }}</div>
</section>
@endsection
