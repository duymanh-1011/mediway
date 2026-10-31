@extends('portal.layouts.app')

@push('portal_page_styles')
    @vite('resources/css/doctors-index.css')
@endpush

@section('title', 'Tra cứu bác sĩ và lịch khám - Mediway')

@section('content')
    @php
        $hasFilters = filled($specialtyId)
            || filled($doctorId)
            || filled($date);

        $selectedSpecialty = $specialties->firstWhere(
            'id',
            $specialtyId
        );

        $selectedDoctor = $doctors->firstWhere(
            'id',
            $doctorId
        );

        $now = now();

        // Một bác sĩ chỉ xuất hiện một lần.
        // Mỗi ngày chứa các slot của ca sáng và chiều.
        $doctorCards = $schedules
            ->groupBy('doctor_id')
            ->map(function ($items) use ($now) {
                $days = $items
                    ->sortBy(
                        fn($schedule) =>
                            $schedule->work_date->format('Y-m-d')
                            . ' '
                            . $schedule->start_time
                    )
                    ->groupBy(
                        fn($schedule) =>
                            $schedule->work_date->format('Y-m-d')
                    )
                    ->map(function ($daySchedules, $dayKey) use ($now) {
                        $entries = $daySchedules
                            ->flatMap(
                                function ($schedule) use ($dayKey, $now) {
                                    return $schedule
                                        ->appointmentTimeSlots
                                        ->map(
                                            function ($slot) use ($schedule, $dayKey, $now) {
                                                $startsAt =
                                                    \Illuminate\Support\Carbon::parse(
                                                        $dayKey
                                                        . ' '
                                                        . substr(
                                                            (string) $slot->start_time,
                                                            0,
                                                            8
                                                        )
                                                    );

                                                $remaining = max(
                                                    0,
                                                    (int) $slot->max_patients
                                                    - (int) $slot->booked_count
                                                );

                                                $state = $startsAt
                                                    ->lessThanOrEqualTo($now)
                                                    ? 'past'
                                                    : (
                                                        $slot->status !== 'ACTIVE'
                                                        || $remaining === 0
                                                        ? 'full'
                                                        : 'open'
                                                    );

                                                return [
                                                    'slot' => $slot,
                                                    'schedule' => $schedule,
                                                    'starts_at' => $startsAt,
                                                    'remaining' => $remaining,
                                                    'state' => $state,
                                                    'period' =>
                                                        (int) $startsAt->format('H') < 12
                                                        ? 'morning'
                                                        : 'afternoon',
                                                ];
                                            }
                                        );
                                }
                            )
                            ->sortBy(
                                fn($entry) =>
                                    $entry['starts_at']->timestamp
                            )
                            ->values();

                        return [
                            'entries' => $entries,
                            'available' =>
                                $entries->where('state', 'open')->count(),
                            'all_past' =>
                                $entries->every(
                                    fn($entry) =>
                                        $entry['state'] === 'past'
                                ),
                        ];
                    });

                return [
                    'doctor' => $items->first()->doctor,
                    'specialties' => $items
                        ->pluck('specialty.name')
                        ->filter()
                        ->unique()
                        ->implode(' · '),
                    'days' => $days,
                    'available' => $days->sum('available'),
                    'open_days' => $days
                        ->filter(
                            fn($day) =>
                                $day['available'] > 0
                        )
                        ->count(),
                ];
            })
            ->values();

        $weekdays = [
            'Chủ nhật',
            'Thứ 2',
            'Thứ 3',
            'Thứ 4',
            'Thứ 5',
            'Thứ 6',
            'Thứ 7',
        ];
    @endphp



    <div class="mw-search" id="mw-doctor-search">

        @error('appointment_time_slot_id')
            <x-portal.alert type="error" class="portal-shell portal-flash">{{ $message }}</x-portal.alert>
        @enderror

        {{-- TIÊU ĐỀ --}}
        <section class="portal-page-head">
            <div class="portal-shell">
                <p class="portal-eyebrow">ĐẶT LỊCH KHÁM</p>

                <h1>Tra cứu bác sĩ và lịch khám</h1>

                <p class="portal-lead">
                    Chọn bác sĩ, ngày khám và khung giờ phù hợp với bạn.
                </p>
            </div>
        </section>

        {{-- BỘ LỌC --}}
        <section class="mw-filters">
            <div class="portal-shell">
                <div class="mw-filter-card">
                    <h2>Bộ lọc lịch khám</h2>

                    <p>
                        Chỉ khi nhấn <strong>Tra cứu</strong>,
                        danh sách kết quả bên dưới mới thay đổi.
                    </p>

                    <form
                        id="mw-filter-form"
                        method="GET"
                        action="{{ route('patient.doctors.index') }}"
                        autocomplete="off"
                    >
                        <div class="mw-filter-grid">

                            <div class="mw-filter-field">
                                <label for="specialty_id">
                                    Chuyên khoa
                                </label>

                                <select
                                    id="specialty_id"
                                    name="specialty_id"
                                >
                                    <option value="">
                                        Tất cả chuyên khoa
                                    </option>

                                    @foreach ($specialties as $specialty)
                                        <option
                                            value="{{ $specialty->id }}"
                                            @selected(
                                                (string) $specialtyId
                                                === (string) $specialty->id
                                            )
                                        >
                                            {{ $specialty->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('specialty_id')
                                    <p class="mw-field-error">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="mw-filter-field">
                                <label for="doctor_id">
                                    Bác sĩ
                                </label>

                                <select
                                    id="doctor_id"
                                    name="doctor_id"
                                >
                                    <option value="">
                                        Tất cả bác sĩ
                                    </option>

                                    @foreach ($doctors as $doctorOption)
                                        <option
                                            value="{{ $doctorOption->id }}"
                                            data-specialty="{{ $doctorOption->specialty_id }}"
                                            data-general="{{ $doctorOption->specialty?->is_general ? '1' : '0' }}"
                                            @selected(
                                                (string) $doctorId
                                                === (string) $doctorOption->id
                                            )
                                        >
                                            {{ $doctorOption->full_name }}
                                            —
                                            {{ $doctorOption->specialty?->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('doctor_id')
                                    <p class="mw-field-error">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="mw-filter-field">
                                <label for="date">
                                    Ngày khám
                                </label>

                                <input
                                    id="date"
                                    name="date"
                                    type="date"
                                    value="{{ $date }}"
                                    min="{{ today()->toDateString() }}"
                                    max="{{ today()->addDays(\App\Models\Appointment::BOOKING_ADVANCE_DAYS)->toDateString() }}"
                                >

                                @error('date')
                                    <p class="mw-field-error">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                        </div>

                        <div class="mw-filter-actions">
                            <button
                                class="mw-btn mw-btn--primary"
                                type="submit"
                            >
                                Tra cứu
                            </button>

                            <a
                                class="mw-btn"
                                href="{{ route('patient.doctors.index') }}"
                            >
                                Xóa bộ lọc
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        {{-- KẾT QUẢ --}}
        <section class="mw-results">
            <div class="portal-shell">

                <div class="mw-context">
                    @if ($hasFilters)
                        <strong>Kết quả đang áp dụng:</strong>

                        @if ($specialtyId)
                            Chuyên khoa:
                            {{ $selectedSpecialty?->name ?? 'Đã chọn' }}.
                        @endif

                        @if ($doctorId)
                            Bác sĩ:
                            {{ $selectedDoctor?->full_name ?? 'Đã chọn' }}.
                        @endif

                        @if ($date)
                                        Ngày:
                                        {{
                            \Illuminate\Support\Carbon::parse($date)
                                ->format('d/m/Y')
                                        }}.
                        @endif
                    @else
                        <strong>Tất cả lịch từ hôm nay:</strong>
                        Mỗi bác sĩ hiển thị một lần; chọn bác sĩ để
                        xem các ngày và ca làm việc.
                    @endif
                </div>

                @if ($doctorCards->isEmpty())
                    <div class="mw-empty">
                        <h3>Không tìm thấy lịch phù hợp</h3>

                        <p>
                            Hiện không có lịch làm việc trong khoảng ngày
                            và bộ lọc bạn đã chọn.
                        </p>
                    </div>
                @else

                    <div class="mw-list">

                        @foreach ($doctorCards as $card)
                                        @php
                                            $doctor = $card['doctor'];
                                            $days = $card['days'];
                                        @endphp

                                        <article class="mw-doctor" data-doctor-card>

                                            {{-- THÔNG TIN BÁC SĨ --}}
                                            <div class="mw-doctor-head">

                                                <div class="mw-avatar" aria-hidden="true">
                                                    <svg
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="1.7"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    >
                                                        <circle cx="12" cy="7" r="4"/>
                                                        <path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
                                                        <path d="M9 15v3m6-3v3m-6 0h6"/>
                                                    </svg>
                                                </div>

                                                <div class="mw-doctor-info">
                                                    <p class="mw-doctor-specialty">
                                                        {{
                            $card['specialties']
                            ?: 'Chuyên khoa chưa cập nhật'
                                                        }}
                                                    </p>

                                                    <h3 class="mw-doctor-name">
                                                        <a href="{{ route('patient.doctors.show', $doctor) }}">{{ $doctor->full_name }}</a>
                                                    </h3>

                                                    <p class="mw-doctor-sub">
                                                        {{ $days->count() }} ngày làm việc
                                                        ·
                                                        {{ $card['available'] }}
                                                        khung giờ còn đặt được
                                                    </p>
                                                </div>

                                                <span
                                                    class="mw-availability {{ $card['available'] ? '' : 'is-none' }}"
                                                    data-doctor-availability
                                                >
                                                    {{
                            $card['available']
                            ? 'Đang nhận lịch'
                            : 'Tạm hết suất'
                                                    }}
                                                </span>

                                                <button
                                                    type="button"
                                                    class="mw-expand"
                                                    data-doctor-toggle
                                                    aria-expanded="false"
                                                    aria-controls="mw-panel-{{ $doctor->id }}"
                                                >
                                                    <span data-toggle-label>Chọn</span>

                                                    <svg
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        stroke-linecap="round"
                                                    >
                                                        <path d="m6 9 6 6 6-6"/>
                                                    </svg>
                                                </button>

                                            </div>

                                            {{-- LỊCH BÁC SĨ --}}
                                            <div
                                                class="mw-doctor-panel"
                                                id="mw-panel-{{ $doctor->id }}"
                                                data-doctor-panel
                                            >
                                                <h4 class="mw-section-heading">
                                                    Chọn ngày khám
                                                </h4>

                                                {{-- DANH SÁCH NGÀY --}}
                                                <div
                                                    class="mw-days"
                                                    role="group"
                                                    aria-label="Các ngày khám của bác sĩ"
                                                >
                                                    @foreach ($days as $dayKey => $day)
                                                                                @php
                                                                                    $dayDate =
                                                                                        \Illuminate\Support\Carbon::parse(
                                                                                            $dayKey
                                                                                        );

                                                                                    $weekday =
                                                                                        $weekdays[$dayDate->dayOfWeek];
                                                                                @endphp

                                                                                <button
                                                                                    type="button"
                                                                                    class="mw-day {{ $loop->first ? 'is-active' : '' }} {{ $day['available'] ? '' : 'is-unavailable' }}"
                                                                                    data-day-tab
                                                                                    data-day="{{ $dayKey }}"
                                                                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                                                                >
                                                                                    <strong>
                                                                                        {{ $weekday }}
                                                                                    </strong>

                                                                                    <span>
                                                                                        {{ $dayDate->format('d/m') }}
                                                                                    </span>

                                                                                    <small data-day-status>
                                                                                        {{
                                                        $day['available']
                                                        ? 'Còn ' . $day['available'] . ' suất'
                                                        : (
                                                            $day['all_past']
                                                            ? 'Đã qua giờ'
                                                            : 'Hết chỗ'
                                                        )
                                                                                        }}
                                                                                    </small>
                                                                                </button>
                                                    @endforeach
                                                </div>

                                                {{-- CHI TIẾT TỪNG NGÀY --}}
                                                @foreach ($days as $dayKey => $day)
                                                    @php
                                                        $dayDate =
                                                            \Illuminate\Support\Carbon::parse(
                                                                $dayKey
                                                            );
                                                    @endphp

                                                    <div
                                                        class="mw-day-panel {{ $loop->first ? 'is-active' : '' }}"
                                                        data-day-panel="{{ $dayKey }}"
                                                    >
                                                        <h4 class="mw-day-title">

                                                            <svg
                                                                viewBox="0 0 24 24"
                                                                fill="none"
                                                                stroke="currentColor"
                                                                stroke-width="1.8"
                                                            >
                                                                <rect
                                                                    x="3" y="5"
                                                                    width="18" height="16"
                                                                    rx="2"
                                                                />
                                                                <path d="M7 3v4m10-4v4M3 10h18"/>
                                                            </svg>

                                                            {{ $weekdays[$dayDate->dayOfWeek] }},
                                                            {{ $dayDate->format('d/m/Y') }}
                                                        </h4>

                                                        {{-- TÁCH SÁNG VÀ CHIỀU --}}
                                                        @foreach ([
                                                                'morning' => 'Sáng',
                                                                'afternoon' => 'Chiều'
                                                            ] as $periodKey => $periodLabel)

                                                                @php
                                                                    $periodEntries =
                                                                        $day['entries']
                                                                            ->where(
                                                                                'period',
                                                                                $periodKey
                                                                            )
                                                                            ->values();

                                                                    $openCount =
                                                                        $periodEntries
                                                                            ->where(
                                                                                'state',
                                                                                'open'
                                                                            )
                                                                            ->count();

                                                                    $allPast =
                                                                        $periodEntries->isNotEmpty()
                                                                        && $periodEntries->every(
                                                                            fn($entry) =>
                                                                                $entry['state'] === 'past'
                                                                        );
                                                                @endphp

                                                                @if ($periodEntries->isNotEmpty())

                                                                                            <section
                                                                                                class="mw-period mw-period--{{ $periodKey }} {{ $openCount ? '' : 'is-unavailable' }}"
                                                                                                data-period
                                                                                            >

                                                                                                <div class="mw-period-head">

                                                                                                    <h5 class="mw-period-title">

                                                                                                        <span
                                                                                                            class="mw-period-icon"
                                                                                                            aria-hidden="true"
                                                                                                        >

                                                                                                            @if ($periodKey === 'morning')

                                                                                                                {{-- ICON MẶT TRỜI BUỔI SÁNG --}}
                                                                                                                <svg
                                                                                                                    viewBox="0 0 24 24"
                                                                                                                    fill="none"
                                                                                                                    stroke="currentColor"
                                                                                                                    stroke-width="1.8"
                                                                                                                    stroke-linecap="round"
                                                                                                                >
                                                                                                                    <circle cx="12" cy="12" r="4"/>
                                                                                                                    <path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/>
                                                                                                                </svg>

                                                                                                            @else

                                                                                                                {{-- ICON BUỔI CHIỀU --}}
                                                                                                                <svg
                                                                                                                    viewBox="0 0 24 24"
                                                                                                                    fill="none"
                                                                                                                    stroke="currentColor"
                                                                                                                    stroke-width="1.8"
                                                                                                                    stroke-linecap="round"
                                                                                                                >
                                                                                                                    <path d="M3 19h18M5 15h14M7 11a5 5 0 0 1 10 0M12 3v2M4 7l2 2m14-2-2 2"/>
                                                                                                                </svg>

                                                                                                            @endif
                                                                                                        </span>

                                                                                                        Lịch khám buổi
                                                                                                        {{ $periodLabel }}
                                                                                                    </h5>

                                                                                                    <span
                                                                                                        class="mw-period-badge {{ $openCount ? '' : 'is-unavailable' }}"
                                                                                                        data-period-badge
                                                                                                    >
                                                                                                        {{
                                                                    $openCount
                                                                    ? 'Còn ' . $openCount . ' khung giờ'
                                                                    : (
                                                                        $allPast
                                                                        ? 'Đã qua giờ'
                                                                        : 'Hết chỗ'
                                                                    )
                                                                                                        }}
                                                                                                    </span>

                                                                                                </div>

                                                                                                <div class="mw-period-body">

                                                                                                    {{-- NHÓM THEO CA LÀM VIỆC --}}
                                                                                                    @foreach (
                                                                                                                                        $periodEntries->groupBy(
                                                                                                                                            fn($entry) =>
                                                                                                                                                $entry['schedule']->id
                                                                                                                                        )
                                                                                                                                        as $shiftEntries
                                                                                                                                    )
                                                                                                                                        @php
                                                                                                                                            $shift =
                                                                                                                                                $shiftEntries
                                                                                                                                                    ->first()['schedule'];
                                                                                                                                        @endphp

                                                                                                                                        <div class="mw-shift">

                                                                                                                                            <div class="mw-shift-meta">

                                                                                                                                                <span>
                                                                                                                                                    <strong>Phòng khám:</strong>
                                                                                                                                                    {{
                                                                                                        $shift->examinationRoom?->name
                                                                                                        ?? 'Chưa cập nhật'
                                                                                                                                                    }}
                                                                                                                                                </span>

                                                                                                                                                <span>
                                                                                                                                                    <strong>Ca làm việc:</strong>
                                                                                                                                                    {{
                                                                                                        substr(
                                                                                                            (string) $shift->start_time,
                                                                                                            0,
                                                                                                            5
                                                                                                        )
                                                                                                                                                    }}
                                                                                                                                                    –
                                                                                                                                                    {{
                                                                                                        substr(
                                                                                                            (string) $shift->end_time,
                                                                                                            0,
                                                                                                            5
                                                                                                        )
                                                                                                                                                    }}
                                                                                                                                                </span>

                                                                                                                                                <span>
                                                                                                                                                    <strong>Chuyên khoa:</strong>
                                                                                                                                                    {{
                                                                                                        $shift->specialty?->name
                                                                                                        ?? 'Chưa cập nhật'
                                                                                                                                                    }}
                                                                                                                                                </span>

                                                                                                                                            </div>

                                                                                                                                            <div class="mw-slot-grid">

                                                                                                                                                @foreach ($shiftEntries as $entry)
                                                                                                                                                                                            @php
                                                                                                                                                                                                $slot = $entry['slot'];

                                                                                                                                                                                                $bookable =
                                                                                                                                                                                                    $entry['state']
                                                                                                                                                                                                    === 'open';
                                                                                                                                                                                            @endphp

                                                                                                                                                                                            <button
                                                                                                                                                                                                type="button"
                                                                                                                                                                                                class="mw-slot {{ $bookable ? '' : 'is-' . $entry['state'] }}"
                                                                                                                                                                                                data-slot
                                                                                                                                                                                                data-state="{{ $entry['state'] }}"
                                                                                                                                                                                                data-start-ts="{{ $entry['starts_at']->timestamp }}"
                                                                                                                                                                                                data-slot-label="{{ substr((string) $slot->start_time, 0, 5) }} – {{ substr((string) $slot->end_time, 0, 5) }}"
                                                                                                                                                                                                data-slot-day="{{ $dayDate->format('d/m/Y') }}"
                                                                                                                                                                                                data-book-url="{{ $bookable ? route('patient.appointments.create', $slot) : '' }}"
                                                                                                                                                                                                aria-pressed="false"
                                                                                                                                                                                                @disabled(!$bookable)
                                                                                                                                                                                            >

                                                                                                                                                                                                <span class="mw-slot-time">
                                                                                                                                                                                                    {{
                                                                                                                                                    substr(
                                                                                                                                                        (string) $slot->start_time,
                                                                                                                                                        0,
                                                                                                                                                        5
                                                                                                                                                    )
                                                                                                                                                                                                    }}
                                                                                                                                                                                                    –
                                                                                                                                                                                                    {{
                                                                                                                                                    substr(
                                                                                                                                                        (string) $slot->end_time,
                                                                                                                                                        0,
                                                                                                                                                        5
                                                                                                                                                    )
                                                                                                                                                                                                    }}
                                                                                                                                                                                                </span>

                                                                                                                                                                                                <span
                                                                                                                                                                                                    class="mw-slot-status"
                                                                                                                                                                                                    data-slot-status
                                                                                                                                                                                                >
                                                                                                                                                                                                    {{
                                                                                                                                                    $entry['state'] === 'past'
                                                                                                                                                    ? 'Đã qua giờ'
                                                                                                                                                    : (
                                                                                                                                                        $entry['state'] === 'full'
                                                                                                                                                        ? 'Hết chỗ'
                                                                                                                                                        : 'Còn ' . $entry['remaining'] . ' chỗ'
                                                                                                                                                    )
                                                                                                                                                                                                    }}
                                                                                                                                                                                                </span>

                                                                                                                                                                                            </button>

                                                                                                                                                @endforeach

                                                                                                                                            </div>
                                                                                                                                        </div>

                                                                                                    @endforeach

                                                                                                </div>
                                                                                            </section>

                                                                @endif
                                                        @endforeach

                                                    </div>
                                                @endforeach

                                                {{-- THANH XÁC NHẬN --}}
                                                <div class="mw-bookbar">

                                                    <p data-booking-summary>
                                                        Chọn một khung giờ còn chỗ ở trên
                                                        để tiếp tục đặt lịch.
                                                    </p>

                                                    <a
                                                        class="mw-btn mw-btn--primary is-disabled"
                                                        data-booking-action
                                                        aria-disabled="true"
                                                        tabindex="-1"
                                                    >
                                                        Đặt lịch
                                                    </a>

                                                </div>
                                            </div>
                                        </article>

                        @endforeach

                    </div>
                @endif

            </div>
        </section>
    </div>

    <script>
    (function () {
        'use strict';

        const root = document.getElementById('mw-doctor-search');

        const specialty = document.getElementById('specialty_id');
        const doctor = document.getElementById('doctor_id');
        const date = document.getElementById('date');

        const appZone = @json(
            config('app.timezone', 'Asia/Ho_Chi_Minh')
        );

        const renderedDate = @json(
            today()->toDateString()
        );

        /*
         * 1. Đồng bộ bộ lọc
         * Không tự tra cứu khi đổi lựa chọn.
         */
        function filterDoctorOptions() {
            if (!specialty || !doctor) return;

            Array.from(doctor.options).forEach(option => {
                if (!option.value) return;

                const match =
                    !specialty.value
                    || option.dataset.specialty === specialty.value
                    || option.dataset.general === '1';

                option.hidden = !match;
                option.disabled = !match;
            });

            if (doctor.selectedOptions[0]?.disabled) {
                doctor.value = '';
            }
        }

        function restoreFilters() {
            if (!specialty || !doctor || !date) return;

            const params = new URLSearchParams(
                location.search
            );

            specialty.value = params.get('specialty_id') || '';

            if (specialty.selectedIndex < 0) {
                specialty.value = '';
            }

            filterDoctorOptions();

            doctor.value = params.get('doctor_id') || '';

            if (
                doctor.selectedIndex < 0
                || doctor.selectedOptions[0]?.disabled
            ) {
                doctor.value = '';
            }

            date.value = params.get('date') || '';
        }

        specialty?.addEventListener('change', () => {
            doctor.value = '';
            filterDoctorOptions();
        });

        restoreFilters();

        /*
         * 2. Chọn / Ẩn lịch bác sĩ
         */
        function resetBooking(card) {
            card.querySelectorAll('[data-slot]').forEach(btn => {
                btn.classList.remove('is-selected');
                btn.setAttribute('aria-pressed', 'false');
            });

            const action = card.querySelector(
                '[data-booking-action]'
            );

            action.removeAttribute('href');
            action.setAttribute('aria-disabled', 'true');
            action.setAttribute('tabindex', '-1');
            action.classList.add('is-disabled');

            card.querySelector(
                '[data-booking-summary]'
            ).textContent =
                'Chọn một khung giờ còn chỗ ở trên để tiếp tục đặt lịch.';
        }

        const cards = Array.from(
            root.querySelectorAll('[data-doctor-card]')
        );

        cards.forEach(card => {
            const toggle = card.querySelector(
                '[data-doctor-toggle]'
            );

            toggle.addEventListener('click', () => {
                const opening = !card.classList.contains(
                    'is-open'
                );

                cards.forEach(other => {
                    other.classList.remove('is-open');

                    other.querySelector(
                        '[data-doctor-toggle]'
                    ).setAttribute('aria-expanded', 'false');

                    other.querySelector(
                        '[data-toggle-label]'
                    ).textContent = 'Chọn';
                });

                if (opening) {
                    card.classList.add('is-open');

                    toggle.setAttribute(
                        'aria-expanded',
                        'true'
                    );

                    toggle.querySelector(
                        '[data-toggle-label]'
                    ).textContent = 'Ẩn lịch';
                }
            });

            /*
             * 3. Chọn ngày
             */
            card.querySelectorAll('[data-day-tab]').forEach(tab => {
                tab.addEventListener('click', () => {
                    card.querySelectorAll(
                        '[data-day-tab]'
                    ).forEach(t => {
                        t.classList.toggle(
                            'is-active',
                            t === tab
                        );

                        t.setAttribute(
                            'aria-pressed',
                            t === tab ? 'true' : 'false'
                        );
                    });

                    card.querySelectorAll(
                        '[data-day-panel]'
                    ).forEach(panel => {
                        panel.classList.toggle(
                            'is-active',
                            panel.dataset.dayPanel === tab.dataset.day
                        );
                    });

                    resetBooking(card);
                });
            });

            /*
             * 4. Chọn khung giờ còn chỗ
             */
            card.querySelectorAll('[data-slot]').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (
                        btn.disabled
                        || btn.dataset.state !== 'open'
                        || !btn.dataset.bookUrl
                    ) {
                        return;
                    }

                    resetBooking(card);

                    btn.classList.add('is-selected');
                    btn.setAttribute('aria-pressed', 'true');

                    card.querySelector(
                        '[data-booking-summary]'
                    ).textContent =
                        'Đã chọn: '
                        + btn.dataset.slotDay
                        + ' · '
                        + btn.dataset.slotLabel;

                    const action = card.querySelector(
                        '[data-booking-action]'
                    );

                    action.href = btn.dataset.bookUrl;
                    action.classList.remove('is-disabled');

                    action.setAttribute(
                        'aria-disabled',
                        'false'
                    );

                    action.removeAttribute('tabindex');
                });
            });

            card.querySelector(
                '[data-booking-action]'
            ).addEventListener('click', event => {
                const link = event.currentTarget;

                if (
                    link.getAttribute('aria-disabled') === 'true'
                ) {
                    event.preventDefault();
                }
            });
        });

        /*
         * 5. Tính ngày hiện tại theo timezone của Laravel
         */
        function appDateToday() {
            try {
                const parts = new Intl.DateTimeFormat(
                    'en-GB',
                    {
                        timeZone: appZone,
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    }
                ).formatToParts(new Date());

                const values = Object.fromEntries(
                    parts.map(part => [
                        part.type,
                        part.value
                    ])
                );

                return (
                    values.year
                    + '-'
                    + values.month
                    + '-'
                    + values.day
                );
            } catch (_) {
                return renderedDate;
            }
        }

        /*
         * 6. Tự cập nhật trạng thái hết giờ
         * Khi sang ngày mới, tải lại để loại ngày cũ.
         */
        function refreshTimeStates() {
            if (appDateToday() !== renderedDate) {

                const params = new URLSearchParams(
                    location.search
                );

                // Nếu bộ lọc đang chọn ngày hôm qua,
                // bỏ ngày cũ nhưng giữ bác sĩ/chuyên khoa.
                if (
                    params.has('date')
                    && params.get('date') < appDateToday()
                ) {
                    params.delete('date');
                }

                const query = params.toString();

                location.replace(
                    location.pathname
                    + (query ? '?' + query : '')
                );

                return;
            }

            const currentTimestamp = Math.floor(
                Date.now() / 1000
            );

            cards.forEach(card => {
                let selectedExpired = false;

                card.querySelectorAll(
                    '[data-slot]'
                ).forEach(btn => {

                    if (btn.dataset.state !== 'open') {
                        return;
                    }

                    if (
                        Number(btn.dataset.startTs)
                        > currentTimestamp
                    ) {
                        return;
                    }

                    if (
                        btn.classList.contains('is-selected')
                    ) {
                        selectedExpired = true;
                    }

                    btn.dataset.state = 'past';
                    btn.disabled = true;

                    btn.classList.add('is-past');
                    btn.classList.remove('is-selected');

                    btn.querySelector(
                        '[data-slot-status]'
                    ).textContent = 'Đã qua giờ';
                });

                if (selectedExpired) {
                    resetBooking(card);
                }

                /*
                 * Cập nhật trạng thái Sáng / Chiều
                 */
                card.querySelectorAll(
                    '[data-period]'
                ).forEach(period => {

                    const slots = Array.from(
                        period.querySelectorAll('[data-slot]')
                    );

                    const open = slots.filter(
                        btn => !btn.disabled
                    ).length;

                    const allPast = slots.every(
                        btn => btn.dataset.state === 'past'
                    );

                    period.classList.toggle(
                        'is-unavailable',
                        open === 0
                    );

                    const badge = period.querySelector(
                        '[data-period-badge]'
                    );

                    badge.classList.toggle(
                        'is-unavailable',
                        open === 0
                    );

                    badge.textContent = open
                        ? 'Còn ' + open + ' khung giờ'
                        : (
                            allPast
                                ? 'Đã qua giờ'
                                : 'Hết chỗ'
                        );
                });

                /*
                 * Cập nhật trạng thái từng ngày
                 */
                card.querySelectorAll(
                    '[data-day-tab]'
                ).forEach(tab => {

                    const panel = Array.from(
                        card.querySelectorAll(
                            '[data-day-panel]'
                        )
                    ).find(
                        p => p.dataset.dayPanel === tab.dataset.day
                    );

                    if (!panel) return;

                    const slots = Array.from(
                        panel.querySelectorAll('[data-slot]')
                    );

                    const open = slots.filter(
                        btn => !btn.disabled
                    ).length;

                    const allPast =
                        slots.length > 0
                        && slots.every(
                            btn => btn.dataset.state === 'past'
                        );

                    tab.classList.toggle(
                        'is-unavailable',
                        open === 0
                    );

                    tab.querySelector(
                        '[data-day-status]'
                    ).textContent = open
                        ? 'Còn ' + open + ' suất'
                        : (
                            allPast
                                ? 'Đã qua giờ'
                                : 'Hết chỗ'
                        );
                });

                /*
                 * Cập nhật trạng thái bác sĩ
                 */
                const remaining = card.querySelectorAll(
                    '[data-slot]:not(:disabled)'
                ).length;

                const status = card.querySelector(
                    '[data-doctor-availability]'
                );

                status.classList.toggle(
                    'is-none',
                    remaining === 0
                );

                status.textContent = remaining
                    ? 'Đang nhận lịch'
                    : 'Tạm hết suất';
            });
        }

        refreshTimeStates();

        // Kiểm tra mỗi 30 giây khi trang đang mở.
        setInterval(refreshTimeStates, 30000);

        window.addEventListener('pageshow', event => {
            if (event.persisted) {
                location.reload();
                return;
            }

            restoreFilters();
            refreshTimeStates();
        });
    })();
    </script>
@endsection
