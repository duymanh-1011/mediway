@extends('doctor.layout')
@section('title', 'Hàng đợi khám')
@include('doctor.examination.assets')

@section('content')
    @php
        $refreshUrl = route('doctor.examination.rooms.queue', [
            'room' => $room, 'date' => $date,
            ...array_filter(\Illuminate\Support\Arr::only($filters, ['status', 'shift'])),
            ...$search->navigation(),
        ]);
        $shiftLabels = ['MORNING' => 'Sáng', 'AFTERNOON' => 'Chiều'];
    @endphp

    <section class="employee-section reception-work doctor-examination d01-queue">
        <a class="employee-card__link doctor-examination__back" href="{{ route('doctor.examination.rooms.index') }}">
            ← Danh sách phòng
        </a>
        <div class="employee-section__heading">
            <div>
                <h1>{{ $room->name }}</h1>
                <p>Hàng đợi khám của phòng</p>
            </div>
            <a class="portal-btn portal-btn--secondary" href="{{ $refreshUrl }}">Làm mới</a>
        </div>

        <form action="{{ route('doctor.examination.rooms.queue.filter', $room) }}" method="post"
            class="employee-card workspace-filter workspace-filter--spaced" aria-label="Lọc hàng đợi khám" autocomplete="off">
            @csrf
            <div class="workspace-filter__field">
                <label for="queue-date">Ngày tiếp nhận</label>
                <input id="queue-date" class="form-control" type="date" name="date" value="{{ old('date', $date) }}">
            </div>
            <div class="workspace-filter__field workspace-filter__field--search">
                <label for="queue-search">Tên, SĐT hoặc CCCD</label>
                <input id="queue-search" class="form-control" type="search" name="q" maxlength="100"
                    placeholder="Nhập thông tin bệnh nhân" value="{{ old('q', $filters['q'] ?? '') }}">
            </div>
            <div class="workspace-filter__field">
                <label for="queue-status">Trạng thái</label>
                <select id="queue-status" class="form-control" name="status">
                    <option value="">Tất cả trạng thái</option>
                    @foreach ($examinationStatusLabels as $code => $label)
                        <option value="{{ $code }}" @selected(old('status', $filters['status'] ?? '') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="workspace-filter__field">
                <label for="queue-shift">Ca lịch hẹn</label>
                <select id="queue-shift" class="form-control" name="shift">
                    <option value="">Tất cả</option>
                    @foreach ($shifts as $shift)
                        <option value="{{ $shift }}" @selected(old('shift', $filters['shift'] ?? '') === $shift)>{{ $shiftLabels[$shift] ?? $shift }}</option>
                    @endforeach
                </select>
            </div>
            <x-workspace-filter-actions :reset-url="route('doctor.examination.rooms.queue', $room)" />
        </form>

        <section class="employee-card doctor-examination__results" aria-labelledby="queue-list-title">
            <div class="employee-section__heading">
                <h2 id="queue-list-title">Hàng đợi khám</h2>
            </div>
            <div class="employee-table-wrap d01-table-wrap" tabindex="0" role="region" aria-label="Hàng đợi phòng khám">
                <table class="employee-table d01-table">
                    <thead>
                        <tr>
                            <th scope="col">Số khám</th>
                            <th scope="col">Bệnh nhân</th>
                            <th scope="col">Tiếp nhận</th>
                            <th scope="col">Bác sĩ</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($queues as $queue)
                            @php
                                $visit = $queue->medicalVisit;
                                $stage = $queue->examination_stage;
                            @endphp
                            <tr>
                                <td><strong>{{ $queue->queue_number }}</strong></td>
                                <td><strong class="d01-patient-name">{{ $visit->patient?->full_name ?? '—' }}</strong></td>
                                <td>{{ $visit->checked_in_at?->format('H:i d/m/Y') ?? '—' }}</td>
                                <td>{{ $visit->doctor?->full_name ?? '—' }}</td>
                                <td>
                                    <span class="d01-status d01-status--{{ $stage ?? 'unknown' }}">{{ $examinationStatusLabels[$stage] ?? 'Chưa xác định' }}</span>
                                    @if ($queue->status?->name === 'SKIPPED')
                                        <small class="d01-muted">Đã bỏ qua lượt gọi</small>
                                    @endif
                                    @if ($visit->displayStatus() === 'WAITING_FOR_PAYMENT')
                                        <small class="d01-muted">Chưa hoàn tất thanh toán</small>
                                    @endif
                                </td>
                                <td>
                                    <x-portal.button variant="secondary" :href="route('doctor.examination.visits.show', $visit)">
                                        Xem lượt khám
                                    </x-portal.button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="doctor-examination__empty">Không có lượt khám phù hợp với bộ lọc.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-portal.pagination :paginator="$queues" />
        </section>
    </section>
@endsection
