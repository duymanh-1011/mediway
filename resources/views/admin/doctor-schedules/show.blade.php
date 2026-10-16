@extends('admin.layouts.app')

@section(
    'title',
    'Chi tiết lịch làm việc - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">

        Admin /
        Lịch & đặt khám /
        Lịch làm việc bác sĩ /
        #{{ $doctorSchedule->id }}

    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Lịch làm việc bác sĩ
            </h1>

            <p class="page-description">

                {{ $doctorSchedule->doctor?->doctor_code }}

                -

                {{ $doctorSchedule->doctor?->full_name }}

            </p>

        </div>

        <div class="header-actions">

            <a
                href="{{ route(
                    'admin.doctor-schedules.index'
                ) }}"
                class="btn btn-secondary"
            >
                ← Danh sách
            </a>

            <a
                href="{{ route(
                    'admin.doctor-schedules.edit',
                    $doctorSchedule
                ) }}"
                class="btn btn-primary"
            >
                Sửa lịch
            </a>

        </div>

    </div>


    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-label">
                Tổng khung giờ
            </div>

            <div class="stat-value">
                {{ $totalSlots }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Tổng sức chứa
            </div>

            <div class="stat-value">
                {{ $totalCapacity }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Lịch đã đặt
            </div>

            <div class="stat-value">
                {{ $totalAppointments }}
            </div>

        </div>

    </div>


    <div class="detail-grid">

        <div class="card">

            <h3 class="section-title">
                Thông tin lịch làm việc
            </h3>

            <div class="detail-row">

                <div class="detail-label">
                    Bác sĩ
                </div>

                <div class="detail-value">

                    {{ $doctorSchedule->doctor?->doctor_code }}

                    -

                    {{ $doctorSchedule->doctor?->full_name }}

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Chuyên khoa
                </div>

                <div class="detail-value">
                    {{ $doctorSchedule
                        ->specialty
                        ?->name
                        ?? '—' }}
                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Phòng khám
                </div>

                <div class="detail-value">

                    {{ $doctorSchedule
                        ->examinationRoom
                        ?->room_code }}

                    -

                    {{ $doctorSchedule
                        ->examinationRoom
                        ?->name }}

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Ngày làm việc
                </div>

                <div class="detail-value">
                    {{ $doctorSchedule
                        ->work_date
                        ->format('d/m/Y') }}
                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Thời gian
                </div>

                <div class="detail-value">

                    {{ substr(
                        $doctorSchedule->start_time,
                        0,
                        5
                    ) }}

                    -

                    {{ substr(
                        $doctorSchedule->end_time,
                        0,
                        5
                    ) }}

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Trạng thái
                </div>

                <div class="detail-value">

                    @if ($doctorSchedule->status === 'ACTIVE')

                        <span class="badge badge-success">
                            ACTIVE
                        </span>

                    @else

                        <span class="badge badge-danger">
                            INACTIVE
                        </span>

                    @endif

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Ghi chú
                </div>

                <div class="detail-value">
                    {{ $doctorSchedule->notes ?? '—' }}
                </div>

            </div>

        </div>


        <div class="card">

            <div class="slot-header">

                <div>

                    <h3 class="section-title">
                        Khung giờ khám
                    </h3>

                    <p class="section-description">
                        Quản lý slot và sức chứa của lịch làm việc.
                    </p>

                </div>

                <a
                    href="{{ route(
                        'admin.doctor-schedules.time-slots.create',
                        $doctorSchedule
                    ) }}"
                    class="btn btn-primary"
                >
                    + Thêm khung giờ
                </a>

            </div>


            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>

                        <tr>
                            <th>Thời gian</th>
                            <th>Sức chứa</th>
                            <th>Đã đặt</th>
                            <th>Còn trống</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>

                    </thead>

                    <tbody>

                    @forelse ($timeSlots as $slot)

                        @php

                            $remaining =
                                max(
                                    0,
                                    $slot->max_patients
                                    -
                                    $slot->appointments_count
                                );

                        @endphp

                        <tr>

                            <td>

                                {{ substr(
                                    $slot->start_time,
                                    0,
                                    5
                                ) }}

                                -

                                {{ substr(
                                    $slot->end_time,
                                    0,
                                    5
                                ) }}

                            </td>

                            <td>
                                {{ $slot->max_patients }}
                            </td>

                            <td>
                                {{ $slot->appointments_count }}
                            </td>

                            <td>
                                {{ $remaining }}
                            </td>

                            <td>

                                @if ($slot->status === 'ACTIVE')

                                    <span class="badge badge-success">
                                        ACTIVE
                                    </span>

                                @else

                                    <span class="badge badge-danger">
                                        INACTIVE
                                    </span>

                                @endif

                            </td>

                            <td>

                                <div class="action-group">

                                    <a
                                        href="{{ route(
                                            'admin.appointment-time-slots.edit',
                                            $slot->id
                                        ) }}"
                                        class="action-link edit"
                                    >
                                        Sửa
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'admin.appointment-time-slots.destroy',
                                            $slot->id
                                        ) }}"
                                        onsubmit="
                                            return confirm(
                                                'Bạn có chắc muốn xóa khung giờ này? Khung giờ đã có lịch hẹn sẽ không thể xóa.'
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
                                colspan="6"
                                class="empty-state"
                            >
                                Lịch làm việc này chưa có khung giờ.
                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

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
        margin-bottom: 7px;

        color: #64748b;

        font-size: 13px;
    }

    .stat-value {
        font-size: 26px;
        font-weight: 700;
    }

    .detail-grid {
        display: grid;

        grid-template-columns:
            minmax(300px, 0.8fr)
            minmax(0, 1.5fr);

        gap: 20px;
    }

    .section-title {
        margin: 0 0 16px;

        font-size: 18px;
    }

    .section-description {
        margin: -10px 0 15px;

        color: #64748b;

        font-size: 13px;
    }

    .slot-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;

        gap: 15px;

        margin-bottom: 10px;
    }

    .detail-row {
        display: grid;

        grid-template-columns:
            130px
            minmax(0, 1fr);

        gap: 15px;

        padding: 12px 0;

        border-bottom: 1px solid #f1f5f9;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        color: #64748b;

        font-size: 13px;
        font-weight: 700;
    }

    .detail-value {
        color: #334155;

        font-size: 14px;
        line-height: 1.55;
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

    .action-link.edit {
        background: #fffbeb;
        color: #b45309;
    }

    .action-link.delete {
        background: #fef2f2;
        color: #b91c1c;
    }

    .empty-state {
        padding: 30px !important;

        text-align: center !important;

        color: #94a3b8 !important;
    }

    @media (max-width: 1000px) {

        .detail-grid {
            grid-template-columns: 1fr;
        }

    }

    @media (max-width: 700px) {

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .slot-header {
            flex-direction: column;
        }

    }

</style>

@endpush