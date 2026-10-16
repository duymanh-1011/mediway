@extends('admin.layouts.app')

@section(
    'title',
    'Quản lý thuốc - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">
        Admin / Danh mục / Thuốc
    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Quản lý thuốc
            </h1>

            <p class="page-description">
                Quản lý danh mục thuốc sử dụng
                trong kê đơn của hệ thống Mediway.
            </p>

        </div>

        <a
            href="{{ route('admin.medicines.create') }}"
            class="btn btn-primary"
        >
            + Thêm thuốc
        </a>

    </div>

    <div class="stats-grid">

        <div class="mini-card">

            <div class="mini-card-label">
                Tổng thuốc
            </div>

            <div class="mini-card-value">
                {{ $totalMedicines }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Đang hoạt động
            </div>

            <div class="mini-card-value success">
                {{ $activeMedicines }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Ngừng hoạt động
            </div>

            <div class="mini-card-value danger">
                {{ $inactiveMedicines }}
            </div>

        </div>

    </div>

    <div class="card">

        <form
            method="GET"
            action="{{ route('admin.medicines.index') }}"
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
                    placeholder="Mã, tên, hoạt chất, nhà sản xuất..."
                >

            </div>

            <div class="filter-item">

                <label class="filter-label">
                    Dạng bào chế
                </label>

                <select
                    name="dosage_form"
                    class="filter-control"
                >

                    <option value="">
                        Tất cả dạng bào chế
                    </option>

                    @foreach ($dosageForms as $item)

                        <option
                            value="{{ $item }}"
                            @selected(
                                $dosageForm === $item
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
                    href="{{ route('admin.medicines.index') }}"
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
                        <th>Mã thuốc</th>
                        <th>Tên thuốc</th>
                        <th>Hoạt chất</th>
                        <th>Hàm lượng</th>
                        <th>Dạng</th>
                        <th>Đường dùng</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>

                </thead>

                <tbody>

                @forelse ($medicines as $medicine)

                    <tr>

                        <td>
                            {{ $medicines->firstItem()
                                + $loop->index }}
                        </td>

                        <td>
                            <strong>
                                {{ $medicine->medicine_code }}
                            </strong>
                        </td>

                        <td>
                            {{ $medicine->name }}
                        </td>

                        <td>
                            {{ $medicine
                                ->active_ingredient
                                ?? '—' }}
                        </td>

                        <td>
                            {{ $medicine->strength ?? '—' }}
                        </td>

                        <td>
                            {{ $medicine->dosage_form ?? '—' }}
                        </td>

                        <td>
                            {{ $medicine
                                ->route_of_administration
                                ?? '—' }}
                        </td>

                        <td>

                            @if (
                                $medicine->status
                                === 'ACTIVE'
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
                                        'admin.medicines.show',
                                        $medicine
                                    ) }}"
                                    class="action-link view"
                                >
                                    Chi tiết
                                </a>

                                <a
                                    href="{{ route(
                                        'admin.medicines.edit',
                                        $medicine
                                    ) }}"
                                    class="action-link edit"
                                >
                                    Sửa
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'admin.medicines.destroy',
                                        $medicine
                                    ) }}"
                                    onsubmit="
                                        return confirm(
                                            'Bạn có chắc muốn xóa thuốc này? Nếu thuốc đã được kê, hệ thống sẽ chuyển sang INACTIVE thay vì xóa.'
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
                            Không tìm thấy thuốc phù hợp.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div class="pagination-wrapper">
            {{ $medicines->links() }}
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
        font-size: 13px;
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
            minmax(260px, 1fr)
            200px
            170px
            auto;

        gap: 14px;
        align-items: end;
        margin-bottom: 20px;
    }

    .filter-label {
        display: block;
        margin-bottom: 6px;

        font-family:
            "Times New Roman",
            Times,
            serif;

        color: #475569;
        font-size: 13px;
        font-weight: 700;
    }

    .filter-control {
        width: 100%;
        padding: 10px 12px;

        font-family:
            "Times New Roman",
            Times,
            serif;

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

        font-family:
            "Times New Roman",
            Times,
            serif;

        font-size: 13px;
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