@extends('admin.layouts.app')

@section(
    'title',
    'Lịch làm việc bác sĩ - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">
        Admin /
        Lịch & đặt khám /
        Lịch làm việc bác sĩ
    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Lịch làm việc bác sĩ
            </h1>

            <p class="page-description">
                Quản lý lịch làm việc theo bác sĩ,
                chuyên khoa, phòng khám và ngày làm việc.
            </p>

        </div>

    <div class="header-actions">

        <a
            href="{{ route(
                'admin.doctor-schedules.calendar'
            ) }}"
            class="btn btn-secondary"
        >
            Lịch tháng
        </a>

        <a
            href="{{ route(
                'admin.doctor-schedules.create'
            ) }}"
            class="btn btn-primary"
        >
            + Thêm lịch làm việc
        </a>

    </div>

    </div>


    <div class="stats-grid">

        <div class="mini-card">

            <div class="mini-card-label">
                Tổng lịch
            </div>

            <div class="mini-card-value">
                {{ $totalSchedules }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Đang hoạt động
            </div>

            <div class="mini-card-value success">
                {{ $activeSchedules }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Lịch hôm nay
            </div>

            <div class="mini-card-value">
                {{ $todaySchedules }}
            </div>

        </div>

    </div>


    <div class="card">

        <form
            method="GET"
            action="{{ route(
                'admin.doctor-schedules.index'
            ) }}"
            class="filter-form"
        >

            <div class="filter-item">

                <label class="filter-label">
                    Tìm kiếm
                </label>

                <input
                    type="text"
                    name="keyword"
                    class="filter-control"
                    value="{{ $keyword }}"
                    placeholder="Mã/tên bác sĩ, chuyên khoa, phòng..."
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
                        Tất cả phòng
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
                            {{ $room->name }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div class="filter-item">

                <label class="filter-label">
                    Ngày làm việc
                </label>

                <input
                    type="date"
                    name="work_date"
                    class="filter-control"
                    value="{{ $workDate }}"
                >

            </div>


            <div class="filter-item">

                <label class="filter-label">
                    Trạng thái
                </label>

                <select
                    name="status"
                    class="filter-control"
                >

                    <option value="">
                        Tất cả
                    </option>

                    <option
                        value="ACTIVE"
                        @selected(
                            $status === 'ACTIVE'
                        )
                    >
                        ACTIVE
                    </option>

                    <option
                        value="INACTIVE"
                        @selected(
                            $status === 'INACTIVE'
                        )
                    >
                        INACTIVE
                    </option>

                </select>

            </div>


            <div class="filter-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tìm kiếm
                </button>

                <a
                    href="{{ route(
                        'admin.doctor-schedules.index'
                    ) }}"
                    class="btn btn-secondary"
                >
                    Đặt lại
                </a>

            </div>

        </form>


        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Ngày</th>
                        <th>Bác sĩ</th>
                        <th>Chuyên khoa</th>
                        <th>Phòng</th>
                        <th>Thời gian</th>
                        <th>Slot</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>

                </thead>

                <tbody>

                @forelse (
                    $doctorSchedules
                    as $schedule
                )

                    <tr>

                        <td>
                            {{ $doctorSchedules->firstItem()
                                + $loop->index }}
                        </td>

                        <td>
                            <strong>
                                {{ $schedule
                                    ->work_date
                                    ->format('d/m/Y') }}
                            </strong>
                        </td>

                        <td>

                            {{ $schedule
                                ->doctor
                                ?->doctor_code }}

                            -

                            {{ $schedule
                                ->doctor
                                ?->full_name }}

                        </td>

                        <td>
                            {{ $schedule
                                ->specialty
                                ?->name
                                ?? '—' }}
                        </td>

                        <td>
                            {{ $schedule
                                ->examinationRoom
                                ?->name
                                ?? '—' }}
                        </td>

                        <td>

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

                        </td>

                        <td>
                            {{ $schedule
                                ->appointment_time_slots_count }}
                        </td>

                        <td>

                            @if (
                                $schedule->status
                                === 'ACTIVE'
                            )

                                <span
                                    class="badge badge-success"
                                >
                                    ACTIVE
                                </span>

                            @else

                                <span
                                    class="badge badge-danger"
                                >
                                    INACTIVE
                                </span>

                            @endif

                        </td>

                        <td>

                            <div class="action-group">

                                <a
                                    href="{{ route(
                                        'admin.doctor-schedules.show',
                                        $schedule
                                    ) }}"
                                    class="action-link view"
                                >
                                    Chi tiết
                                </a>

                                <a
                                    href="{{ route(
                                        'admin.doctor-schedules.edit',
                                        $schedule
                                    ) }}"
                                    class="action-link edit"
                                >
                                    Sửa
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'admin.doctor-schedules.destroy',
                                        $schedule
                                    ) }}"
                                    onsubmit="
                                        return confirm(
                                            'Bạn có chắc muốn xóa lịch làm việc này? Lịch có slot hoặc lịch hẹn sẽ không được phép xóa.'
                                        );
                                    "
                                >

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="action-link delete"
                                    >
                                        Xóa
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="9"
                            class="empty-state"
                        >
                            Không tìm thấy lịch làm việc phù hợp.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        <div class="pagination-wrapper">
            {{ $doctorSchedules->links() }}
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

    .stats-grid {
        display: grid;

        grid-template-columns:
            repeat(3, minmax(0, 1fr));

        gap: 14px;

        margin-bottom: 18px;
    }

    .mini-card {
        padding: 16px 18px;

        background: #ffffff;

        border: 1px solid #e2e8f0;
        border-radius: 11px;
    }

    .mini-card-label {
        margin-bottom: 7px;

        color: #64748b;

        font-size: 13px;
    }

    .mini-card-value {
        font-size: 26px;
        font-weight: 700;
    }

    .mini-card-value.success {
        color: #059669;
    }

    .filter-form {
        display: grid;

        grid-template-columns:
            repeat(3, minmax(0, 1fr));

        gap: 14px;

        align-items: end;

        margin-bottom: 20px;
    }

    .filter-label {
        display: block;

        margin-bottom: 6px;

        font-family:
            "Times New Roman",
            Times,
            serif;

        color: #475569;

        font-size: 13px;
        font-weight: 700;
    }

    .filter-control {
        width: 100%;

        padding: 10px 12px;

        font-family:
            "Times New Roman",
            Times,
            serif;

        border: 1px solid #cbd5e1;
        border-radius: 8px;

        background: #ffffff;

        outline: none;
    }

    .filter-actions {
        display: flex;

        gap: 8px;
    }

    .action-group {
        display: flex;
        align-items: center;

        gap: 7px;
    }

    .action-group form {
        margin: 0;
    }

    .action-link {
        padding: 6px 9px;

        border: none;
        border-radius: 6px;

        text-decoration: none;

        font-family:
            "Times New Roman",
            Times,
            serif;

        font-size: 13px;
        font-weight: 600;

        cursor: pointer;
    }

    .action-link.view {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .action-link.edit {
        background: #fffbeb;
        color: #b45309;
    }

    .action-link.delete {
        background: #fef2f2;
        color: #b91c1c;
    }

    .empty-state {
        padding: 40px !important;

        text-align: center !important;

        color: #94a3b8 !important;
    }

    .pagination-wrapper {
        margin-top: 18px;
    }

    @media (max-width: 900px) {

        .filter-form {
            grid-template-columns: 1fr 1fr;
        }

    }

    @media (max-width: 700px) {

        .stats-grid,
        .filter-form {
            grid-template-columns: 1fr;
        }

    }

    .header-actions {
        display: flex;
        gap: 8px;
    }

</style>

@endpush