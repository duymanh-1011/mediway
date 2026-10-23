@extends('admin.layouts.app')
@section('admin-page', 'doctor-schedules-show')

@section(
    'title',
    'Chi tiết lịch làm việc - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Lịch và đặt khám</span>
    <span aria-hidden="true">/</span>
    <span>Lịch làm việc bác sĩ</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">#{{ $doctorSchedule->id }}</strong>
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
                class="admin-btn admin-btn-secondary"
            >← Quay lại</a>

            <a
                href="{{ route(
                    'admin.doctor-schedules.edit',
                    $doctorSchedule
                ) }}"
                class="admin-btn admin-btn-secondary"
            >Sửa</a>

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

                        @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ACTIVE'])

                    @else

                        @include('admin.layouts.partials.status-badge', ['badgeCode' => 'INACTIVE'])

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
                        Quản lý khung giờ và sức chứa của lịch làm việc.
                    </p>

                </div>

                <a
                    href="{{ route(
                        'admin.doctor-schedules.time-slots.create',
                        $doctorSchedule
                    ) }}"
                    class="admin-btn admin-btn-primary"
                >Thêm khung giờ</a>

            </div>


            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>

                        <tr>
                            <th scope="col" class="admin-row-number">STT</th>
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

                            @include('admin.layouts.partials.row-number', ['rows' => $timeSlots, 'index' => $loop->index])
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

                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ACTIVE'])

                                @else

                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => 'INACTIVE'])

                                @endif

                            </td>

                            <td><div class="admin-actions admin-table-actions">

                                <div class="action-group">

                                    <a
                                        href="{{ route(
                                            'admin.appointment-time-slots.edit',
                                            $slot->id
                                        ) }}"
                                        class="admin-btn admin-btn-secondary"
                                    >Sửa</a>

                                    <form novalidate
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
                                            class="admin-btn admin-btn-danger"
                                        >
                                            Xóa
                                        </button>

                                    </form>

                                </div>

                            </div></td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
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
