@extends('admin.layouts.app')
@section('admin-page', 'examination-rooms-index')

@section(
    'title',
    'Quản lý phòng khám - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Phòng khám</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Quản lý phòng khám
            </h1>

            <p class="page-description">
                Quản lý phòng khám sử dụng cho
                lịch bác sĩ, lịch hẹn và lượt khám.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.examination-rooms.create'
            ) }}"
            class="admin-btn admin-btn-primary"
        >Thêm phòng khám</a>

    </div>

    <div class="stats-grid">

        <div class="mini-card">

            <div class="mini-card-label">
                Tổng phòng khám
            </div>

            <div class="mini-card-value">
                {{ $totalRooms }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Đang hoạt động
            </div>

            <div class="mini-card-value success">
                {{ $activeRooms }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Ngừng hoạt động
            </div>

            <div class="mini-card-value danger">
                {{ $inactiveRooms }}
            </div>

        </div>

    </div>

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc phòng khám',
        'description' => 'Lọc theo từ khóa, tòa nhà, tầng và trạng thái.',
    ])
        <form
            method="GET"
            action="{{ route(
                'admin.examination-rooms.index'
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
                    placeholder="Mã, tên hoặc số phòng..."
                >
@error('keyword')<div class="field-error">{{ $message }}</div>@enderror

            </div>

            <div class="filter-item admin-filter-field">

                <label class="filter-label">
                    Tòa nhà
                </label>

                <select
                    name="building"
                    class="filter-control"
                >

                    <option value="">
                        Tất cả tòa nhà
                    </option>

                    @foreach ($buildings as $item)

                        <option
                            value="{{ $item }}"
                            @selected(
                                $building === $item
                            )
                        >
                            {{ $item }}
                        </option>

                    @endforeach

                </select>
@error('building')<div class="field-error">{{ $message }}</div>@enderror

            </div>

            <div class="filter-item admin-filter-field">

                <label class="filter-label">
                    Tầng
                </label>

                <select
                    name="floor"
                    class="filter-control"
                >

                    <option value="">
                        Tất cả tầng
                    </option>

                    @foreach ($floors as $item)

                        <option
                            value="{{ $item }}"
                            @selected(
                                $floor === $item
                            )
                        >
                            {{ $item }}
                        </option>

                    @endforeach

                </select>
@error('floor')<div class="field-error">{{ $message }}</div>@enderror

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
                        Tất cả trạng thái
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
                        'admin.examination-rooms.index'
                    ), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách phòng khám'])


        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Mã phòng</th>
                        <th>Tên phòng khám</th>
                        <th>Số phòng</th>
                        <th>Tầng</th>
                        <th>Tòa nhà</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>

                </thead>

                <tbody>

                @forelse (
                    $rooms
                    as $room
                )

                    <tr>

                        @include('admin.layouts.partials.row-number', ['rows' => $rooms, 'index' => $loop->index])

                        <td>
                            <strong>
                                @include('admin.layouts.partials.record-code', ['code' => $room->room_code])
                            </strong>
                        </td>

                        <td>
                            {{ $room->name }}
                        </td>

                        <td>
                            {{ $room->room_number }}
                        </td>

                        <td>
                            {{ $room->floor ?? '—' }}
                        </td>

                        <td>
                            {{ $room->building ?? '—' }}
                        </td>

                        <td>

                            @if (
                                $room->status
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
                                        'admin.examination-rooms.show',
                                        $room
                                    ) }}"
                                    class="admin-btn admin-btn-detail"
                                >Chi tiết</a>

                                <a
                                    href="{{ route(
                                        'admin.examination-rooms.edit',
                                        $room
                                    ) }}"
                                    class="admin-btn admin-btn-secondary"
                                >Sửa</a>

                                @include('admin.layouts.partials.catalog-status', ['record' => $room, 'catalog' => 'examination-rooms'])

                            </div>

                        </div></td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="8"
                            class="empty-state"
                        >
                            Không tìm thấy phòng khám phù hợp.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div class="pagination-wrapper">

            {{ $rooms->links() }}

        </div>

    </div>

@endsection
