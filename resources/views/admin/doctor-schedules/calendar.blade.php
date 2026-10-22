@extends('admin.layouts.app')
@section('admin-page', 'doctor-schedules-calendar')

@section(
    'title',
    'Lịch bác sĩ theo tháng - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Lịch và đặt khám</span>
    <span aria-hidden="true">/</span>
    <span>Lịch làm việc bác sĩ</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Lịch tháng</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Lịch bác sĩ theo tháng
            </h1>

            <p class="page-description">
                Xem lịch làm việc của bác sĩ
                theo dạng lịch tháng.
            </p>

        </div>

        <div class="header-actions">

            <a
                href="{{ route(
                    'admin.doctor-schedules.index'
                ) }}"
                class="admin-btn admin-btn-secondary btn btn-secondary"
            >
                ☰ Danh sách
            </a>

            <a
                href="{{ route(
                    'admin.doctor-schedules.create'
                ) }}"
                class="btn btn-primary"
            >
                + Thêm lịch
            </a>

        </div>

    </div>


    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc lịch bác sĩ theo tháng',
        'description' => 'Lọc theo tháng, bác sĩ, chuyên khoa và phòng khám.',
    ])
        <form
            method="GET"
            action="{{ route(
                'admin.doctor-schedules.calendar'
            ) }}"
            class="filter-form admin-filter-form"
        >

            <div class="filter-item admin-filter-field">

                <label class="filter-label">
                    Tháng
                </label>

                <input
                    type="month"
                    name="month"
                    class="filter-control"
                    value="{{ $month }}"
                >
@error('month')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-item admin-filter-field">

                <label class="filter-label">
                    Bác sĩ
                </label>

                <select
                    name="doctor_id"
                    class="filter-control"
                >

                    <option value="">
                        Tất cả bác sĩ
                    </option>

                    @foreach ($doctors as $doctor)

                        <option
                            value="{{ $doctor->id }}"
                            @selected(
                                (string) $doctorId
                                ===
                                (string) $doctor->id
                            )
                        >
                            {{ $doctor->doctor_code }}
                            -
                            {{ $doctor->full_name }}
                        </option>

                    @endforeach

                </select>
@error('doctor_id')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-item admin-filter-field">

                <label class="filter-label">
                    Chuyên khoa
                </label>

                <select
                    name="specialty_id"
                    class="filter-control"
                >

                    <option value="">
                        Tất cả chuyên khoa
                    </option>

                    @foreach (
                        $specialties
                        as $specialty
                    )

                        <option
                            value="{{ $specialty->id }}"
                            @selected(
                                (string) $specialtyId
                                ===
                                (string) $specialty->id
                            )
                        >
                            {{ $specialty->name }}
                        </option>

                    @endforeach

                </select>
@error('specialty_id')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-item admin-filter-field">

                <label class="filter-label">
                    Phòng khám
                </label>

                <select
                    name="examination_room_id"
                    class="filter-control"
                >

                    <option value="">
                        Tất cả phòng khám
                    </option>

                    @foreach (
                        $examinationRooms
                        as $room
                    )

                        <option
                            value="{{ $room->id }}"
                            @selected(
                                (string) $roomId
                                ===
                                (string) $room->id
                            )
                        >
                            {{ $room->room_code }}
                            -
                            {{ $room->name }}
                        </option>

                    @endforeach

                </select>
@error('examination_room_id')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-actions admin-filter-layout">





            </div>


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route(
                        'admin.doctor-schedules.calendar'
                    ), 'searchOnly' => false])
        </form>
    @endcomponent


    <div class="calendar-navigation">

        <a
            href="{{ route(
                'admin.doctor-schedules.calendar',
                array_filter([
                    'month' =>
                        $previousMonth,

                    'doctor_id' =>
                        $doctorId,

                    'specialty_id' =>
                        $specialtyId,

                    'examination_room_id' =>
                        $roomId,
                ])
            ) }}"
            class="btn btn-secondary"
        >
            ← Tháng trước
        </a>


        <div class="calendar-month-title">

            Tháng
            {{ $currentMonth->format('m/Y') }}

        </div>


        <a
            href="{{ route(
                'admin.doctor-schedules.calendar',
                array_filter([
                    'month' =>
                        $nextMonth,

                    'doctor_id' =>
                        $doctorId,

                    'specialty_id' =>
                        $specialtyId,

                    'examination_room_id' =>
                        $roomId,
                ])
            ) }}"
            class="btn btn-secondary"
        >
            Tháng sau →
        </a>

    </div>


    <div class="calendar-container">

        <div class="calendar-week-header">

            <div>Thứ 2</div>
            <div>Thứ 3</div>
            <div>Thứ 4</div>
            <div>Thứ 5</div>
            <div>Thứ 6</div>
            <div>Thứ 7</div>
            <div>Chủ nhật</div>

        </div>


        <div class="calendar-grid">

            @foreach (
                $calendarDays
                as $day
            )

                @php

                    $dateKey =
                        $day->format('Y-m-d');

                    $daySchedules =
                        $schedules->get(
                            $dateKey,
                            collect()
                        );

                    $isCurrentMonth =
                        $day->month
                        ===
                        $currentMonth->month;

                    $isToday =
                        $day->isToday();

                @endphp


                <div
                    class="
                        calendar-day

                        {{ !$isCurrentMonth
                            ? 'other-month'
                            : '' }}

                        {{ $isToday
                            ? 'today'
                            : '' }}
                    "
                >

                    <div class="calendar-day-number">

                        <span>
                            {{ $day->day }}
                        </span>

                        @if ($isToday)

                            <span class="today-label">
                                Hôm nay
                            </span>

                        @endif

                    </div>


                    <div class="calendar-events">

                        @forelse (
                            $daySchedules
                            as $schedule
                        )

                            <a
                                href="{{ route(
                                    'admin.doctor-schedules.show',
                                    $schedule
                                ) }}"
                                class="
                                    calendar-event

                                    {{ $schedule->status
                                        === 'INACTIVE'
                                        ? 'inactive'
                                        : '' }}
                                "
                            >

                                <div class="event-time">

                                    {{ substr(
                                        $schedule->start_time,
                                        0,
                                        5
                                    ) }}

                                    -

                                    {{ substr(
                                        $schedule->end_time,
                                        0,
                                        5
                                    ) }}

                                </div>


                                <div class="event-doctor">

                                    {{ $schedule
                                        ->doctor
                                        ?->full_name
                                        ?? 'Không rõ bác sĩ' }}

                                </div>


                                <div class="event-room">

                                    {{ $schedule
                                        ->examinationRoom
                                        ?->room_code
                                        ?? '—' }}

                                </div>

                            </a>

                        @empty

                            <div class="no-event">
                                —
                            </div>

                        @endforelse

                    </div>

                </div>

            @endforeach

        </div>

    </div>

@endsection
