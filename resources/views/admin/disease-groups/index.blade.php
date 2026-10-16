@extends('admin.layouts.app')

@section(
    'title',
    'Quản lý nhóm bệnh - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">
        Admin / Danh mục / Nhóm bệnh
    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Quản lý nhóm bệnh
            </h1>

            <p class="page-description">
                Quản lý các nhóm phân loại
                bệnh trong hệ thống Mediway.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.disease-groups.create'
            ) }}"
            class="btn btn-primary"
        >
            + Thêm nhóm bệnh
        </a>

    </div>

    <div class="stats-grid">

        <div class="mini-card">

            <div class="mini-card-label">
                Tổng nhóm bệnh
            </div>

            <div class="mini-card-value">
                {{ $totalDiseaseGroups }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Đang hoạt động
            </div>

            <div class="mini-card-value success">
                {{ $activeDiseaseGroups }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Ngừng hoạt động
            </div>

            <div class="mini-card-value danger">
                {{ $inactiveDiseaseGroups }}
            </div>

        </div>

    </div>

    <div class="card">

        <form
            method="GET"
            action="{{ route(
                'admin.disease-groups.index'
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
                    placeholder="Mã hoặc tên nhóm bệnh..."
                >

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
                        'admin.disease-groups.index'
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
                        <th>Mã nhóm</th>
                        <th>Tên nhóm bệnh</th>
                        <th>Số bệnh</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>

                </thead>

                <tbody>

                @forelse (
                    $diseaseGroups
                    as $diseaseGroup
                )

                    <tr>

                        <td>
                            {{ $diseaseGroups->firstItem()
                                + $loop->index }}
                        </td>

                        <td>
                            <strong>
                                {{ $diseaseGroup
                                    ->disease_group_code }}
                            </strong>
                        </td>

                        <td>
                            {{ $diseaseGroup->name }}
                        </td>

                        <td>
                            {{ $diseaseGroup
                                ->diseases_count }}
                        </td>

                        <td>

                            @if (
                                $diseaseGroup->status
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
                            {{ $diseaseGroup
                                ->created_at
                                ?->format('d/m/Y') }}
                        </td>

                        <td>

                            <div class="action-group">

                                <a
                                    href="{{ route(
                                        'admin.disease-groups.show',
                                        $diseaseGroup
                                    ) }}"
                                    class="action-link view"
                                >
                                    Chi tiết
                                </a>

                                <a
                                    href="{{ route(
                                        'admin.disease-groups.edit',
                                        $diseaseGroup
                                    ) }}"
                                    class="action-link edit"
                                >
                                    Sửa
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'admin.disease-groups.destroy',
                                        $diseaseGroup
                                    ) }}"
                                    onsubmit="
                                        return confirm(
                                            'Bạn có chắc muốn xóa nhóm bệnh này? Nếu nhóm đang chứa bệnh, hệ thống sẽ chuyển sang INACTIVE thay vì xóa.'
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
                            colspan="7"
                            class="empty-state"
                        >
                            Không tìm thấy nhóm bệnh phù hợp.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div class="pagination-wrapper">
            {{ $diseaseGroups->links() }}
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
        margin-bottom: 7px;

        color: #64748b;

        font-size: 12px;
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
            minmax(220px, 1fr)
            220px
            auto;

        gap: 14px;

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

    @media (max-width: 800px) {

        .stats-grid,
        .filter-form {
            grid-template-columns: 1fr;
        }

    }

</style>

@endpush