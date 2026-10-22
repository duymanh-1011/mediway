@extends('admin.layouts.app')
@section('admin-page', 'examination-rooms-show')

@section(
    'title',
    'Chi tiết phòng khám - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Phòng khám</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">{{ $examinationRoom->room_code }}</strong>
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
                            Đang hoạt động
                        </span>

                    @else

                        <span class="badge badge-danger">
                            Ngừng hoạt động
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
                            <th scope="col" class="admin-row-number">STT</th>
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

                            @include('admin.layouts.partials.row-number', ['rows' => $recentSchedules, 'index' => $loop->index])
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
                                {{ \App\Models\AdminDisplay::label($schedule->status) }}
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
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
        class="admin-u-a26bda7d card"

    >

        <h3 class="section-title">
            Lượt khám gần đây
        </h3>

        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Mã lượt khám</th>
                        <th>Bệnh nhân</th>
                        <th>Bác sĩ</th>
                        <th>Tiếp nhận</th>
                        <th>Trạng thái</th>
                    </tr>

                </thead>

                <tbody>

                @forelse (
                    $recentVisits
                    as $visit
                )

                    <tr>

                        @include('admin.layouts.partials.row-number', ['rows' => $recentVisits, 'index' => $loop->index])
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
                            colspan="6"
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
