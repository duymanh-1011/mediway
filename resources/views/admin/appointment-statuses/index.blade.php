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
        <form data-filter-fields="3"
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

                <label class="filter-label">Trạng thái</label>

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
                        Cho phép chuyển trạng thái
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

    @include('admin.layouts.partials.status-list', ['listTitle' => 'Danh sách trạng thái lịch hẹn', 'statusRows' => $appointmentStatuses, 'statusRoute' => 'admin.appointment-statuses', 'permissionField' => 'allows_transition', 'statusContext' => null])
@endsection
