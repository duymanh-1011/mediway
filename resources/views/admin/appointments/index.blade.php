@extends('admin.layouts.app')
@section('admin-page', 'appointments-index')

@section(
    'title',
    'Quản lý lịch hẹn - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Lịch và đặt khám</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Lịch hẹn</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Quản lý lịch hẹn
            </h1>

            <p class="page-description">
                Quản lý lịch khám đã đặt của bệnh nhân.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.appointments.create'
            ) }}"
            class="admin-btn admin-btn-primary"
        >Thêm lịch hẹn</a>

    </div>


    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-label">
                Tổng lịch hẹn
            </div>

            <div class="stat-value">
                {{ $totalAppointments }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Lịch hôm nay
            </div>

            <div class="stat-value">
                {{ $todayAppointments }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Đã hủy
            </div>

            <div class="stat-value">
                {{ $cancelledAppointments }}
            </div>

        </div>

    </div>


    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc lịch hẹn',
        'description' => 'Lọc theo từ khóa, ngày khám, trạng thái, bác sĩ, chuyên khoa và phòng khám.',
    ])
        <form data-filter-fields="6"
            method="GET"
            action="{{ route(
                'admin.appointments.index'
            ) }}"
            class="filter-grid admin-filter-form"
        >

            <div class="filter-group admin-filter-field">

                <label>Tìm kiếm</label>

                <input
                    type="text"
                    name="keyword"
                    class="form-control"
                    value="{{ $keyword }}"
                    placeholder="Mã lịch, bệnh nhân, bác sĩ..."
                >
@error('keyword')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-group admin-filter-field">

                <label>Ngày khám</label>

                <input
                    type="date"
                    name="appointment_date"
                    class="form-control"
                    value="{{ $appointmentDate }}"
                >
@error('appointment_date')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-group admin-filter-field">

                <label>Trạng thái</label>

                <select
                    name="appointment_status_id"
                    class="form-control"
                >

                    <option value="">
                        Tất cả
                    </option>

                    @foreach ($statuses as $item)

                        <option
                            value="{{ $item->id }}"
                            @selected(
                                (string) $statusId
                                ===
                                (string) $item->id
                            )
                        >
                            {{ \App\Models\AdminDisplay::label($item->name) }}
                        </option>

                    @endforeach

                </select>
@error('appointment_status_id')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-group admin-filter-field">

                <label>Bác sĩ</label>

                <select
                    name="doctor_id"
                    class="form-control"
                >

                    <option value="">
                        Tất cả
                    </option>

                    @foreach ($doctors as $doctor)

                        <option
                    value="{{ $doctor->id }}"
                    data-specialty-id="{{ $doctor->specialty_id }}"
                    data-is-general="{{ $doctor->specialty?->is_general ? '1' : '0' }}"
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


            <div class="filter-group admin-filter-field">

                <label>Chuyên khoa</label>

                <select
                    name="specialty_id"
                    class="form-control"
                >

                    <option value="">
                        Tất cả
                    </option>

                    @foreach ($specialties as $specialty)

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


            <div class="filter-group admin-filter-field">

                <label>Phòng khám</label>

                <select
                    name="examination_room_id"
                    class="form-control"
                >

                    <option value="">
                        Tất cả
                    </option>

                    @foreach ($examinationRooms as $room)

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
                        'admin.appointments.index'
                    ), 'searchOnly' => false])
        </form>
    @endcomponent


    <div class="card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách lịch hẹn'])


        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Mã lịch</th>
                        <th>Bệnh nhân</th>
                        <th>Ngày / giờ</th>
                        <th>Bác sĩ</th>
                        <th>Phòng</th>
                        <th>Trạng thái</th>
                        <th>Lượt khám</th>
                        <th>Thao tác</th>
                    </tr>

                </thead>

                <tbody>

                @forelse ($appointments as $appointment)

                    <tr>

                        @include('admin.layouts.partials.row-number', ['rows' => $appointments, 'index' => $loop->index])
                        <td>
                            <strong>
                                @include('admin.layouts.partials.record-code', ['code' => $appointment->appointment_code])
                            </strong>
                        </td>

                        <td>

                            @include('admin.layouts.partials.record-code', ['code' => $appointment->patient?->patient_code])

                            <br>

                            <span class="muted">
                                {{ $appointment->patient?->full_name }}
                            </span>

                        </td>

                        <td>

                            {{ $appointment
                                ->appointment_date
                                ?->format('d/m/Y') }}

                            <br>

                            <span class="muted">

                                {{ substr(
                                    $appointment
                                        ->appointmentTimeSlot
                                        ?->start_time
                                        ?? '',
                                    0,
                                    5
                                ) }}

                                -

                                {{ substr(
                                    $appointment
                                        ->appointmentTimeSlot
                                        ?->end_time
                                        ?? '',
                                    0,
                                    5
                                ) }}

                            </span>

                        </td>

                        <td>
                            {{ $appointment->doctor?->full_name }}
                        </td>

                        <td>
                            @include('admin.layouts.partials.record-code', ['code' => $appointment
                                ->examinationRoom
                                ?->room_code])
                        </td>

                        <td>

                            @include('admin.layouts.partials.status-badge', ['badgeCode' => $appointment->appointmentStatus?->name ?? '—'])

                        </td>

                        <td>

                            @if ($appointment->medical_visits_count > 0)

                                <span class="visit-yes">
                                    Đã phát sinh
                                </span>

                            @else

                                <span class="muted">
                                    Chưa có
                                </span>

                            @endif

                        </td>

                        <td><div class="admin-actions admin-table-actions">

                            <a
                                href="{{ route(
                                    'admin.appointments.show',
                                    $appointment
                                ) }}"
                                class="admin-btn admin-btn-detail"
                            >Chi tiết</a>

                        </div></td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="9"
                            class="empty-state"
                        >
                            Chưa có lịch hẹn phù hợp.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        <div class="pagination-wrapper">
            {{ $appointments->links() }}
        </div>

    </div>

@endsection
