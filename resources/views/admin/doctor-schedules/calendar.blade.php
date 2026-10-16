@extends('admin.layouts.app')

@section(
    'title',
    'Lịch bác sĩ theo tháng - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">

        Admin /
        Lịch & đặt khám /
        Lịch làm việc bác sĩ /
        Lịch tháng

    </div>

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
                class="btn btn-secondary"
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


    <div class="card filter-card">

        <form
            method="GET"
            action="{{ route(
                'admin.doctor-schedules.calendar'
            ) }}"
            class="filter-form"
        >

            <div class="filter-item">

                <label class="filter-label">
                    Tháng
                </label>

                <input
                    type="month"
                    name="month"
                    class="filter-control"
                    value="{{ $month }}"
                >

            </div>


            <div class="filter-item">

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

            </div>


            <div class="filter-item">

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

            </div>


            <div class="filter-item">

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

            </div>


            <div class="filter-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Xem lịch
                </button>

                <a
                    href="{{ route(
                        'admin.doctor-schedules.calendar'
                    ) }}"
                    class="btn btn-secondary"
                >
                    Đặt lại
                </a>

            </div>

        </form>

    </div>


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


@push('styles')

<style>

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;

        gap: 20px;

        margin-bottom: 20px;
    }

    .header-actions {
        display: flex;

        gap: 8px;
    }

    .filter-card {
        margin-bottom: 18px;
    }

    .filter-form {
        display: grid;

        grid-template-columns:
            170px
            minmax(200px, 1fr)
            minmax(180px, 1fr)
            minmax(180px, 1fr)
            auto;

        gap: 12px;

        align-items: end;
    }

    .filter-label {
        display: block;

        margin-bottom: 6px;

        color: #475569;

        font-family:
            "Times New Roman",
            Times,
            serif;

        font-size: 13px;
        font-weight: 700;
    }

    .filter-control {
        width: 100%;

        padding: 9px 11px;

        border: 1px solid #cbd5e1;
        border-radius: 8px;

        background: #ffffff;

        font-family:
            "Times New Roman",
            Times,
            serif;

        outline: none;
    }

    .filter-actions {
        display: flex;

        gap: 8px;
    }

    .calendar-navigation {
        display: grid;

        grid-template-columns:
            auto
            1fr
            auto;

        gap: 15px;

        align-items: center;

        margin-bottom: 15px;
    }

    .calendar-month-title {
        text-align: center;

        color: #0f172a;

        font-size: 22px;
        font-weight: 700;
    }

    .calendar-container {
        overflow-x: auto;

        border: 1px solid #dbe3ef;
        border-radius: 10px;

        background: #ffffff;
    }

    .calendar-week-header {
        min-width: 1050px;

        display: grid;

        grid-template-columns:
            repeat(7, minmax(150px, 1fr));

        background: #f8fafc;

        border-bottom: 1px solid #dbe3ef;
    }

    .calendar-week-header div {
        padding: 11px 8px;

        text-align: center;

        color: #334155;

        font-size: 13px;
        font-weight: 700;

        border-right: 1px solid #e2e8f0;
    }

    .calendar-week-header div:last-child {
        border-right: none;
    }

    .calendar-grid {
        min-width: 1050px;

        display: grid;

        grid-template-columns:
            repeat(7, minmax(150px, 1fr));
    }

    .calendar-day {
        min-height: 150px;

        padding: 8px;

        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;

        background: #ffffff;
    }

    .calendar-day:nth-child(7n) {
        border-right: none;
    }

    .calendar-day.other-month {
        background: #f8fafc;
    }

    .calendar-day.today {
        background: #eff6ff;
    }

    .calendar-day-number {
        display: flex;
        justify-content: space-between;
        align-items: center;

        margin-bottom: 8px;

        font-size: 14px;
        font-weight: 700;
    }

    .other-month .calendar-day-number {
        color: #94a3b8;
    }

    .today-label {
        padding: 2px 5px;

        border-radius: 5px;

        background: #2563eb;
        color: #ffffff;

        font-size: 10px;
    }

    .calendar-events {
        display: flex;
        flex-direction: column;

        gap: 6px;
    }

    .calendar-event {
        display: block;

        padding: 7px;

        border: 1px solid #bfdbfe;
        border-radius: 7px;

        background: #eff6ff;

        color: #1e3a8a;

        text-decoration: none;

        transition:
            transform 0.12s ease,
            box-shadow 0.12s ease;
    }

    .calendar-event:hover {
        transform: translateY(-1px);

        box-shadow:
            0 2px 8px
            rgba(15, 23, 42, 0.08);
    }

    .calendar-event.inactive {
        border-color: #e2e8f0;

        background: #f8fafc;
        color: #64748b;
    }

    .event-time {
        margin-bottom: 3px;

        font-size: 12px;
        font-weight: 700;
    }

    .event-doctor {
        margin-bottom: 2px;

        overflow: hidden;

        font-size: 12px;
        font-weight: 600;

        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .event-room {
        font-size: 11px;
    }

    .no-event {
        color: #cbd5e1;

        text-align: center;

        font-size: 12px;
    }

    @media (max-width: 1100px) {

        .filter-form {
            grid-template-columns:
                repeat(2, 1fr);
        }

    }

    @media (max-width: 700px) {

        .page-header {
            flex-direction: column;
        }

        .filter-form {
            grid-template-columns: 1fr;
        }

        .calendar-navigation {
            grid-template-columns: 1fr;

            text-align: center;
        }

    }

</style>

@endpush