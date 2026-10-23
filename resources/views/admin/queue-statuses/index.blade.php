@extends('admin.layouts.app')
@section('admin-page', 'queue-statuses-index')

@section('title', 'Trạng thái hàng đợi')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Cấu hình trạng thái</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Trạng thái hàng đợi</strong>
@endsection

@section('content')

    <div class="admin-u-e0f559a1">

        <div>
            <h1 class="page-title">
                Trạng thái hàng đợi
            </h1>

            <p class="page-description">
                Quản lý cấu hình trạng thái của hàng đợi phòng khám.
            </p>
        </div>

    </div>


    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc trạng thái hàng đợi',
        'description' => 'Lọc theo từ khóa và trạng thái.',
    ])
        <form
            method="GET"
            action="{{ route('admin.queue-statuses.index') }}"
         class="admin-filter-form">

            <div class="admin-u-b5392107 admin-filter-layout">

                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">
                        Tìm kiếm
                    </label>

                    <input class="admin-u-19b29622"
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Tên hoặc mô tả trạng thái..."

                    >
@error('search')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-filter-field">

                    <label class="admin-u-63101fce">Trạng thái</label>

                    <select class="admin-u-181f32ca"
                        name="is_active"

                    >

                        <option value="">
                            Tất cả
                        </option>

                        <option
                            value="1"
                            @selected(request('is_active') === '1')
                        >
                            Đang hoạt động
                        </option>

                        <option
                            value="0"
                            @selected(request('is_active') === '0')
                        >
                            Ngừng hoạt động
                        </option>

                    </select>
@error('is_active')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="admin-u-5786f359 admin-filter-layout">





                </div>

            </div>


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.queue-statuses.index'), 'searchOnly' => false])
        </form>
    @endcomponent

    @include('admin.layouts.partials.status-list', ['listTitle' => 'Danh sách trạng thái hàng đợi', 'statusRows' => $queueStatuses, 'statusRoute' => 'admin.queue-statuses', 'permissionField' => 'allows_transition', 'statusContext' => null])
@endsection
