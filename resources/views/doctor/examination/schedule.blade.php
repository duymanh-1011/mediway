@extends('doctor.layout')
@section('title', 'Lịch làm việc')

@section('content')
    <section class="employee-section reception-work">
        <div class="employee-section__heading">
            <div><h1>Lịch làm việc</h1><p>Tra cứu lịch phân công của {{ $doctor->full_name }}.</p></div>
        </div>
        <form method="get" action="{{ route('doctor.schedule') }}" class="employee-card workspace-filter workspace-filter--spaced" aria-label="Lọc lịch làm việc">
            <div class="workspace-filter__field workspace-filter__field--month">
                <label for="work-month">Tháng làm việc</label>
                <input id="work-month" type="month" class="form-control" name="month" value="{{ $month->format('Y-m') }}" required>
            </div>
            <x-workspace-filter-actions :reset-url="route('doctor.schedule')" />
        </form>
        <section class="employee-card workspace-schedule" aria-labelledby="schedule-title">
            <h2 id="schedule-title">Lịch tháng {{ $month->format('m/Y') }}</h2>
            <div class="employee-table-wrap" tabindex="0" role="region" aria-label="Lịch làm việc của bác sĩ">
                <table class="employee-table">
                    <thead><tr>
                        <th scope="col">Ngày</th><th scope="col">Ca</th><th scope="col">Giờ làm việc</th>
                        <th scope="col">Phòng làm việc</th><th scope="col">Chuyên khoa</th><th scope="col">Trạng thái</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($schedules as $schedule)
                            @php($isWorking = $schedule->status === 'ACTIVE')
                            <tr>
                                <td>{{ $schedule->work_date->format('d/m/Y') }}</td>
                                <td>{{ ['MORNING' => 'Sáng', 'AFTERNOON' => 'Chiều'][$schedule->shift_code] ?? 'Ca riêng' }}</td>
                                <td>{{ $isWorking && $schedule->start_time && $schedule->end_time ? substr($schedule->start_time, 0, 5).' – '.substr($schedule->end_time, 0, 5) : '—' }}</td>
                                <td>{{ $isWorking ? ($schedule->examinationRoom?->name ?? '—') : '—' }}</td>
                                <td>{{ $isWorking ? ($schedule->specialty?->name ?? '—') : '—' }}</td>
                                <td>{{ $isWorking ? 'Làm việc' : ($schedule->shift_code ? 'Nghỉ' : 'Ngừng hoạt động') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">Chưa có lịch làm việc trong tháng này.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
@endsection
