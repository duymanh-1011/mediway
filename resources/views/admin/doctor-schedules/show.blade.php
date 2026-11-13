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
    @php($standardShift = $doctorSchedule->isStandardShift())

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Lịch làm việc bác sĩ
            </h1>

            <p class="page-description">

                @include('admin.layouts.partials.record-code', ['code' => $doctorSchedule->doctor?->doctor_code])

                -

                {{ $doctorSchedule->doctor?->full_name }}

            </p>

        </div>

        <div class="header-actions">

            <a href="{{ route('admin.doctor-schedules.month.show', [$doctorSchedule->doctor_id, 'month' => $doctorSchedule->work_date->format('Y-m')]) }}" class="admin-btn admin-btn-secondary">← Quản lý lịch tháng</a>

            @unless ($standardShift)
            <a
                href="{{ route(
                    'admin.doctor-schedules.edit',
                    $doctorSchedule
                ) }}"
                class="admin-btn admin-btn-secondary"
            >Sửa</a>
            @endunless

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

        @unless ($standardShift)
        <div class="stat-card">

            <div class="stat-label">
                Tổng hạn mức cũ
            </div>

            <div class="stat-value">
                {{ $totalCapacity }}
            </div>

        </div>
        @endunless

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

                    @include('admin.layouts.partials.record-code', ['code' => $doctorSchedule->doctor?->doctor_code])

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

                    @include('admin.layouts.partials.record-code', ['code' => $doctorSchedule
                        ->examinationRoom
                        ?->room_code])

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

                    @if ($doctorSchedule->effectiveStatus() === 'ACTIVE')

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


        <div class="card admin-list-panel admin-list-panel--embedded">

            <div class="slot-header">

                <div>

                    <h3 class="section-title">
                        Khung giờ khám
                    </h3>

                    <p class="section-description">
                        {{ $standardShift ? 'Khung giờ được sinh tự động từ ca làm việc.' : 'Theo dõi khung giờ và lượt đăng ký của lịch cũ.' }}
                    </p>

                </div>

                @unless ($standardShift)
                <a
                    href="{{ route(
                        'admin.doctor-schedules.time-slots.create',
                        $doctorSchedule
                    ) }}"
                    class="admin-btn admin-btn-primary"
                >Thêm khung giờ</a>
                @endunless

            </div>


            <div class="table-wrapper admin-list-table-wrap">

                <table class="admin-table admin-list-table">

                    <thead>

                        <tr>
                            <th scope="col" class="admin-row-number">STT</th>
                            <th>Thời gian</th>
                            @unless ($standardShift)<th>Hạn mức cũ</th>@endunless
                            <th>Đã đặt</th>
                            <th>Trạng thái</th>
                            @unless ($standardShift)<th>Thao tác</th>@endunless
                        </tr>

                    </thead>

                    <tbody>

                    @forelse ($timeSlots as $slot)

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

                            @unless ($standardShift)<td>
                                {{ $slot->max_patients }}
                            </td>@endunless

                            <td>
                                {{ $slot->appointments_count }}
                            </td>

                            <td>

                                @if ($slot->effectiveStatus() === 'ACTIVE')

                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ACTIVE'])

                                @else

                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => 'INACTIVE'])

                                @endif

                            </td>

                            @unless ($standardShift)<td><div class="admin-actions admin-table-actions">

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
                                        data-confirm="Bạn có chắc muốn xóa khung giờ này? Khung giờ đã có lịch hẹn sẽ không thể xóa."
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

                            </div></td>@endunless

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="{{ $standardShift ? 4 : 6 }}"
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
