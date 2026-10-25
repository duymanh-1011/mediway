@extends('admin.layouts.app')
@section('admin-page', 'service-orders-index')

@section('title', 'Chỉ định dịch vụ')

@section('content')

    <div class="service-order-page">

        {{-- Breadcrumb --}}
        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Dịch vụ</span>
            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                Chỉ định dịch vụ
            </strong>

        </nav>


        {{-- Header --}}
        <div class="admin-info-header order-header">

            <div>


                <h1>
                    Chỉ định dịch vụ
                </h1>

                <p>
                    Theo dõi các phiếu chỉ định dịch vụ,
                    lượt khám, bác sĩ chỉ định và trạng thái xử lý.
                </p>

            </div>


        </div>


        {{-- Flash --}}
        @if (session('success'))

            <div class="order-alert order-alert-success">
                {{ session('success') }}
            </div>

        @endif


        @if (session('error'))

            <div class="order-alert order-alert-error">
                {{ session('error') }}
            </div>

        @endif


        {{-- Filter --}}
        @component('admin.layouts.partials.filter-panel', [
            'title' => 'Bộ lọc phiếu chỉ định',
            'description' => 'Lọc theo từ khóa, trạng thái phiếu và khoảng thời gian.',
        ])
            <form data-filter-fields="4" method="GET" action="{{ route('admin.service-orders.index') }}" class="admin-filter-form">

                    <div class="order-filter-grid admin-filter-layout">

                        {{-- Search --}}
                        <div class="order-form-group order-search-group admin-filter-field">

                            <label for="q">
                                Tìm kiếm
                            </label>

                            <input id="q" type="text" name="q" class="order-control" value="{{ request('q') }}"
                                placeholder="Mã phiếu, lượt khám, bác sĩ...">
@error('q')<div class="field-error">{{ $message }}</div>@enderror

                        </div>


                        {{-- Status --}}
                        <div class="order-form-group admin-filter-field">

                            <label for="status">
                                Trạng thái phiếu
                            </label>

                            <select id="status" name="status" class="order-control">

                                <option value="">
                                    Tất cả
                                </option>

                                @foreach ($orderStatuses as $orderStatus)

                                    <option value="{{ $orderStatus }}" @selected(
                                        request('status') === $orderStatus
                                    )>
                                        {{ \App\Models\AdminDisplay::label($orderStatus) }}
                                    </option>

                                @endforeach

                            </select>
@error('status')<div class="field-error">{{ $message }}</div>@enderror

                        </div>


                        {{-- From date --}}
                        <div class="order-form-group admin-filter-field">

                            <label for="date_from">
                                Từ ngày
                            </label>

                            <input id="date_from" type="date" name="date_from" class="order-control"
                                value="{{ request('date_from') }}">
@error('date_from')<div class="field-error">{{ $message }}</div>@enderror

                        </div>


                        {{-- To date --}}
                        <div class="order-form-group admin-filter-field">

                            <label for="date_to">
                                Đến ngày
                            </label>

                            <input id="date_to" type="date" name="date_to" class="order-control"
                                value="{{ request('date_to') }}">
@error('date_to')<div class="field-error">{{ $message }}</div>@enderror

                        </div>

                    </div>


                    <div class="order-filter-actions admin-filter-layout">





                    </div>


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.service-orders.index'), 'searchOnly' => false])
            </form>
        @endcomponent


        {{-- List --}}
        <div class="order-card">

            <div class="order-card-header order-list-header">

                <div>

                    <h2 class="admin-list-title">Danh sách phiếu chỉ định</h2>

                    <p>
                        Các phiếu được sắp xếp theo
                        thời gian chỉ định mới nhất.
                    </p>

                </div>


                <div class="order-count">

                    Tổng cộng

                    <strong>
                        {{ $orders->total() }}
                    </strong>

                    phiếu

                </div>

            </div>


            <div class="order-table-wrapper">

                <table class="order-table">

                    <thead>

                        <tr>

                            <th scope="col" class="admin-row-number">STT</th>
                            <th>
                                Mã phiếu
                            </th>

                            <th>
                                Lượt khám
                            </th>

                            <th>
                                Bác sĩ chỉ định
                            </th>

                            <th>
                                Thời gian chỉ định
                            </th>

                            <th>
                                Số dịch vụ
                            </th>

                            <th>
                                Trạng thái
                            </th>

                            <th>
                                Thao tác
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($orders as $order)

                                            <tr>

                                                {{-- Order code --}}
                                                @include('admin.layouts.partials.row-number', ['rows' => $orders, 'index' => $loop->index])
                                                <td>

                                                    <div class="order-code">
                                                        @include('admin.layouts.partials.record-code', ['code' => $order->service_order_code])
                                                    </div>

                                                </td>


                                                {{-- Visit --}}
                                                <td>

                                                    @if ($order->medicalVisit)

                                                        <div class="order-visit-code">
                                                            @include('admin.layouts.partials.record-code', ['code' => $order->medicalVisit->visit_code])
                                                        </div>

                                                    @else

                                                        <span class="order-empty-value">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Doctor --}}
                                                <td>

                                                    @if ($order->orderedByDoctor)

                                                        <div class="order-doctor">

                                                            <strong>
                                                                {{ $order->orderedByDoctor->full_name }}
                                                            </strong>

                                                            <span>
                                                                @include('admin.layouts.partials.record-code', ['code' => $order->orderedByDoctor->doctor_code])
                                                            </span>

                                                        </div>

                                                    @else

                                                        <span class="order-empty-value">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Ordered at --}}
                                                <td>

                                                    <div class="order-date">

                                                        @if ($order->ordered_at)

                                                                                    {{ $order->ordered_at->format(
                                                                'd/m/Y H:i'
                                                            ) }}

                                                        @else

                                                            —

                                                        @endif

                                                    </div>

                                                </td>


                                                {{-- Items count --}}
                                                <td>

                                                    <span class="order-item-count">
                                                        {{ $order->items_count }}
                                                    </span>

                                                </td>


                                                {{-- Status --}}
                                                <td>



                                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => $order->status])

                                                </td>


                                                {{-- Action --}}
                                                <td><div class="admin-actions admin-table-actions">

                                                    <div class="order-actions">

                                                        <a href="{{ route(
                                'admin.service-orders.show',
                                $order->id
                            ) }}" class="admin-btn admin-btn-detail">Chi tiết</a>

                                                    </div>

                                                </div></td>

                                            </tr>

                        @empty

                            <tr>

                                <td colspan="8" class="order-empty">

                                    <div class="order-empty-icon">
                                        !
                                    </div>

                                    <strong>
                                        Không tìm thấy phiếu chỉ định
                                    </strong>

                                    <p>
                                        Không có dữ liệu phù hợp với
                                        điều kiện tìm kiếm hiện tại.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}
            @if ($orders->hasPages())

                <div class="order-pagination">

                    <div class="order-pagination-info">

                        Trang

                        <strong>
                            {{ $orders->currentPage() }}
                        </strong>

                        /

                        {{ $orders->lastPage() }}

                    </div>


                    <div class="order-pagination-buttons">

                        @if ($orders->onFirstPage())

                            <span class="admin-btn admin-btn-secondary" aria-disabled="true">Trước</span>

                        @else

                            <a href="{{ $orders->previousPageUrl() }}" class="admin-btn admin-btn-secondary">Trước</a>

                        @endif


                        @if ($orders->hasMorePages())

                            <a href="{{ $orders->nextPageUrl() }}" class="admin-btn admin-btn-secondary">Sau</a>

                        @else

                            <span class="admin-btn admin-btn-secondary" aria-disabled="true">Sau</span>

                        @endif

                    </div>

                </div>

            @endif

        </div>

    </div>




@endsection
