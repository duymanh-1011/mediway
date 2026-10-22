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
        'description' => 'Lọc theo từ khóa và hoạt động.',
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

                    <label class="admin-u-63101fce">
                        Hoạt động
                    </label>

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


    <div class="card">

        @if ($queueStatuses->count())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th scope="col" class="admin-row-number">STT</th>
                            <th>Tên trạng thái</th>
                            <th>Mô tả</th>
                            <th>Thứ tự xử lý</th>
                            <th>Cho phép chuyển</th>
                            <th>Hoạt động</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>


                    <tbody>

                    @foreach ($queueStatuses as $status)

                        <tr>

                            @include('admin.layouts.partials.row-number', ['rows' => $queueStatuses, 'index' => $loop->index])

                            <td>
                                <strong>
                                    {{ \App\Models\AdminDisplay::label($status->name) }}
                                </strong>
                            </td>

                            <td>
                                {{ $status->description ?: '—' }}
                            </td>

                            <td>
                                {{ $status->process_order }}
                            </td>

                            <td>

                                @if ($status->allows_transition)

                                    <span class="badge badge-success">
                                        Có
                                    </span>

                                @else

                                    <span class="badge badge-warning">
                                        Không
                                    </span>

                                @endif

                            </td>

                            <td>

                                @if ($status->is_active)

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

                                <div class="admin-u-c9035c8e">

                                    <a
                                        href="{{ route(
                                            'admin.queue-statuses.show',
                                            $status
                                        ) }}"
                                        class="admin-btn admin-btn-detail btn btn-secondary"
                                    >Chi tiết</a>

                                    <a
                                        href="{{ route(
                                            'admin.queue-statuses.edit',
                                            $status
                                        ) }}"
                                        class="admin-btn btn btn-primary admin-btn-primary"
                                    >Sửa</a>

                                </div>

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            <div class="admin-u-6840f0a6">
                {{ $queueStatuses->links() }}
            </div>

        @else

            <div class="admin-u-caa0d3af">
                Không tìm thấy trạng thái hàng đợi phù hợp.
            </div>

        @endif

    </div>

@endsection
