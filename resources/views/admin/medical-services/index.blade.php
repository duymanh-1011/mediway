@extends('admin.layouts.app')

@section(
    'title',
    'Quản lý dịch vụ y tế - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">
        Admin / Danh mục / Dịch vụ y tế
    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Quản lý dịch vụ y tế
            </h1>

            <p class="page-description">
                Quản lý danh mục dịch vụ,
                giá và thông tin thực hiện.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.medical-services.create'
            ) }}"
            class="btn btn-primary"
        >
            + Thêm dịch vụ
        </a>

    </div>

    <div class="stats-grid">

        <div class="mini-card">

            <div class="mini-card-label">
                Tổng dịch vụ
            </div>

            <div class="mini-card-value">
                {{ $totalServices }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Đang hoạt động
            </div>

            <div class="mini-card-value success">
                {{ $activeServices }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Ngừng hoạt động
            </div>

            <div class="mini-card-value danger">
                {{ $inactiveServices }}
            </div>

        </div>

    </div>

    <div class="card">

        <form
            method="GET"
            action="{{ route(
                'admin.medical-services.index'
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
                    placeholder="Mã hoặc tên dịch vụ..."
                >

            </div>

            <div class="filter-item">

                <label class="filter-label">
                    Nhóm dịch vụ
                </label>

                <select
                    name="service_group_id"
                    class="filter-control"
                >

                    <option value="">
                        Tất cả nhóm
                    </option>

                    @foreach ($serviceGroups as $group)

                        <option
                            value="{{ $group->id }}"
                            @selected(
                                (string) $serviceGroupId
                                ===
                                (string) $group->id
                            )
                        >
                            {{ $group->name }}
                        </option>

                    @endforeach

                </select>

            </div>

            <div class="filter-item">

                <label class="filter-label">
                    Giá từ
                </label>

                <input
                    type="number"
                    name="min_price"
                    class="filter-control"
                    value="{{ $minPrice }}"
                    min="0"
                    placeholder="0"
                >

            </div>

            <div class="filter-item">

                <label class="filter-label">
                    Giá đến
                </label>

                <input
                    type="number"
                    name="max_price"
                    class="filter-control"
                    value="{{ $maxPrice }}"
                    min="0"
                    placeholder="..."
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
                        'admin.medical-services.index'
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
                        <th>Mã dịch vụ</th>
                        <th>Tên dịch vụ</th>
                        <th>Nhóm</th>
                        <th>Giá</th>
                        <th>Đơn vị</th>
                        <th>Thời lượng</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>

                </thead>

                <tbody>

                @forelse (
                    $medicalServices
                    as $service
                )

                    <tr>

                        <td>
                            {{ $medicalServices->firstItem()
                                + $loop->index }}
                        </td>

                        <td>
                            <strong>
                                {{ $service->service_code }}
                            </strong>
                        </td>

                        <td>
                            {{ $service->name }}
                        </td>

                        <td>
                            {{ $service->serviceGroup?->name ?? '—' }}
                        </td>

                        <td>
                            {{
                                number_format(
                                    $service->price,
                                    0,
                                    ',',
                                    '.'
                                )
                            }}
                            đ
                        </td>

                        <td>
                            {{ $service->unit }}
                        </td>

                        <td>

                            @if (
                                $service
                                    ->estimated_duration_minutes
                            )

                                {{ $service
                                    ->estimated_duration_minutes }}
                                phút

                            @else
                                —
                            @endif

                        </td>

                        <td>

                            @if (
                                $service->status
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
                                        'admin.medical-services.show',
                                        $service
                                    ) }}"
                                    class="action-link view"
                                >
                                    Chi tiết
                                </a>

                                <a
                                    href="{{ route(
                                        'admin.medical-services.edit',
                                        $service
                                    ) }}"
                                    class="action-link edit"
                                >
                                    Sửa
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'admin.medical-services.destroy',
                                        $service
                                    ) }}"
                                    onsubmit="
                                        return confirm(
                                            'Bạn có chắc muốn xóa dịch vụ này? Nếu dịch vụ đã được chỉ định, hệ thống sẽ chuyển sang INACTIVE thay vì xóa.'
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
                            colspan="9"
                            class="empty-state"
                        >
                            Không tìm thấy dịch vụ phù hợp.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div class="pagination-wrapper">

            {{ $medicalServices->links() }}

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
            180px
            130px
            130px
            160px
            auto;
        gap: 10px;
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

    @media (max-width: 1200px) {

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