@extends('admin.layouts.app')

@section(
    'title',
    'Quản lý lịch hẹn - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">
        Admin /
        Lịch & đặt khám /
        Lịch hẹn
    </div>

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
            class="btn btn-primary"
        >
            + Thêm lịch hẹn
        </a>

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


    <div class="card filter-card">

        <form
            method="GET"
            action="{{ route(
                'admin.appointments.index'
            ) }}"
            class="filter-grid"
        >

            <div class="filter-group">

                <label>Tìm kiếm</label>

                <input
                    type="text"
                    name="keyword"
                    class="form-control"
                    value="{{ $keyword }}"
                    placeholder="Mã lịch, bệnh nhân, bác sĩ..."
                >

            </div>


            <div class="filter-group">

                <label>Ngày khám</label>

                <input
                    type="date"
                    name="appointment_date"
                    class="form-control"
                    value="{{ $appointmentDate }}"
                >

            </div>


            <div class="filter-group">

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
                            {{ $item->name }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div class="filter-group">

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


            <div class="filter-group">

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

            </div>


            <div class="filter-group">

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

            </div>


            <div class="filter-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lọc
                </button>

                <a
                    href="{{ route(
                        'admin.appointments.index'
                    ) }}"
                    class="btn btn-secondary"
                >
                    Đặt lại
                </a>

            </div>

        </form>

    </div>


    <div class="card">

        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
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

                        <td>
                            <strong>
                                {{ $appointment->appointment_code }}
                            </strong>
                        </td>

                        <td>

                            {{ $appointment->patient?->patient_code }}

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
                            {{ $appointment
                                ->examinationRoom
                                ?->room_code }}
                        </td>

                        <td>

                            <span class="status-badge">
                                {{ $appointment
                                    ->appointmentStatus
                                    ?->name
                                    ?? '—' }}
                            </span>

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

                        <td>

                            <a
                                href="{{ route(
                                    'admin.appointments.show',
                                    $appointment
                                ) }}"
                                class="action-link"
                            >
                                Chi tiết
                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="8"
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


@push('styles')

<style>

    .page-header {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 20px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }

    .stat-card {
        padding: 16px 18px;

        background: #ffffff;

        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }

    .stat-label {
        margin-bottom: 6px;
        color: #64748b;
    }

    .stat-value {
        font-size: 26px;
        font-weight: 700;
    }

    .filter-card {
        margin-bottom: 20px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
        gap: 14px;
        align-items: end;
    }

    .filter-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 700;
    }

    .form-control {
        width: 100%;
        padding: 9px 11px;

        border: 1px solid #cbd5e1;
        border-radius: 8px;

        font-family:
            "Times New Roman",
            Times,
            serif;
    }

    .filter-actions {
        display: flex;
        gap: 8px;
    }

    .muted {
        color: #64748b;
        font-size: 13px;
    }

    .status-badge,
    .visit-yes {
        display: inline-block;

        padding: 4px 7px;

        border-radius: 6px;

        background: #eff6ff;
        color: #1d4ed8;

        font-size: 12px;
        font-weight: 700;
    }

    .visit-yes {
        background: #ecfdf5;
        color: #047857;
    }

    .action-link {
        text-decoration: none;
        color: #2563eb;
        font-weight: 700;
    }

    .empty-state {
        padding: 30px !important;
        text-align: center !important;
        color: #94a3b8 !important;
    }

    .pagination-wrapper {
        margin-top: 18px;
    }

    @media (max-width: 900px) {

        .filter-grid {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

    }

</style>

@endpush