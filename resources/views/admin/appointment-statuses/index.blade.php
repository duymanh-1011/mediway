@extends('admin.layouts.app')

@section(
    'title',
    'Trạng thái lịch hẹn - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">
        Admin /
        Cấu hình trạng thái /
        Trạng thái lịch hẹn
    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Trạng thái lịch hẹn
            </h1>

            <p class="page-description">
                Quản lý cấu hình các trạng thái
                trong quy trình đặt và khám bệnh.
            </p>

        </div>

    </div>

    <div class="stats-grid">

        <div class="mini-card">

            <div class="mini-card-label">
                Tổng trạng thái
            </div>

            <div class="mini-card-value">
                {{ $totalStatuses }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Đang hoạt động
            </div>

            <div class="mini-card-value success">
                {{ $activeStatuses }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Trạng thái kết thúc
            </div>

            <div class="mini-card-value">
                {{ $terminalStatuses }}
            </div>

        </div>

    </div>

    <div class="card">

        <form
            method="GET"
            action="{{ route(
                'admin.appointment-statuses.index'
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
                    placeholder="Tên hoặc mô tả trạng thái..."
                >

            </div>

            <div class="filter-item">

                <label class="filter-label">
                    Hoạt động
                </label>

                <select
                    name="is_active"
                    class="filter-control"
                >

                    <option value="">
                        Tất cả
                    </option>

                    <option
                        value="1"
                        @selected(
                            $isActive === '1'
                        )
                    >
                        Đang hoạt động
                    </option>

                    <option
                        value="0"
                        @selected(
                            $isActive === '0'
                        )
                    >
                        Ngừng hoạt động
                    </option>

                </select>

            </div>

            <div class="filter-item">

                <label class="filter-label">
                    Chuyển tiếp
                </label>

                <select
                    name="allows_transition"
                    class="filter-control"
                >

                    <option value="">
                        Tất cả
                    </option>

                    <option
                        value="1"
                        @selected(
                            $allowsTransition === '1'
                        )
                    >
                        Cho phép chuyển tiếp
                    </option>

                    <option
                        value="0"
                        @selected(
                            $allowsTransition === '0'
                        )
                    >
                        Trạng thái kết thúc
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
                        'admin.appointment-statuses.index'
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
                        <th>Thứ tự</th>
                        <th>Tên trạng thái</th>
                        <th>Mô tả</th>
                        <th>Cho phép chuyển tiếp</th>
                        <th>Hoạt động</th>
                        <th>Thao tác</th>
                    </tr>

                </thead>

                <tbody>

                @forelse (
                    $appointmentStatuses
                    as $appointmentStatus
                )

                    <tr>

                        <td>
                            <span class="order-badge">
                                {{ $appointmentStatus
                                    ->process_order }}
                            </span>
                        </td>

                        <td>
                            <strong>
                                {{ $appointmentStatus->name }}
                            </strong>
                        </td>

                        <td class="description-cell">
                            {{ $appointmentStatus
                                ->description
                                ?? '—' }}
                        </td>

                        <td>

                            @if (
                                $appointmentStatus
                                    ->allows_transition
                            )

                                <span class="badge badge-success">
                                    Có
                                </span>

                            @else

                                <span class="badge badge-secondary">
                                    Không
                                </span>

                            @endif

                        </td>

                        <td>

                            @if (
                                $appointmentStatus
                                    ->is_active
                            )

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
                                        'admin.appointment-statuses.show',
                                        $appointmentStatus
                                    ) }}"
                                    class="action-link view"
                                >
                                    Chi tiết
                                </a>

                                <a
                                    href="{{ route(
                                        'admin.appointment-statuses.edit',
                                        $appointmentStatus
                                    ) }}"
                                    class="action-link edit"
                                >
                                    Sửa
                                </a>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="6"
                            class="empty-state"
                        >
                            Không tìm thấy trạng thái phù hợp.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div class="pagination-wrapper">
            {{ $appointmentStatuses->links() }}
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
        margin-bottom: 7px;

        color: #64748b;

        font-size: 13px;
    }

    .mini-card-value {
        font-size: 26px;
        font-weight: 700;
    }

    .mini-card-value.success {
        color: #059669;
    }

    .filter-form {
        display: grid;

        grid-template-columns:
            minmax(240px, 1fr)
            190px
            210px
            auto;

        gap: 14px;

        align-items: end;

        margin-bottom: 20px;
    }

    .filter-label {
        display: block;

        margin-bottom: 6px;

        color: #475569;

        font-family:
            "Times New Roman",
            Times,
            serif;

        font-size: 13px;
        font-weight: 700;
    }

    .filter-control {
        width: 100%;

        padding: 10px 12px;

        border: 1px solid #cbd5e1;
        border-radius: 8px;

        background: #ffffff;

        font-family:
            "Times New Roman",
            Times,
            serif;

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

    .order-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        width: 30px;
        height: 30px;

        border-radius: 50%;

        background: #eff6ff;
        color: #1d4ed8;

        font-weight: 700;
    }

    .description-cell {
        max-width: 360px;

        white-space: normal !important;

        line-height: 1.5;
    }

    .action-group {
        display: flex;
        align-items: center;

        gap: 7px;
    }

    .action-link {
        padding: 6px 9px;

        border-radius: 6px;

        text-decoration: none;

        font-family:
            "Times New Roman",
            Times,
            serif;

        font-size: 13px;
        font-weight: 600;
    }

    .action-link.view {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .action-link.edit {
        background: #fffbeb;
        color: #b45309;
    }

    .empty-state {
        padding: 40px !important;

        text-align: center !important;

        color: #94a3b8 !important;
    }

    .pagination-wrapper {
        margin-top: 18px;
    }

    @media (max-width: 1000px) {

        .filter-form {
            grid-template-columns: 1fr 1fr;
        }

    }

    @media (max-width: 700px) {

        .stats-grid,
        .filter-form {
            grid-template-columns: 1fr;
        }

    }

</style>

@endpush