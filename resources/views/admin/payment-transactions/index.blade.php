@extends('admin.layouts.app')
@section('admin-page', 'payment-transactions-index')

@section('title', 'Giao dịch thanh toán')

@section('content')

    <div class="transaction-page">

        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Thanh toán</span>
            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                Giao dịch thanh toán
            </strong>

        </nav>


        <div class="admin-info-header transaction-header">

            <div>

                <h1>
                    Giao dịch thanh toán
                </h1>

                <p>
                    Tra cứu lịch sử các giao dịch thanh toán.
                </p>
            </div>


        </div>


        @component('admin.layouts.partials.filter-panel', [
            'title' => 'Bộ lọc giao dịch thanh toán',
            'description' => 'Lọc theo từ khóa, phương thức, kết quả và khoảng thời gian.',
        ])
            <form method="GET" action="{{ route('admin.payment-transactions.index') }}" class="transaction-filter-form admin-filter-form">

                <div class="transaction-form-group transaction-search-group admin-filter-field">

                    <label for="q">
                        Tìm kiếm
                    </label>

                    <input id="q" type="text" name="q" value="{{ request('q') }}" class="transaction-control"
                        placeholder="Mã giao dịch, khoản thu, lượt khám, mã nhà cung cấp...">
@error('q')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="transaction-form-group admin-filter-field">

                    <label for="payment_method">
                        Phương thức
                    </label>

                    <select id="payment_method" name="payment_method" class="transaction-control">

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($paymentMethods as $method)

                            <option value="{{ $method }}" @selected(request('payment_method') === $method)>
                                {{ \App\Models\AdminDisplay::label($method) }}
                            </option>

                        @endforeach

                    </select>
@error('payment_method')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="transaction-form-group admin-filter-field">

                    <label for="transaction_result">
                        Kết quả
                    </label>

                    <select id="transaction_result" name="transaction_result" class="transaction-control">

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($transactionResults as $result)

                            <option value="{{ $result }}" @selected(
                                request('transaction_result') === $result
                            )>
                                {{ \App\Models\AdminDisplay::label($result) }}
                            </option>

                        @endforeach

                    </select>
@error('transaction_result')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="transaction-form-group admin-filter-field">

                    <label for="date_from">
                        Từ ngày
                    </label>

                    <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}"
                        class="transaction-control">
@error('date_from')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="transaction-form-group admin-filter-field">

                    <label for="date_to">
                        Đến ngày
                    </label>

                    <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}"
                        class="transaction-control">
@error('date_to')<div class="field-error">{{ $message }}</div>@enderror

                </div>


                <div class="transaction-filter-actions admin-filter-layout">





                </div>


@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.payment-transactions.index'), 'searchOnly' => false])
            </form>
        @endcomponent


        <div class="transaction-card">

            <div class="transaction-table-header">

                <div>
                    <h2 class="admin-list-title">Danh sách giao dịch thanh toán</h2>

                    <p>
                        Tổng cộng {{ $transactions->total() }} bản ghi
                    </p>
                </div>

            </div>


            <div class="transaction-table-wrapper">

                <table class="transaction-table">

                    <thead>
                        <tr>
                            <th scope="col" class="admin-row-number">STT</th>
                            <th>Mã giao dịch</th>
                            <th>Khoản thu</th>
                            <th>Lượt khám</th>
                            <th>Phương thức</th>
                            <th>Kết quả</th>
                            <th class="text-right">Số tiền</th>
                            <th>Thu tại quầy</th>
                            <th>Bắt đầu</th>
                            <th>Xác nhận</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>


                    <tbody>

                        @forelse ($transactions as $transaction)



                                            <tr>

                                                @include('admin.layouts.partials.row-number', ['rows' => $transactions, 'index' => $loop->index])
                                                <td>
                                                    <strong class="transaction-code">
                                                        {{ $transaction->transaction_code }}
                                                    </strong>
                                                </td>


                                                <td>
                                                    <span class="transaction-secondary-code">
                                                        {{ $transaction->payable_code ?? '—' }}
                                                    </span>
                                                </td>


                                                <td>
                                                    <span class="transaction-secondary-code">
                                                        {{ $transaction->visit_code ?? '—' }}
                                                    </span>
                                                </td>


                                                <td>
                                                    <span class="transaction-method">
                                                        {{ \App\Models\AdminDisplay::label($transaction->payment_method) }}
                                                    </span>
                                                </td>


                                                <td>
                                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => $transaction->transaction_result])
                                                </td>


                                                <td class="text-right">

                                                    <strong>
                                                        {{ number_format(
                                (float) $transaction->amount,
                                0,
                                ',',
                                '.'
                            ) }}
                                                        ₫
                                                    </strong>

                                                </td>


                                                <td>

                                                    @if ($transaction->collector_name)

                                                        <div class="transaction-collector">

                                                            <strong>
                                                                {{ $transaction->collector_name }}
                                                            </strong>

                                                            <span>
                                                                {{ $transaction->employee_code }}
                                                            </span>

                                                        </div>

                                                    @else

                                                        <span class="transaction-empty">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                <td>
                                                    {{
                                $transaction->started_at
                                ? $transaction->started_at->format(
                                    'd/m/Y H:i'
                                )
                                : '—'
                                                        }}
                                                </td>


                                                <td>
                                                    {{
                                $transaction->confirmed_at
                                ? $transaction->confirmed_at->format(
                                    'd/m/Y H:i'
                                )
                                : '—'
                                                        }}
                                                </td>


                                                <td class="text-right"><div class="admin-actions admin-table-actions">

                                                    <a href="{{ route(
                                'admin.payment-transactions.show',
                                $transaction->id
                            ) }}" class="admin-btn admin-btn-detail">Chi tiết</a>

                                                </div></td>

                                            </tr>

                        @empty

                            <tr>
                                <td colspan="11" class="transaction-empty-state">

                                    <strong>
                                        Không có giao dịch phù hợp
                                    </strong>

                                    <p>
                                        Thử thay đổi điều kiện tìm kiếm hoặc bộ lọc.
                                    </p>

                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            @if ($transactions->hasPages())

                <div class="transaction-pagination">

                    <div>
                        Hiển thị
                        {{ $transactions->firstItem() }}
                        -
                        {{ $transactions->lastItem() }}
                        /
                        {{ $transactions->total() }}
                    </div>


                    <div class="transaction-pagination-actions">

                        @if ($transactions->onFirstPage())
                            <span class="admin-btn admin-btn-secondary" aria-disabled="true">Trước</span>
                        @else
                            <a href="{{ $transactions->previousPageUrl() }}" class="admin-btn admin-btn-secondary">Trước</a>
                        @endif


                        <span>
                            Trang
                            {{ $transactions->currentPage() }}
                            /
                            {{ $transactions->lastPage() }}
                        </span>


                        @if ($transactions->hasMorePages())
                            <a href="{{ $transactions->nextPageUrl() }}" class="admin-btn admin-btn-secondary">Sau</a>
                        @else
                            <span class="admin-btn admin-btn-secondary" aria-disabled="true">Sau</span>
                        @endif

                    </div>

                </div>

            @endif

        </div>

    </div>




@endsection
