@extends('admin.layouts.app')

@section(
    'title',
    'Quản lý phòng khám - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">
        Admin / Danh mục / Phòng khám
    </div>

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
            class="btn btn-primary"
        >
            + Thêm phòng khám
        </a>

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

    <div class="card">

        <form
            method="GET"
            action="{{ route(
                'admin.examination-rooms.index'
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
                    placeholder="Mã, tên hoặc số phòng..."
                >

            </div>

            <div class="filter-item">

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

            </div>

            <div class="filter-item">

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
                        Tất cả trạng thái
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
                        'admin.examination-rooms.index'
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

                        <td>
                            {{ $rooms->firstItem()
                                + $loop->index }}
                        </td>

                        <td>
                            <strong>
                                {{ $room->room_code }}
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
                                        'admin.examination-rooms.show',
                                        $room
                                    ) }}"
                                    class="action-link view"
                                >
                                    Chi tiết
                                </a>

                                <a
                                    href="{{ route(
                                        'admin.examination-rooms.edit',
                                        $room
                                    ) }}"
                                    class="action-link edit"
                                >
                                    Sửa
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'admin.examination-rooms.destroy',
                                        $room
                                    ) }}"
                                    onsubmit="
                                        return confirm(
                                            'Bạn có chắc muốn xóa phòng khám này? Nếu phòng đã được sử dụng, hệ thống sẽ chuyển sang INACTIVE thay vì xóa.'
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

@push('styles')

<style>

    .page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;

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
        color: #64748b;

        font-size: 12px;

        margin-bottom: 7px;
    }

    .mini-card-value {
        font-size: 26px;
        font-weight: 700;
    }

    .mini-card-value.success {
        color: #059669;
    }

    .mini-card-value.danger {
        color: #dc2626;
    }

    .filter-form {
        display: grid;

        grid-template-columns:
            minmax(200px, 1fr)
            170px
            140px
            170px
            auto;

        gap: 12px;

        align-items: end;

        margin-bottom: 20px;
    }

    .filter-label {
        display: block;

        margin-bottom: 6px;

        color: #475569;

        font-size: 12px;
        font-weight: 700;
    }

    .filter-control {
        width: 100%;

        padding: 10px 12px;

        border: 1px solid #cbd5e1;
        border-radius: 8px;

        background: #ffffff;

        outline: none;
    }

    .filter-control:focus {
        border-color: #3b82f6;

        box-shadow:
            0 0 0 3px
            rgba(59, 130, 246, 0.1);
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

        font-family: inherit;
        font-size: 12px;
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

    @media (max-width: 1100px) {

        .filter-form {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

    }

    @media (max-width: 800px) {

        .stats-grid,
        .filter-form {
            grid-template-columns: 1fr;
        }

    }

</style>

@endpush