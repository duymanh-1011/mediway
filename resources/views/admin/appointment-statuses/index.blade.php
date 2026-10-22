@extends('admin.layouts.app')
@section('admin-page', 'appointment-statuses-index')

@section(
    'title',
    'Trạng thái lịch hẹn - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Cấu hình trạng thái</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Trạng thái lịch hẹn</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Trạng thái lịch hẹn
            </h1>

            <p class="page-description">
                Quản lý cấu hình các trạng thái
                trong quy trình đặt và khám bệnh.
            </p>

        </div>

    </div>

    <div class="stats-grid">

        <div class="mini-card">

            <div class="mini-card-label">
                Tổng trạng thái
            </div>

            <div class="mini-card-value">
                {{ $totalStatuses }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Đang hoạt động
            </div>

            <div class="mini-card-value success">
                {{ $activeStatuses }}
            </div>

        </div>

        <div class="mini-card">

            <div class="mini-card-label">
                Trạng thái kết thúc
            </div>

            <div class="mini-card-value">
                {{ $terminalStatuses }}
            </div>

        </div>

    </div>

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc trạng thái lịch hẹn',
        'description' => 'Lọc theo từ khóa, hoạt động và chuyển tiếp.',
    ])
        <form
            method="GET"
            action="{{ route(
                'admin.appointment-statuses.index'
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
                    placeholder="Tên hoặc mô tả trạng thái..."
                >
@error('keyword')<div class="field-error">{{ $message }}</div>@enderror

            </div>

            <div class="filter-item admin-filter-field">

                <label class="filter-label">
                    Hoạt động
                </label>

                <select
                    name="is_active"
                    class="filter-control"
                >

                    <option value="">
                        Tất cả
                    </option>

                    <option
                        value="1"
                        @selected(
                            $isActive === '1'
                        )
                    >
                        Đang hoạt động
                    </option>

                    <option
                        value="0"
                        @selected(
                            $isActive === '0'
                        )
                    >
                        Ngừng hoạt động
                    </option>

                </select>
@error('is_active')<div class="field-error">{{ $message }}</div>@enderror

            </div>

            <div class="filter-item admin-filter-field">

                <label class="filter-label">
                    Chuyển tiếp
                </label>

                <select
                    name="allows_transition"
                    class="filter-control"
                >

                    <option value="">
                        Tất cả
                    </option>

                    <option
                        value="1"
                        @selected(
                            $allowsTransition === '1'
                        )
                    >
                        Cho phép chuyển tiếp
                    </option>

                    <option
                        value="0"
                        @selected(
                            $allowsTransition === '0'
                        )
                    >
                        Trạng thái kết thúc
                    </option>

                </select>
@error('allows_transition')<div class="field-error">{{ $message }}</div>@enderror

            </div>

            <div class="filter-actions admin-filter-layout">





            </div>


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route(
                        'admin.appointment-statuses.index'
                    ), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="card">

        <div class="table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Thứ tự xử lý</th>
                        <th>Tên trạng thái</th>
                        <th>Mô tả</th>
                        <th>Cho phép chuyển tiếp</th>
                        <th>Hoạt động</th>
                        <th>Thao tác</th>
                    </tr>

                </thead>

                <tbody>

                @forelse (
                    $appointmentStatuses
                    as $appointmentStatus
                )

                    <tr>

                        @include('admin.layouts.partials.row-number', ['rows' => $appointmentStatuses, 'index' => $loop->index])
                        <td>
                            <span class="order-badge">
                                {{ $appointmentStatus
                                    ->process_order }}
                            </span>
                        </td>

                        <td>
                            <strong>
                                {{ \App\Models\AdminDisplay::label($appointmentStatus->name) }}
                            </strong>
                        </td>

                        <td class="description-cell">
                            {{ \App\Models\AdminDisplay::text($appointmentStatus->description ?? '—') }}
                        </td>

                        <td>

                            @if (
                                $appointmentStatus
                                    ->allows_transition
                            )

                                <span class="badge badge-success">
                                    Có
                                </span>

                            @else

                                <span class="badge badge-secondary">
                                    Không
                                </span>

                            @endif

                        </td>

                        <td>

                            @if (
                                $appointmentStatus
                                    ->is_active
                            )

                                <span class="badge badge-success">
                                    Đang hoạt động
                                </span>

                            @else

                                <span class="badge badge-danger">
                                    Ngừng hoạt động
                                </span>

                            @endif

                        </td>

                        <td>

                            <div class="action-group">

                                <a
                                    href="{{ route(
                                        'admin.appointment-statuses.show',
                                        $appointmentStatus
                                    ) }}"
                                    class="admin-btn admin-btn-detail action-link view"
                                >Chi tiết</a>

                                <a
                                    href="{{ route(
                                        'admin.appointment-statuses.edit',
                                        $appointmentStatus
                                    ) }}"
                                    class="admin-btn admin-btn-secondary action-link edit"
                                >Sửa</a>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="7"
                            class="empty-state"
                        >
                            Không tìm thấy trạng thái phù hợp.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div class="pagination-wrapper">
            {{ $appointmentStatuses->links() }}
        </div>

    </div>

@endsection
