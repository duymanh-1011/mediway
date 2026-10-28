@extends('admin.layouts.app')
@section('admin-page', 'service-statuses-index')

@section('title', 'Trạng thái dịch vụ')

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
                Trạng thái dịch vụ
            </strong>

        </nav>


        {{-- Header --}}
        <div class="admin-info-header status-header">

            <div>


                <h1>
                    Trạng thái dịch vụ
                </h1>

                <p>
                    Quản lý cấu hình luồng xử lý và trạng thái sử dụng
                    của các chỉ định dịch vụ.
                </p>

            </div>


        </div>


        {{-- Filter --}}
        @component('admin.layouts.partials.filter-panel', [
            'title' => 'Bộ lọc trạng thái dịch vụ',
            'description' => 'Lọc theo từ khóa và trạng thái sử dụng.',
        ])
            <form data-filter-fields="2" method="GET" action="{{ route('admin.service-statuses.index') }}" class="admin-filter-form">

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


                        {{-- Usage Status --}}
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


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.service-statuses.index'), 'searchOnly' => false])
            </form>
        @endcomponent

    @include('admin.layouts.partials.status-list', ['listTitle' => 'Danh sách trạng thái dịch vụ', 'statusRows' => $statuses, 'statusRoute' => 'admin.service-statuses', 'permissionField' => 'allows_transition', 'statusContext' => 'service'])
@endsection
