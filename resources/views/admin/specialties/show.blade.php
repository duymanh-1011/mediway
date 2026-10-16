@extends('admin.layouts.app')

@section(
    'title',
    'Chi tiết chuyên khoa - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">

        Admin /
        Danh mục /
        Chuyên khoa /
        {{ $specialty->specialty_code }}

    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                {{ $specialty->name }}
            </h1>

            <p class="page-description">
                Mã chuyên khoa:
                {{ $specialty->specialty_code }}
            </p>

        </div>

        <div class="header-actions">

            <a
                href="{{ route(
                    'admin.specialties.index'
                ) }}"
                class="btn btn-secondary"
            >
                ← Danh sách
            </a>

            <a
                href="{{ route(
                    'admin.specialties.edit',
                    $specialty
                ) }}"
                class="btn btn-primary"
            >
                Sửa chuyên khoa
            </a>

        </div>

    </div>

    <div class="detail-grid">

        <div class="card">

            <h3 class="section-title">
                Thông tin chuyên khoa
            </h3>

            @if ($specialty->image_path)

                <img
                    src="{{ asset(
                        'storage/'
                        . $specialty->image_path
                    ) }}"
                    alt="{{ $specialty->name }}"
                    class="detail-image"
                >

            @endif

            <div class="detail-row">

                <div class="detail-label">
                    Mã chuyên khoa
                </div>

                <div class="detail-value">
                    {{ $specialty->specialty_code }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Tên chuyên khoa
                </div>

                <div class="detail-value">
                    {{ $specialty->name }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Trạng thái
                </div>

                <div class="detail-value">

                    @if (
                        $specialty->status
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

                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Mô tả
                </div>

                <div class="detail-value">
                    {{ $specialty->description ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Hướng dẫn trước khám
                </div>

                <div class="detail-value">
                    {{ $specialty
                        ->pre_visit_instructions
                        ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Ghi chú
                </div>

                <div class="detail-value">
                    {{ $specialty->notes ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Ngày tạo
                </div>

                <div class="detail-value">
                    {{ $specialty
                        ->created_at
                        ?->format(
                            'd/m/Y H:i'
                        ) }}
                </div>

            </div>

        </div>

        <div>

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
                                <th>Phòng</th>
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
                                        {{ $schedule->full_name }}
                                    </small>

                                </td>

                                <td>

                                    {{ $schedule->room_code }}

                                    <br>

                                    <small>
                                        {{ $schedule->room_name }}
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

    </div>

    <div
        class="card"
        style="margin-top:20px;"
    >

        <h3 class="section-title">
            Lịch hẹn gần đây
        </h3>

        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th>Mã lịch hẹn</th>
                        <th>Ngày khám</th>
                        <th>Bệnh nhân</th>
                        <th>Bác sĩ</th>
                        <th>Trạng thái</th>
                    </tr>

                </thead>

                <tbody>

                @forelse (
                    $recentAppointments
                    as $appointment
                )

                    <tr>

                        <td>
                            <strong>
                                {{ $appointment
                                    ->appointment_code }}
                            </strong>
                        </td>

                        <td>
                            {{
                                \Carbon\Carbon::parse(
                                    $appointment
                                        ->appointment_date
                                )->format('d/m/Y')
                            }}
                        </td>

                        <td>

                            {{ $appointment
                                ->patient_code }}

                            <br>

                            <small>
                                {{ $appointment
                                    ->patient_name }}
                            </small>

                        </td>

                        <td>

                            {{ $appointment
                                ->doctor_code }}

                            <br>

                            <small>
                                {{ $appointment
                                    ->doctor_name }}
                            </small>

                        </td>

                        <td>
                            {{ $appointment
                                ->appointment_status }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="5"
                            class="empty-state"
                        >
                            Chưa có lịch hẹn thuộc chuyên khoa này.
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

    .detail-image {
        width: 100%;
        max-height: 220px;

        object-fit: cover;

        border-radius: 10px;

        margin-bottom: 18px;
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

    .stats-grid {
        display: grid;

        grid-template-columns:
            repeat(3, minmax(0, 1fr));

        gap: 12px;

        margin-bottom: 20px;
    }

    .stat-card {
        padding: 16px;

        background: #fff;

        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }

    .stat-label {
        color: #64748b;

        font-size: 12px;

        margin-bottom: 6px;
    }

    .stat-value {
        font-size: 25px;
        font-weight: 700;
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

</style>

@endpush