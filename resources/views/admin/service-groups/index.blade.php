@extends('admin.layouts.app')
@section('admin-page', 'service-groups-index')

@section(
    'title',
    'Quản lý nhóm dịch vụ - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Nhóm dịch vụ</strong>
@endsection

@section('content')
    @include('admin.layouts.partials.service-catalog-tabs')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Quản lý nhóm dịch vụ
            </h1>

            <p class="page-description">
                Quản lý các nhóm dùng để phân loại
                dịch vụ y tế trong hệ thống.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.service-groups.create'
            ) }}"
            class="admin-btn admin-btn-primary"
        >Thêm nhóm dịch vụ</a>

    </div>

    <div class="stats-grid">

        <div class="mini-card">

            <div class="mini-card-label">
                Tổng nhóm
            </div>

            <div class="mini-card-value">
                {{ $totalServiceGroups }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Đang hoạt động
            </div>

            <div class="mini-card-value success">
                {{ $activeServiceGroups }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Ngừng hoạt động
            </div>

            <div class="mini-card-value danger">
                {{ $inactiveServiceGroups }}
            </div>

        </div>

    </div>

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc nhóm dịch vụ',
        'description' => 'Lọc theo từ khóa và trạng thái.',
    ])
        <form
            method="GET"
            action="{{ route(
                'admin.service-groups.index'
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
                    placeholder="Mã hoặc tên nhóm dịch vụ..."
                >
@error('keyword')<div class="field-error">{{ $message }}</div>@enderror

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
                        'admin.service-groups.index'
                    ), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách nhóm dịch vụ'])


        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Mã nhóm</th>
                        <th>Tên nhóm</th>
                        <th>Số dịch vụ</th>
                        <th>Thứ tự hiển thị</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>

                </thead>

                <tbody>

                @forelse (
                    $serviceGroups
                    as $serviceGroup
                )

                    <tr>

                        @include('admin.layouts.partials.row-number', ['rows' => $serviceGroups, 'index' => $loop->index])

                        <td>
                            <strong>
                                {{ $serviceGroup
                                    ->service_group_code }}
                            </strong>
                        </td>

                        <td>
                            {{ $serviceGroup->name }}
                        </td>

                        <td>
                            {{ $serviceGroup
                                ->medical_services_count }}
                        </td>

                        <td>
                            {{ $serviceGroup
                                ->display_order }}
                        </td>

                        <td>

                            @if (
                                $serviceGroup->status
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
                                        'admin.service-groups.show',
                                        $serviceGroup
                                    ) }}"
                                    class="admin-btn admin-btn-detail"
                                >Chi tiết</a>

                                <a
                                    href="{{ route(
                                        'admin.service-groups.edit',
                                        $serviceGroup
                                    ) }}"
                                    class="admin-btn admin-btn-secondary"
                                >Sửa</a>

                                @include('admin.layouts.partials.catalog-status', ['record' => $serviceGroup, 'catalog' => 'service-groups'])

                            </div>

                        </div></td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="7"
                            class="empty-state"
                        >
                            Không tìm thấy nhóm dịch vụ phù hợp.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div class="pagination-wrapper">

            {{ $serviceGroups->links() }}

        </div>

    </div>

@endsection
