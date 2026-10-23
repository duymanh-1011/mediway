@extends('admin.layouts.app')
@section('admin-page', 'payables-index')

@section('title', 'Khoản phải thanh toán')

@section('content')

    <div class="payable-page">

        <nav class="admin-breadcrumb" aria-label="Đường dẫn">
            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Thanh toán</span>
            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                Khoản phải thanh toán
            </strong>
        </nav>


        <div class="admin-info-header payable-header">

            <div>

                <h1>
                    Khoản phải thanh toán
                </h1>

                <p>
                    Theo dõi phí khám, phí dịch vụ và trạng thái thanh toán.
                </p>
            </div>


        </div>


        @if (session('success'))
            <div class="payable-alert payable-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="payable-alert payable-alert-error">
                {{ session('error') }}
            </div>
        @endif


        @component('admin.layouts.partials.filter-panel', [
            'title' => 'Bộ lọc khoản phải thanh toán',
            'description' => 'Lọc theo từ khóa, loại khoản thu, trạng thái và khoảng thời gian.',
        ])
            <form method="GET" action="{{ route('admin.payables.index') }}" class="payable-filter-form admin-filter-form">

                <div class="payable-form-group payable-search-group admin-filter-field">

                    <label for="q">
                        Tìm kiếm
                    </label>

                    <input id="q" type="text" name="q" value="{{ request('q') }}" class="payable-control"
                        placeholder="Mã khoản thu, lượt khám, mô tả...">
@error('q')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="payable-form-group admin-filter-field">

                    <label for="payment_type">
                        Loại khoản thu
                    </label>

                    <select id="payment_type" name="payment_type" class="payable-control">

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($paymentTypes as $type)

                            <option value="{{ $type }}" @selected(request('payment_type') === $type)>
                                @if ($type === 'EXAMINATION_FEE')
                                    Phí khám
                                @elseif ($type === 'SERVICE_FEE')
                                    Phí dịch vụ
                                @else
                                    {{ \App\Models\AdminDisplay::label($type) }}
                                @endif
                            </option>

                        @endforeach

                    </select>
@error('payment_type')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="payable-form-group admin-filter-field">

                    <label for="payment_status_id">
                        Trạng thái
                    </label>

                    <select id="payment_status_id" name="payment_status_id" class="payable-control">

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($paymentStatuses as $status)

                            <option value="{{ $status->id }}" @selected(
                                (string) request('payment_status_id')
                                ===
                                (string) $status->id
                            )>
                                {{ \App\Models\AdminDisplay::label($status->name, 'payment') }}
                            </option>

                        @endforeach

                    </select>
@error('payment_status_id')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="payable-form-group admin-filter-field">

                    <label for="date_from">
                        Từ ngày
                    </label>

                    <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}"
                        class="payable-control">
@error('date_from')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="payable-form-group admin-filter-field">

                    <label for="date_to">
                        Đến ngày
                    </label>

                    <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="payable-control">
@error('date_to')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="payable-filter-actions admin-filter-layout">





                </div>


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.payables.index'), 'searchOnly' => false])
            </form>
        @endcomponent


        <div class="payable-card">

            <div class="payable-table-header">

                <div>
                    <h2 class="admin-list-title">Danh sách khoản phải thanh toán</h2>

                    <p>
                        Tổng cộng {{ $payables->total() }} bản ghi
                    </p>
                </div>

            </div>


            <div class="payable-table-wrapper">

                <table class="payable-table">

                    <thead>
                        <tr>
                            <th scope="col" class="admin-row-number">STT</th>
                            <th>Mã khoản thu</th>
                            <th>Lượt khám</th>
                            <th>Loại</th>
                            <th>Mô tả</th>
                            <th class="text-right">Số tiền</th>
                            <th>Trạng thái</th>
                            <th>Thời điểm tạo</th>
                            <th>Đã thanh toán</th>
                            <th class="text-center">Giao dịch</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>


                    <tbody>

                        @forelse ($payables as $payable)

                                            @php
                                                $statusName = strtoupper(
                                                    $payable->payment_status_name ?? ''
                                                );



                                                $typeClass =
                                                    $payable->payment_type === 'EXAMINATION_FEE'
                                                    ? 'exam'
                                                    : 'service';
                                            @endphp

                                            <tr>

                                                @include('admin.layouts.partials.row-number', ['rows' => $payables, 'index' => $loop->index])
                                                <td>
                                                    <strong class="payable-code">
                                                        {{ $payable->payable_code }}
                                                    </strong>
                                                </td>


                                                <td>
                                                    <span class="payable-secondary-code">
                                                        {{ $payable->visit_code ?? '—' }}
                                                    </span>
                                                </td>


                                                <td>

                                                    <span class="
                                                                payable-type
                                                                {{ $typeClass }}
                                                            ">
                                                        @if ($payable->payment_type === 'EXAMINATION_FEE')
                                                            Phí khám
                                                        @elseif ($payable->payment_type === 'SERVICE_FEE')
                                                            Phí dịch vụ
                                                        @else
                                                            {{ \App\Models\AdminDisplay::label($payable->payment_type) }}
                                                        @endif
                                                    </span>

                                                </td>


                                                <td>
                                                    <div class="payable-description">
                                                        {{ $payable->description }}
                                                    </div>
                                                </td>


                                                <td class="text-right">

                                                    <strong class="payable-amount">
                                                        {{ number_format(
                                (float) $payable->amount,
                                0,
                                ',',
                                '.'
                            ) }}
                                                        ₫
                                                    </strong>

                                                </td>


                                                <td>

                                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => $payable->payment_status_name ?? '—', 'badgeContext' => 'payment'])

                                                </td>


                                                <td>

                                                    @if ($payable->generated_at)

                                                        <div class="payable-time">
                                                            <strong>
                                                                {{ $payable->generated_at->format('d/m/Y') }}
                                                            </strong>

                                                            <span>
                                                                {{ $payable->generated_at->format('H:i') }}
                                                            </span>
                                                        </div>

                                                    @else
                                                        <span class="payable-empty">—</span>
                                                    @endif

                                                </td>


                                                <td>

                                                    @if ($payable->paid_at)

                                                        <div class="payable-time">
                                                            <strong>
                                                                {{ $payable->paid_at->format('d/m/Y') }}
                                                            </strong>

                                                            <span>
                                                                {{ $payable->paid_at->format('H:i') }}
                                                            </span>
                                                        </div>

                                                    @else
                                                        <span class="payable-empty">
                                                            Chưa thanh toán
                                                        </span>
                                                    @endif

                                                </td>


                                                <td class="text-center">

                                                    <span class="payable-count">
                                                        {{ $payable->transactions_count }}
                                                    </span>

                                                </td>


                                                <td class="text-right"><div class="admin-actions admin-table-actions">

                                                    <a href="{{ route(
                                'admin.payables.show',
                                $payable->id
                            ) }}" class="admin-btn admin-btn-detail">Chi tiết</a>

                                                </div></td>

                                            </tr>

                        @empty

                            <tr>
                                <td colspan="11" class="payable-empty-state">

                                    <div class="payable-empty-icon">
                                        ₫
                                    </div>

                                    <strong>
                                        Chưa có khoản phải thanh toán phù hợp
                                    </strong>

                                    <p>
                                        Không tìm thấy dữ liệu theo điều kiện hiện tại.
                                    </p>

                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            @if ($payables->hasPages())

                <div class="payable-pagination">

                    <div class="payable-pagination-info">
                        Hiển thị

                        <strong>
                            {{ $payables->firstItem() }}
                        </strong>

                        -

                        <strong>
                            {{ $payables->lastItem() }}
                        </strong>

                        trên

                        <strong>
                            {{ $payables->total() }}
                        </strong>

                        bản ghi
                    </div>


                    <div class="payable-pagination-actions">

                        @if ($payables->onFirstPage())
                            <span class="admin-btn admin-btn-secondary" aria-disabled="true">Trước</span>
                        @else
                            <a href="{{ $payables->previousPageUrl() }}" class="admin-btn admin-btn-secondary">Trước</a>
                        @endif


                        <span class="payable-page-current">
                            Trang
                            {{ $payables->currentPage() }}
                            /
                            {{ $payables->lastPage() }}
                        </span>


                        @if ($payables->hasMorePages())
                            <a href="{{ $payables->nextPageUrl() }}" class="admin-btn admin-btn-secondary">Sau</a>
                        @else
                            <span class="admin-btn admin-btn-secondary" aria-disabled="true">Sau</span>
                        @endif

                    </div>

                </div>

            @endif

        </div>

    </div>




@endsection
