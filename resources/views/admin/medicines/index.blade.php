@extends('admin.layouts.app')
@section('admin-page', 'medicines-index')

@section(
    'title',
    'Quản lý thuốc - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thuốc</strong>
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
            class="admin-btn admin-btn-primary"
        >Thêm thuốc</a>

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

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc thuốc',
        'description' => 'Lọc theo từ khóa, dạng bào chế và trạng thái.',
    ])
        <form
            method="GET"
            action="{{ route('admin.medicines.index') }}"
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
                    placeholder="Mã, tên, hoạt chất, nhà sản xuất..."
                >
@error('keyword')<div class="field-error">{{ $message }}</div>@enderror

            </div>

            <div class="filter-item admin-filter-field">

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
@error('dosage_form')<div class="field-error">{{ $message }}</div>@enderror

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


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.medicines.index'), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách thuốc'])


        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
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

                        @include('admin.layouts.partials.row-number', ['rows' => $medicines, 'index' => $loop->index])

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

                                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ACTIVE'])

                            @else

                                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'INACTIVE'])

                            @endif

                        </td>

                        <td><div class="admin-actions admin-table-actions">

                            <div class="action-group">

                                <a
                                    href="{{ route(
                                        'admin.medicines.show',
                                        $medicine
                                    ) }}"
                                    class="admin-btn admin-btn-detail"
                                >Chi tiết</a>

                                <a
                                    href="{{ route(
                                        'admin.medicines.edit',
                                        $medicine
                                    ) }}"
                                    class="admin-btn admin-btn-secondary"
                                >Sửa</a>

                                @include('admin.layouts.partials.catalog-status', ['record' => $medicine, 'catalog' => 'medicines'])

                            </div>

                        </div></td>

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
