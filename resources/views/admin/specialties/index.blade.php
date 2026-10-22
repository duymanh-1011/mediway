@extends('admin.layouts.app')
@section('admin-page', 'specialties-index')

@section(
    'title',
    'Quản lý chuyên khoa - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Chuyên khoa</strong>
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
            class="admin-btn btn btn-primary admin-btn-primary"
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

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc chuyên khoa',
        'description' => 'Lọc theo từ khóa và trạng thái.',
    ])
        <form
            method="GET"
            action="{{ route(
                'admin.specialties.index'
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
                    placeholder="Mã hoặc tên chuyên khoa..."
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
                        'admin.specialties.index'
                    ), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="card">

        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
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

                        @include('admin.layouts.partials.row-number', ['rows' => $specialties, 'index' => $loop->index])

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
                                    class="admin-btn admin-btn-detail action-link view"
                                >Chi tiết</a>

                                <a
                                    href="{{ route(
                                        'admin.specialties.edit',
                                        $specialty
                                    ) }}"
                                    class="admin-btn admin-btn-secondary action-link edit"
                                >Sửa</a>

                                @include('admin.layouts.partials.catalog-status', ['record' => $specialty, 'catalog' => 'specialties'])

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
