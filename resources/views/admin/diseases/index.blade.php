@extends('admin.layouts.app')
@section('admin-page', 'diseases-index')

@section(
    'title',
    'Danh mục bệnh - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Danh mục bệnh</strong>
@endsection

@section('content')
    @include('admin.layouts.partials.disease-catalog-tabs')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Danh mục bệnh
            </h1>

            <p class="page-description">
                Quản lý danh mục bệnh phục vụ
                chẩn đoán trong hệ thống Mediway.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.diseases.create'
            ) }}"
            class="admin-btn admin-btn-primary"
        >Thêm bệnh</a>

    </div>

    <div class="stats-grid">

        <div class="mini-card">

            <div class="mini-card-label">
                Tổng bệnh
            </div>

            <div class="mini-card-value">
                {{ $totalDiseases }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Đang hoạt động
            </div>

            <div class="mini-card-value success">
                {{ $activeDiseases }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Ngừng hoạt động
            </div>

            <div class="mini-card-value danger">
                {{ $inactiveDiseases }}
            </div>

        </div>

    </div>

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc bệnh',
        'description' => 'Lọc theo từ khóa, nhóm bệnh và trạng thái.',
    ])
        <form
            method="GET"
            action="{{ route(
                'admin.diseases.index'
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
                    placeholder="Mã, tên hoặc tên thay thế..."
                >
@error('keyword')<div class="field-error">{{ $message }}</div>@enderror

            </div>

            <div class="filter-item admin-filter-field">

                <label class="filter-label">
                    Nhóm bệnh
                </label>

                <select
                    name="disease_group_id"
                    class="filter-control"
                >

                    <option value="">
                        Tất cả nhóm bệnh
                    </option>

                    @foreach ($diseaseGroups as $group)

                        <option
                            value="{{ $group->id }}"
                            @selected(
                                (string) $diseaseGroupId
                                ===
                                (string) $group->id
                            )
                        >
                            {{ $group->name }}
                        </option>

                    @endforeach

                </select>
@error('disease_group_id')<div class="field-error">{{ $message }}</div>@enderror

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
                        'admin.diseases.index'
                    ), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách bệnh'])


        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Mã bệnh</th>
                        <th>Tên bệnh</th>
                        <th>Tên khác</th>
                        <th>Nhóm bệnh</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>

                </thead>

                <tbody>

                @forelse (
                    $diseases
                    as $disease
                )

                    <tr>

                        @include('admin.layouts.partials.row-number', ['rows' => $diseases, 'index' => $loop->index])

                        <td>
                            <strong>
                                @include('admin.layouts.partials.record-code', ['code' => $disease->disease_code])
                            </strong>
                        </td>

                        <td>
                            {{ $disease->name }}
                        </td>

                        <td>
                            {{ $disease
                                ->alternative_name
                                ?? '—' }}
                        </td>

                        <td>
                            {{ $disease
                                ->diseaseGroup
                                ?->name
                                ?? '—' }}
                        </td>

                        <td>

                            @if (
                                $disease->status
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
                                        'admin.diseases.show',
                                        $disease
                                    ) }}"
                                    class="admin-btn admin-btn-detail"
                                >Chi tiết</a>

                                <a
                                    href="{{ route(
                                        'admin.diseases.edit',
                                        $disease
                                    ) }}"
                                    class="admin-btn admin-btn-secondary"
                                >Sửa</a>

                                @include('admin.layouts.partials.catalog-status', ['record' => $disease, 'catalog' => 'diseases'])

                            </div>

                        </div></td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="7"
                            class="empty-state"
                        >
                            Không tìm thấy bệnh phù hợp.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div class="pagination-wrapper">
            {{ $diseases->links() }}
        </div>

    </div>

@endsection
