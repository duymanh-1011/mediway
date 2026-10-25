@extends('admin.layouts.app')
@section('admin-page', 'payment-statuses-index')

@section('title', 'Trạng thái thanh toán')

@section('content')

    <div class="status-page">

        {{-- Breadcrumb --}}
        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Cấu hình trạng thái</span>
            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                Trạng thái thanh toán
            </strong>

        </nav>


        {{-- Header --}}
        <div class="admin-info-header status-header">

            <div>


                <h1>
                    Trạng thái thanh toán
                </h1>

                <p>
                    Quản lý cấu hình quy trình và trạng thái sử dụng
                    của các trạng thái thanh toán.
                </p>

            </div>


        </div>


        {{-- Filter --}}
        @component('admin.layouts.partials.filter-panel', [
            'title' => 'Bộ lọc trạng thái thanh toán',
            'description' => 'Lọc theo từ khóa và trạng thái sử dụng.',
        ])
            <form data-filter-fields="2" method="GET" action="{{ route('admin.payment-statuses.index') }}" class="admin-filter-form">

                    <div class="status-filter-grid admin-filter-layout">

                        {{-- Search --}}
                        <div class="status-form-group admin-filter-field">

                            <label for="q">
                                Tìm kiếm
                            </label>

                            <input id="q" type="text" name="q" class="status-control" value="{{ request('q') }}"
                                placeholder="Tên trạng thái hoặc mô tả...">
@error('q')<div class="field-error">{{ $message }}</div>@enderror

                        </div>


                        {{-- Usage status --}}
                        <div class="status-form-group admin-filter-field">

                            <label for="is_active">
                                Trạng thái
                            </label>

                            <select id="is_active" name="is_active" class="status-control">

                                <option value="">
                                    Tất cả
                                </option>

                                <option value="1" @selected(request('is_active') === '1')>Đang hoạt động</option>

                                <option value="0" @selected(request('is_active') === '0')>Ngừng hoạt động</option>

                            </select>
@error('is_active')<div class="field-error">{{ $message }}</div>@enderror

                        </div>

                    </div>


                    <div class="status-filter-actions admin-filter-layout">





                    </div>


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.payment-statuses.index'), 'searchOnly' => false])
            </form>
        @endcomponent

    @include('admin.layouts.partials.status-list', ['listTitle' => 'Danh sách trạng thái thanh toán', 'statusRows' => $statuses, 'statusRoute' => 'admin.payment-statuses', 'permissionField' => 'allows_service_execution', 'statusContext' => 'payment'])
@endsection
