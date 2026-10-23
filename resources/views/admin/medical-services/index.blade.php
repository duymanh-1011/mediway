@extends('admin.layouts.app')
@section('admin-page', 'medical-services-index')

@section(
    'title',
    'Quản lý dịch vụ y tế - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Dịch vụ y tế</strong>
@endsection

@section('content')
    @include('admin.layouts.partials.service-catalog-tabs')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Dịch vụ y tế
            </h1>

            <p class="page-description">
                Quản lý dịch vụ, nhóm dịch vụ,
                mức giá và thông tin thực hiện.
            </p>

        </div>

        <a
            href="{{ route(
                'admin.medical-services.create'
            ) }}"
            class="admin-btn admin-btn-primary"
        >Thêm dịch vụ</a>

    </div>


    {{-- =====================================================
         THỐNG KÊ
    ====================================================== --}}

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


    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc dịch vụ y tế',
        'description' => 'Lọc theo từ khóa, nhóm dịch vụ, giá từ, giá đến và trạng thái.',
    ])
        <form
            method="GET"
            action="{{ route(
                'admin.medical-services.index'
            ) }}"
            class="filter-form admin-filter-form"
        >

            <div class="filter-item admin-filter-field">

                <label
                    for="keyword"
                    class="filter-label"
                >
                    Từ khóa
                </label>

                <input
                    type="text"
                    id="keyword"
                    name="keyword"
                    class="filter-control"
                    value="{{ $keyword }}"
                    placeholder="Mã hoặc tên dịch vụ"
                >
@error('keyword')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-item admin-filter-field">

                <label
                    for="group_id"
                    class="filter-label"
                >
                    Nhóm dịch vụ
                </label>

                <select
                    id="group_id"
                    name="group_id"
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
@error('group_id')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-item admin-filter-field">

                <label
                    for="min_price"
                    class="filter-label"
                >
                    Giá từ
                </label>

                <input
                    type="number"
                    id="min_price"
                    name="min_price"
                    class="filter-control"
                    value="{{ $minPrice }}"
                    min="0"
                    placeholder="0"
                >
@error('min_price')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-item admin-filter-field">

                <label
                    for="max_price"
                    class="filter-label"
                >
                    Giá đến
                </label>

                <input
                    type="number"
                    id="max_price"
                    name="max_price"
                    class="filter-control"
                    value="{{ $maxPrice }}"
                    min="0"
                    placeholder="Không giới hạn"
                >
@error('max_price')<div class="field-error">{{ $message }}</div>@enderror

            </div>


            <div class="filter-item admin-filter-field">

                <label
                    for="status"
                    class="filter-label"
                >
                    Trạng thái
                </label>

                <select
                    id="status"
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
                        'admin.medical-services.index'
                    ), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách dịch vụ y tế'])



        {{-- =====================================================
             DANH SÁCH
        ====================================================== --}}

        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Mã dịch vụ</th>
                        <th>Tên dịch vụ</th>
                        <th>Nhóm dịch vụ</th>
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

                        @include('admin.layouts.partials.row-number', ['rows' => $medicalServices, 'index' => $loop->index])


                        <td>
                            <strong class="service-code">
                                {{ $service->service_code }}
                            </strong>
                        </td>


                        <td>
                            <strong class="service-name">
                                {{ $service->name }}
                            </strong>
                        </td>


                        {{-- Nhiều nhóm dịch vụ --}}
                        <td>

                            <div class="group-badges">

                                @forelse (
                                    $service->serviceGroups
                                    as $group
                                )

                                    <span class="group-badge">
                                        {{ $group->name }}
                                    </span>

                                @empty

                                    <span class="text-muted">
                                        Chưa phân nhóm
                                    </span>

                                @endforelse

                            </div>

                        </td>


                        <td>
                            <strong class="price-text">
                                {{
                                    number_format(
                                        (float) $service->price,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                                đ
                            </strong>
                        </td>


                        <td>
                            {{ $service->unit ?: '—' }}
                        </td>


                        <td>

                            @if (
                                $service
                                    ->estimated_duration_minutes
                            )

                                {{
                                    $service
                                        ->estimated_duration_minutes
                                }}
                                phút

                            @else

                                —

                            @endif

                        </td>


                        <td>

                            @if (
                                $service->status
                                ===
                                'ACTIVE'
                            )

                                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ACTIVE'])

                            @else

                                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'INACTIVE'])

                            @endif

                        </td>


                        <td><div class="admin-actions admin-table-actions">

                            <div class="action-group">
                                @include('admin.layouts.partials.catalog-status', ['record' => $service, 'catalog' => 'medical-services'])

                                <a
                                    href="{{ route(
                                        'admin.medical-services.show',
                                        $service
                                    ) }}"
                                    class="admin-btn admin-btn-detail"
                                >Chi tiết</a>

                                <a
                                    href="{{ route(
                                        'admin.medical-services.edit',
                                        $service
                                    ) }}"
                                    class="admin-btn admin-btn-secondary"
                                >Sửa</a>

                            </div>

                        </div></td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="9"
                            class="empty-state"
                        >
                            Không có dịch vụ phù hợp
                            với bộ lọc hiện tại.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        {{-- =====================================================
             PHÂN TRANG
        ====================================================== --}}

        @if ($medicalServices->hasPages())

            <div class="pagination-wrapper">

                {{ $medicalServices->links() }}

            </div>

        @endif

    </div>

@endsection
