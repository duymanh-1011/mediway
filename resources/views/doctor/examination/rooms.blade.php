@extends('doctor.layout')

@section('title', 'Phòng khám')

@section('content')

    <section class="employee-section reception-work doctor-examination">

        <div class="employee-section__heading">
            <div>
                <h1>Phòng khám</h1>
                <p>
                    Lịch phân công ngày
                    {{ \Carbon\CarbonImmutable::parse($date)->format('d/m/Y') }}.
                    Số lượt chờ lấy từ dữ liệu tiếp nhận thực tế.
                </p>
            </div>
        </div>

        {{-- TÌM KIẾM VÀ LỌC PHÒNG --}}
        <form action="{{ route('doctor.examination.rooms.index') }}" method="get" class="employee-card employee-filter"
            aria-label="Lọc phòng khám">
            <div class="employee-filter__field">
                <label for="room-search">Mã hoặc tên phòng</label>

                <input id="room-search" class="form-control" type="search" name="q" maxlength="100"
                    value="{{ $filters['q'] ?? '' }}">
            </div>

            <div class="employee-filter__field">
                <label for="room-specialty">Chuyên khoa</label>

                <select id="room-specialty" class="form-control" name="specialty">
                    <option value="">Tất cả chuyên khoa</option>

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

            <div class="reception-actions">
                <x-portal.button type="submit">
                    Tìm phòng
                </x-portal.button>

                <a class="portal-btn portal-btn--secondary" href="{{ route('doctor.examination.rooms.index') }}">
                    Xóa bộ lọc
                </a>
            </div>
        </form>

        {{-- DANH SÁCH PHÒNG --}}
        <section class="employee-card doctor-examination__results" aria-labelledby="room-list-title">
            <div class="employee-section__heading">
                <h2 id="room-list-title">Danh sách phòng khám</h2>
            </div>

            <div class="employee-table-wrap" tabindex="0" role="region" aria-label="Danh sách phòng khám">
                <table class="employee-table">

                    <thead>
                        <tr>
                            <th scope="col">Phòng</th>
                            <th scope="col">Chuyên khoa</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Bác sĩ phân công</th>
                            <th scope="col">Đang chờ</th>
                            <th scope="col">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($rooms as $room)

                            @php
                                $roomSchedules = collect($schedules ?? [])
                                    ->get($room->id, []);

                                $waitingCount = isset($waitingCounts)
                                    ? data_get($waitingCounts, $room->id)
                                    : null;
                            @endphp

                            <tr>
                                <td>
                                    <strong>{{ $room->name }}</strong>

                                    <small class="doctor-examination__meta">
                                        {{ $room->room_code }}
                                    </small>
                                </td>

                                <td>
                                    {{ $room->specialty?->name ?? '—' }}
                                </td>

                                <td>
                                    <x-portal.badge :type="\App\Models\AdminDisplay::badge($room->status)">
                                        {{ \App\Models\AdminDisplay::label($room->status) }}
                                    </x-portal.badge>
                                </td>

                                <td>
                                    @forelse ($roomSchedules as $schedule)
                                        <div>
                                            {{ $schedule->doctor?->full_name ?? '—' }}

                                            @if ($schedule->start_time && $schedule->end_time)
                                                <small class="doctor-examination__meta">
                                                    {{ substr($schedule->start_time, 0, 5) }}
                                                    –
                                                    {{ substr($schedule->end_time, 0, 5) }}
                                                </small>
                                            @endif
                                        </div>
                                    @empty
                                        <span class="doctor-examination__meta">
                                            Chưa có lịch phân công
                                        </span>
                                    @endforelse
                                </td>

                                <td>
                                    {{ $waitingCount ?? '—' }}
                                </td>

                                <td>
                                    <a class="portal-btn portal-btn--secondary"
                                        href="{{ route('doctor.examination.rooms.queue', $room) }}">
                                        Xem hàng đợi
                                    </a>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="6" class="doctor-examination__empty">
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