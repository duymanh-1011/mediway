@extends('doctor.layout')

@section('title', 'Phòng khám')

@include('doctor.examination.assets')

@section('content')

    <section class="employee-section reception-work doctor-examination doctor-rooms-page">

        <div class="employee-section__heading">
            <div>
                <h1>Phòng khám</h1>

                <p>
                    Lịch phân công ngày
                    {{ \Carbon\CarbonImmutable::parse($date)->format('d/m/Y') }}.
                    Số lượt chờ lấy từ dữ liệu tiếp nhận thực tế.
                </p>
                <p>Lịch làm việc của bạn · <strong>{{ $workStatus }}</strong></p>
            </div>
        </div>

        <form id="doctor-room-filter" action="{{ route('doctor.examination.rooms.index') }}" method="get"
            class="employee-card employee-filter doctor-rooms__filter" aria-label="Lọc phòng khám" autocomplete="off">
            <div class="doctor-rooms__filter-field">
                <label for="room-search">
                    Tên phòng
                </label>

                <input id="room-search" class="form-control" type="search" name="q" maxlength="100"
                    placeholder="Nhập tên phòng…" value="{{ $filters['q'] ?? '' }}" autocomplete="off">
            </div>

            <div class="doctor-rooms__filter-field">
                <label for="room-specialty">
                    Chuyên khoa
                </label>

                <select id="room-specialty" class="form-control" name="specialty">
                    <option value="">
                        Tất cả chuyên khoa
                    </option>

                    @foreach ($specialties as $specialty)
                        <option value="{{ $specialty->id }}" @selected(
                            (int) ($filters['specialty'] ?? 0)
                            === (int) $specialty->id
                        )>
                            {{ $specialty->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="doctor-rooms__filter-actions">
                <x-portal.button type="submit">
                    Tìm kiếm
                </x-portal.button>

                <a class="portal-btn portal-btn--secondary" href="{{ route('doctor.examination.rooms.index') }}">
                    Xóa bộ lọc
                </a>
            </div>
        </form>

        <section class="employee-card doctor-examination__results" aria-labelledby="room-list-title">
            <div class="employee-section__heading">
                <h2 id="room-list-title">
                    Danh sách phòng khám
                </h2>
            </div>

            <div class="employee-table-wrap doctor-rooms__table-wrap" tabindex="0" role="region"
                aria-label="Danh sách phòng khám">
                <table class="employee-table doctor-rooms__table">

                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>

                    <thead>
                        <tr>
                            <th scope="col">Phòng</th>
                            <th scope="col">Chuyên khoa</th>
                            <th scope="col">Trạng thái phòng</th>
                            <th scope="col">Bác sĩ phân công</th>
                            <th scope="col">Đang chờ</th>
                            <th scope="col">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($rooms as $room)

                            @php
                                $roomSchedules = collect($schedules ?? [])->get($room->id, []);
                                $assignedDoctors = collect($roomSchedules)->groupBy(fn ($schedule) =>
                                    $schedule->doctor_id !== null
                                        ? 'doctor-'.$schedule->doctor_id
                                        : 'schedule-'.$schedule->id
                                );
                                $waitingCount = data_get($room, 'waiting_count');
                                $statusClass = match (strtoupper((string) $room->status)) {
                                    'ACTIVE' => 'active',
                                    'INACTIVE', 'DISABLED', 'CLOSED' => 'inactive',
                                    'MAINTENANCE' => 'maintenance',
                                    default => 'other',
                                };
                            @endphp

                            <tr>

                                <td>
                                    <strong class="doctor-rooms__room-name">
                                        {{ $room->name }}
                                    </strong>
                                    <small class="doctor-examination__meta">{{ $room->room_code }}</small>
                                </td>

                                <td>
                                    <span class="doctor-rooms__specialty">
                                        {{ $room->specialty?->name ?? 'Chưa cập nhật' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="doctor-rooms__status doctor-rooms__status--{{ $statusClass }}">
                                        {{ \App\Models\AdminDisplay::label($room->status) }}
                                    </span>
                                </td>

                                <td>
                                    <div class="doctor-rooms__assignment-list">

                                        @forelse ($assignedDoctors as $doctorShifts)

                                            @php
                                                $assignedDoctor = $doctorShifts->first()?->doctor;
                                                $visibleShifts = $doctorShifts
                                                    ->filter(fn ($shift) => filled($shift->start_time) && filled($shift->end_time))
                                                    ->unique(fn ($shift) => (string) $shift->start_time.'|'.(string) $shift->end_time);
                                                $onDuty = $visibleShifts->contains(fn($shift) =>
                                                    (string) $shift->start_time <= $now->toTimeString()
                                                    && (string) $shift->end_time > $now->toTimeString());
                                            @endphp

                                            <div class="doctor-rooms__assignment">

                                                <div class="doctor-rooms__assignment-name">

                                                    <span class="doctor-rooms__assignment-icon" aria-hidden="true">
                                                        <x-employee-icon name="user" />
                                                    </span>

                                                    <strong>
                                                        {{ $assignedDoctor?->full_name ?? 'Chưa cập nhật tên' }}
                                                    </strong>

                                                </div>

                                                <div class="doctor-rooms__assignment-shifts">

                                                    @forelse ($visibleShifts as $shift)

                                                        <span class="doctor-rooms__shift">
                                                            {{ substr((string) $shift->start_time, 0, 5) }}
                                                            –
                                                            {{ substr((string) $shift->end_time, 0, 5) }}
                                                        </span>

                                                    @empty

                                                        <span class="doctor-examination__meta">
                                                            Chưa cập nhật giờ làm
                                                        </span>

                                                    @endforelse

                                                </div>

                                            </div>

                                        @empty

                                            <span class="doctor-rooms__unassigned">
                                                Chưa có lịch phân công
                                            </span>

                                        @endforelse

                                    </div>
                                </td>

                                <td>
                                    @if ($waitingCount !== null)

                                        <span class="doctor-rooms__waiting">
                                            {{ $waitingCount }}
                                        </span>

                                    @else

                                        <span class="doctor-rooms__waiting doctor-rooms__waiting--empty">
                                            —
                                        </span>

                                    @endif
                                </td>

                                <td>
                                    <a class="portal-btn portal-btn--secondary doctor-rooms__action"
                                        href="{{ route('doctor.examination.rooms.queue', $room) }}">
                                        Xem hàng đợi
                                    </a>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="6" class="doctor-rooms__empty">
                                    Không tìm thấy phòng khám phù hợp.
                                </td>
                            </tr>

                        @endforelse
                    </tbody>

                </table>
            </div>

            @if (method_exists($rooms, 'links'))
                <x-portal.pagination :paginator="$rooms" />
            @endif

        </section>

    </section>
@endsection