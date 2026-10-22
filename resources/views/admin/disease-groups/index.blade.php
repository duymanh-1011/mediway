@extends('admin.layouts.app')
@section('admin-page', 'disease-groups-index')

@section(
    'title',
    'Quản lý nhóm bệnh - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Nhóm bệnh</strong>
@endsection

@section('content')
    @include('admin.layouts.partials.disease-catalog-tabs')

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
            class="admin-btn btn btn-primary admin-btn-primary"
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

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc nhóm bệnh',
        'description' => 'Lọc theo từ khóa và trạng thái.',
    ])
        <form
            method="GET"
            action="{{ route(
                'admin.disease-groups.index'
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
                    placeholder="Mã hoặc tên nhóm bệnh..."
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
                        'admin.disease-groups.index'
                    ), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="card">

        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
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

                        @include('admin.layouts.partials.row-number', ['rows' => $diseaseGroups, 'index' => $loop->index])

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
                            <a href="{{ route('admin.disease-groups.show', $diseaseGroup) }}#group-diseases" aria-label="Xem bệnh thuộc nhóm {{ $diseaseGroup->name }}">{{ $diseaseGroup->diseases_count }}</a>
                        </td>

                        <td>

                            @if (
                                $diseaseGroup->status
                                === 'ACTIVE'
                            )

                                <span
                                    class="badge badge-success"
                                >
                                    Đang hoạt động
                                </span>

                            @else

                                <span
                                    class="badge badge-danger"
                                >
                                    Ngừng hoạt động
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
                                    class="admin-btn admin-btn-detail action-link view"
                                >Chi tiết</a>

                                <a
                                    href="{{ route(
                                        'admin.disease-groups.edit',
                                        $diseaseGroup
                                    ) }}"
                                    class="admin-btn admin-btn-secondary action-link edit"
                                >Sửa</a>

                                @include('admin.layouts.partials.catalog-status', ['record' => $diseaseGroup, 'catalog' => 'disease-groups'])

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
