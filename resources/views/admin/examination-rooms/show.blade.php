@extends('admin.layouts.app')

@section(
    'title',
    'Chi tiết phòng khám - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">

        Admin /
        Danh mục /
        Phòng khám /
        {{ $examinationRoom->room_code }}

    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                {{ $examinationRoom->name }}
            </h1>

            <p class="page-description">

                {{ $examinationRoom->room_code }}

                ·

                Phòng
                {{ $examinationRoom->room_number }}

            </p>

        </div>

        <div class="header-actions">

            <a
                href="{{ route(
                    'admin.examination-rooms.index'
                ) }}"
                class="btn btn-secondary"
            >
                ← Danh sách
            </a>

            <a
                href="{{ route(
                    'admin.examination-rooms.edit',
                    $examinationRoom
                ) }}"
                class="btn btn-primary"
            >
                Sửa phòng khám
            </a>

        </div>

    </div>

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-label">
                Lịch bác sĩ
            </div>

            <div class="stat-value">
                {{ $scheduleCount }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Lịch hẹn
            </div>

            <div class="stat-value">
                {{ $appointmentCount }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Lượt khám
            </div>

            <div class="stat-value">
                {{ $visitCount }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Lượt hàng đợi
            </div>

            <div class="stat-value">
                {{ $queueCount }}
            </div>

        </div>

    </div>

    <div class="detail-grid">

        <div class="card">

            <h3 class="section-title">
                Thông tin phòng khám
            </h3>

            <div class="detail-row">

                <div class="detail-label">
                    Mã phòng
                </div>

                <div class="detail-value">
                    {{ $examinationRoom->room_code }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Tên phòng
                </div>

                <div class="detail-value">
                    {{ $examinationRoom->name }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Số phòng
                </div>

                <div class="detail-value">
                    {{ $examinationRoom->room_number }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Tầng
                </div>

                <div class="detail-value">
                    {{ $examinationRoom->floor ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Tòa nhà
                </div>

                <div class="detail-value">
                    {{ $examinationRoom->building ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Trạng thái
                </div>

                <div class="detail-value">

                    @if (
                        $examinationRoom->status
                        === 'ACTIVE'
                    )

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
                    Hướng dẫn vị trí
                </div>

                <div class="detail-value">
                    {{ $examinationRoom
                        ->location_instructions
                        ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Mô tả
                </div>

                <div class="detail-value">
                    {{ $examinationRoom
                        ->description
                        ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Ghi chú
                </div>

                <div class="detail-value">
                    {{ $examinationRoom
                        ->notes
                        ?? '—' }}
                </div>

            </div>

        </div>

        <div class="card">

            <h3 class="section-title">
                Lịch bác sĩ gần đây
            </h3>

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>

                        <tr>
                            <th>Ngày</th>
                            <th>Bác sĩ</th>
                            <th>Chuyên khoa</th>
                            <th>Thời gian</th>
                            <th>Trạng thái</th>
                        </tr>

                    </thead>

                    <tbody>

                    @forelse (
                        $recentSchedules
                        as $schedule
                    )

                        <tr>

                            <td>
                                {{
                                    \Carbon\Carbon::parse(
                                        $schedule->work_date
                                    )->format('d/m/Y')
                                }}
                            </td>

                            <td>

                                {{ $schedule->doctor_code }}

                                <br>

                                <small>
                                    {{ $schedule->doctor_name }}
                                </small>

                            </td>

                            <td>

                                {{ $schedule->specialty_code }}

                                <br>

                                <small>
                                    {{ $schedule->specialty_name }}
                                </small>

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
                                {{ $schedule->status }}
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="empty-state"
                            >
                                Chưa có lịch bác sĩ.
                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <div
        class="card"
        style="margin-top:20px;"
    >

        <h3 class="section-title">
            Lượt khám gần đây
        </h3>

        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th>Mã lượt khám</th>
                        <th>Bệnh nhân</th>
                        <th>Bác sĩ</th>
                        <th>Check-in</th>
                        <th>Trạng thái</th>
                    </tr>

                </thead>

                <tbody>

                @forelse (
                    $recentVisits
                    as $visit
                )

                    <tr>

                        <td>
                            <strong>
                                {{ $visit->visit_code }}
                            </strong>
                        </td>

                        <td>

                            {{ $visit->patient_code }}

                            <br>

                            <small>
                                {{ $visit->patient_name }}
                            </small>

                        </td>

                        <td>

                            {{ $visit->doctor_code }}

                            <br>

                            <small>
                                {{ $visit->doctor_name }}
                            </small>

                        </td>

                        <td>

                            @if ($visit->checked_in_at)

                                {{
                                    \Carbon\Carbon::parse(
                                        $visit->checked_in_at
                                    )->format(
                                        'd/m/Y H:i'
                                    )
                                }}

                            @else
                                —
                            @endif

                        </td>

                        <td>
                            {{ $visit->visit_status }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="5"
                            class="empty-state"
                        >
                            Chưa có lượt khám tại phòng này.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

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
            repeat(4, minmax(0, 1fr));

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
        color: #64748b;

        font-size: 12px;

        margin-bottom: 6px;
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
        margin:
            0
            0
            16px;

        font-size: 17px;
    }

    .detail-row {
        display: grid;

        grid-template-columns:
            150px
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

        font-size: 12px;
        font-weight: 700;
    }

    .detail-value {
        color: #334155;

        font-size: 13px;
        line-height: 1.55;
    }

    .empty-state {
        padding: 30px !important;

        text-align: center !important;

        color: #94a3b8 !important;
    }

    @media (max-width: 1000px) {

        .stats-grid {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .detail-grid {
            grid-template-columns: 1fr;
        }

    }

    @media (max-width: 600px) {

        .stats-grid {
            grid-template-columns: 1fr;
        }

    }

</style>

@endpush