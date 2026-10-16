@extends('admin.layouts.app')

@section(
    'title',
    'Quản lý chuyên khoa - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">
        Admin / Danh mục / Chuyên khoa
    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Quản lý chuyên khoa
            </h1>

            <p class="page-description">
                Quản lý danh mục chuyên khoa phục vụ
                lịch làm việc, đặt lịch và khám bệnh.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.specialties.create'
            ) }}"
            class="btn btn-primary"
        >
            + Thêm chuyên khoa
        </a>

    </div>

    <div class="stats-grid">

        <div class="mini-card">

            <div class="mini-card-label">
                Tổng chuyên khoa
            </div>

            <div class="mini-card-value">
                {{ $totalSpecialties }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Đang hoạt động
            </div>

            <div class="mini-card-value success">
                {{ $activeSpecialties }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Ngừng hoạt động
            </div>

            <div class="mini-card-value danger">
                {{ $inactiveSpecialties }}
            </div>

        </div>

    </div>

    <div class="card">

        <form
            method="GET"
            action="{{ route(
                'admin.specialties.index'
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
                    placeholder="Mã hoặc tên chuyên khoa..."
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
                        'admin.specialties.index'
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
                        <th>Ảnh</th>
                        <th>Mã chuyên khoa</th>
                        <th>Tên chuyên khoa</th>
                        <th>Hướng dẫn trước khám</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>

                </thead>

                <tbody>

                @forelse (
                    $specialties
                    as $specialty
                )

                    <tr>

                        <td>
                            {{ $specialties->firstItem()
                                + $loop->index }}
                        </td>

                        <td>

                            @if ($specialty->image_path)

                                <img
                                    src="{{ asset(
                                        'storage/'
                                        . $specialty->image_path
                                    ) }}"
                                    alt="{{ $specialty->name }}"
                                    class="table-image"
                                >

                            @else

                                <div class="no-image">
                                    —
                                </div>

                            @endif

                        </td>

                        <td>
                            <strong>
                                {{ $specialty->specialty_code }}
                            </strong>
                        </td>

                        <td>
                            {{ $specialty->name }}
                        </td>

                        <td class="instruction-cell">

                            {{ $specialty
                                ->pre_visit_instructions
                                ?? '—' }}

                        </td>

                        <td>

                            @if (
                                $specialty->status
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
                            {{ $specialty
                                ->created_at
                                ?->format('d/m/Y') }}
                        </td>

                        <td>

                            <div class="action-group">

                                <a
                                    href="{{ route(
                                        'admin.specialties.show',
                                        $specialty
                                    ) }}"
                                    class="action-link view"
                                >
                                    Chi tiết
                                </a>

                                <a
                                    href="{{ route(
                                        'admin.specialties.edit',
                                        $specialty
                                    ) }}"
                                    class="action-link edit"
                                >
                                    Sửa
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'admin.specialties.destroy',
                                        $specialty
                                    ) }}"
                                    onsubmit="
                                        return confirm(
                                            'Bạn có chắc muốn xóa chuyên khoa này? Nếu chuyên khoa đã được sử dụng, hệ thống sẽ chuyển sang INACTIVE thay vì xóa.'
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
                            Không tìm thấy chuyên khoa phù hợp.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div class="pagination-wrapper">

            {{ $specialties->links() }}

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

        background: #fff;

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

        background: #fff;

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

    .table-image {
        width: 48px;
        height: 48px;

        object-fit: cover;

        border-radius: 8px;

        border: 1px solid #e2e8f0;
    }

    .no-image {
        width: 48px;
        height: 48px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 8px;

        background: #f8fafc;

        color: #94a3b8;
    }

    .instruction-cell {
        max-width: 260px;

        white-space: normal !important;

        line-height: 1.45;
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

    @media (max-width: 900px) {

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .filter-form {
            grid-template-columns: 1fr;
        }

    }

</style>

@endpush