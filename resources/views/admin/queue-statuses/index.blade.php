@extends('admin.layouts.app')

@section('title', 'Trạng thái hàng đợi')

@section('breadcrumb')
    <div class="breadcrumb">
        Dashboard / Cấu hình trạng thái / Trạng thái hàng đợi
    </div>
@endsection

@section('content')

    <div style="
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 22px;
    ">

        <div>
            <h1 class="page-title">
                Trạng thái hàng đợi
            </h1>

            <p class="page-description">
                Quản lý cấu hình trạng thái của hàng đợi phòng khám.
            </p>
        </div>

    </div>


    <div class="card" style="margin-bottom: 18px;">

        <form
            method="GET"
            action="{{ route('admin.queue-statuses.index') }}"
        >

            <div style="
                display: grid;
                grid-template-columns: minmax(220px, 1fr) 220px auto;
                gap: 12px;
                align-items: end;
            ">

                <div>

                    <label style="
                        display: block;
                        margin-bottom: 6px;
                        font-weight: 700;
                    ">
                        Tìm kiếm
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Tên hoặc mô tả trạng thái..."
                        style="
                            width: 100%;
                            padding: 9px 11px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                        "
                    >

                </div>


                <div>

                    <label style="
                        display: block;
                        margin-bottom: 6px;
                        font-weight: 700;
                    ">
                        Hoạt động
                    </label>

                    <select
                        name="is_active"
                        style="
                            width: 100%;
                            padding: 9px 11px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: white;
                        "
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

                </div>


                <div style="
                    display: flex;
                    gap: 8px;
                ">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Lọc
                    </button>

                    <a
                        href="{{ route('admin.queue-statuses.index') }}"
                        class="btn btn-secondary"
                    >
                        Đặt lại
                    </a>

                </div>

            </div>

        </form>

    </div>


    <div class="card">

        @if ($queueStatuses->count())

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên trạng thái</th>
                            <th>Mô tả</th>
                            <th>Thứ tự</th>
                            <th>Cho phép chuyển</th>
                            <th>Hoạt động</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>


                    <tbody>

                    @foreach ($queueStatuses as $status)

                        <tr>

                            <td>
                                {{ $status->id }}
                            </td>

                            <td>
                                <strong>
                                    {{ $status->name }}
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

                                <div style="
                                    display: flex;
                                    flex-wrap: wrap;
                                    gap: 6px;
                                ">

                                    <a
                                        href="{{ route(
                                            'admin.queue-statuses.show',
                                            $status
                                        ) }}"
                                        class="btn btn-secondary"
                                    >
                                        Chi tiết
                                    </a>

                                    <a
                                        href="{{ route(
                                            'admin.queue-statuses.edit',
                                            $status
                                        ) }}"
                                        class="btn btn-primary"
                                    >
                                        Sửa
                                    </a>

                                </div>

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            <div style="margin-top: 18px;">
                {{ $queueStatuses->links() }}
            </div>

        @else

            <div style="
                padding: 30px;
                text-align: center;
                color: #64748b;
            ">
                Không tìm thấy trạng thái hàng đợi phù hợp.
            </div>

        @endif

    </div>

@endsection
