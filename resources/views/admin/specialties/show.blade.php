@extends('admin.layouts.app')
@section('admin-page', 'specialties-show')

@section(
    'title',
    'Chi tiết chuyên khoa - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Chuyên khoa</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">{{ $specialty->specialty_code }}</strong>
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
                            Đang hoạt động
                        </span>

                    @else

                        <span
                            class="badge badge-danger"
                        >
                            Ngừng hoạt động
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
                                <th scope="col" class="admin-row-number">STT</th>
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

    </div>

    <div
        class="admin-u-a26bda7d card"

    >

        <h3 class="section-title">
            Lịch hẹn gần đây
        </h3>

        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
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

                        @include('admin.layouts.partials.row-number', ['rows' => $recentAppointments, 'index' => $loop->index])
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
                            {{ \App\Models\AdminDisplay::label($appointment
                                ->appointment_status) }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="6"
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
