@extends('admin.layouts.app')
@section('admin-page', 'doctor-schedules-index')

@section(
    'title',
    'Lịch làm việc bác sĩ - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Lịch và đặt khám</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Lịch làm việc bác sĩ</strong>
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
            class="admin-btn admin-btn-secondary"
        >
            Lịch tháng
        </a>

        <a
            href="{{ route(
                'admin.doctor-schedules.create'
            ) }}"
            class="admin-btn admin-btn-primary"
        >Thêm lịch làm việc</a>

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


    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc lịch bác sĩ',
        'description' => 'Lọc theo từ khóa, bác sĩ, chuyên khoa, phòng khám, ngày làm việc và trạng thái.',
    ])
        <form
            method="GET"
            action="{{ route(
                'admin.doctor-schedules.index'
            ) }}"
            class="filter-form admin-filter-form"
        >

            <div class="filter-item admin-filter-field">

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
@error('keyword')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-item admin-filter-field">

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
@error('doctor_id')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-item admin-filter-field">

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
@error('specialty_id')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-item admin-filter-field">

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
@error('examination_room_id')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-item admin-filter-field">

                <label class="filter-label">
                    Ngày làm việc
                </label>

                <input
                    type="date"
                    name="work_date"
                    class="filter-control"
                    value="{{ $workDate }}"
                >
@error('work_date')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-item admin-filter-field">

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
                        Đang hoạt động
                    </option>

                    <option
                        value="INACTIVE"
                        @selected(
                            $status === 'INACTIVE'
                        )
                    >
                        Ngừng hoạt động
                    </option>

                </select>
@error('status')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-actions admin-filter-layout">





            </div>


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route(
                        'admin.doctor-schedules.index'
                    ), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách lịch làm việc bác sĩ'])



        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Ngày</th>
                        <th>Bác sĩ</th>
                        <th>Chuyên khoa</th>
                        <th>Phòng</th>
                        <th>Thời gian</th>
                        <th>Khung giờ</th>
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

                        @include('admin.layouts.partials.row-number', ['rows' => $doctorSchedules, 'index' => $loop->index])

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

                                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ACTIVE'])

                            @else

                                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'INACTIVE'])

                            @endif

                        </td>

                        <td><div class="admin-actions admin-table-actions">

                            <div class="action-group">

                                <a
                                    href="{{ route(
                                        'admin.doctor-schedules.show',
                                        $schedule
                                    ) }}"
                                    class="admin-btn admin-btn-detail"
                                >Chi tiết</a>

                                <a
                                    href="{{ route(
                                        'admin.doctor-schedules.edit',
                                        $schedule
                                    ) }}"
                                    class="admin-btn admin-btn-secondary"
                                >Sửa</a>

                                <form novalidate
                                    method="POST"
                                    action="{{ route(
                                        'admin.doctor-schedules.destroy',
                                        $schedule
                                    ) }}"
                                    onsubmit="
                                        return confirm(
                                            'Bạn có chắc muốn xóa lịch làm việc này? Lịch có khung giờ hoặc lịch hẹn sẽ không được phép xóa.'
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
