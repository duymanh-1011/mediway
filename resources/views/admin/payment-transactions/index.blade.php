@extends('admin.layouts.app')

@section('title', 'Giao dịch thanh toán')

@section('content')

    <div class="transaction-page">

        <div class="transaction-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <strong>
                Giao dịch thanh toán
            </strong>

        </div>


        <div class="transaction-header">

            <div>
                <div class="transaction-module-label">
                    THANH TOÁN
                </div>

                <h1>
                    Giao dịch thanh toán
                </h1>

                <p>
                    Tra cứu lịch sử các giao dịch thanh toán.
                </p>
            </div>

            <div class="transaction-header-badge">
                Chỉ đọc / Tra cứu
            </div>

        </div>


        <div class="transaction-card transaction-filter-card">

            <form method="GET" action="{{ route('admin.payment-transactions.index') }}" class="transaction-filter-form">

                <div class="transaction-form-group transaction-search-group">

                    <label for="q">
                        Tìm kiếm
                    </label>

                    <input id="q" type="text" name="q" value="{{ request('q') }}" class="transaction-control"
                        placeholder="Mã giao dịch, khoản thu, lượt khám, mã nhà cung cấp...">

                </div>


                <div class="transaction-form-group">

                    <label for="payment_method">
                        Phương thức
                    </label>

                    <select id="payment_method" name="payment_method" class="transaction-control">

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($paymentMethods as $method)

                            <option value="{{ $method }}" @selected(request('payment_method') === $method)>
                                {{ $method }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="transaction-form-group">

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
                                {{ $result }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="transaction-form-group">

                    <label for="date_from">
                        Từ ngày
                    </label>

                    <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}"
                        class="transaction-control">

                </div>


                <div class="transaction-form-group">

                    <label for="date_to">
                        Đến ngày
                    </label>

                    <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}"
                        class="transaction-control">

                </div>


                <div class="transaction-filter-actions">

                    <button type="submit" class="transaction-btn transaction-btn-primary">
                        Lọc dữ liệu
                    </button>

                    <a href="{{ route('admin.payment-transactions.index') }}"
                        class="transaction-btn transaction-btn-secondary">
                        Đặt lại
                    </a>

                </div>

            </form>

        </div>


        <div class="transaction-card">

            <div class="transaction-table-header">

                <div>
                    <h2>
                        Danh sách giao dịch
                    </h2>

                    <p>
                        Tổng cộng {{ $transactions->total() }} bản ghi
                    </p>
                </div>

            </div>


            <div class="transaction-table-wrapper">

                <table class="transaction-table">

                    <thead>
                        <tr>
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

                                            @php
                                                $resultClass = match (
                                                strtoupper(
                                                    $transaction->transaction_result ?? ''
                                                )
                                                ) {
                                                    'SUCCESS' => 'success',
                                                    'FAILED' => 'failed',
                                                    default => 'default',
                                                };
                                            @endphp

                                            <tr>

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
                                                        {{ $transaction->payment_method }}
                                                    </span>
                                                </td>


                                                <td>
                                                    <span class="
                                                                transaction-result
                                                                {{ $resultClass }}
                                                            ">
                                                        {{ $transaction->transaction_result }}
                                                    </span>
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


                                                <td class="text-right">

                                                    <a href="{{ route(
                                'admin.payment-transactions.show',
                                $transaction->id
                            ) }}" class="transaction-detail-link">
                                                        Chi tiết
                                                    </a>

                                                </td>

                                            </tr>

                        @empty

                            <tr>
                                <td colspan="10" class="transaction-empty-state">

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
                            <span class="transaction-page-btn disabled">
                                ← Trước
                            </span>
                        @else
                            <a href="{{ $transactions->previousPageUrl() }}" class="transaction-page-btn">
                                ← Trước
                            </a>
                        @endif


                        <span>
                            Trang
                            {{ $transactions->currentPage() }}
                            /
                            {{ $transactions->lastPage() }}
                        </span>


                        @if ($transactions->hasMorePages())
                            <a href="{{ $transactions->nextPageUrl() }}" class="transaction-page-btn">
                                Sau →
                            </a>
                        @else
                            <span class="transaction-page-btn disabled">
                                Sau →
                            </span>
                        @endif

                    </div>

                </div>

            @endif

        </div>

    </div>


    <style>
        .transaction-page {
            --primary: #2563eb;
            --border: #e2e8f0;

            width: 100%;
            color: #0f172a;
        }

        .transaction-page * {
            box-sizing: border-box;
        }

        .transaction-breadcrumb {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 18px;
            color: #94a3b8;
            font-size: 13px;
        }

        .transaction-breadcrumb a {
            color: #64748b;
            text-decoration: none;
        }

        .transaction-header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }

        .transaction-module-label {
            margin-bottom: 7px;
            color: #2563eb;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .transaction-header h1 {
            margin: 0;
            font-size: 29px;
        }

        .transaction-header p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .transaction-header-badge {
            height: fit-content;
            padding: 8px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            background: #f8fafc;
            color: #475569;
            font-size: 10px;
            font-weight: 800;
        }

        .transaction-card {
            overflow: hidden;
            margin-bottom: 22px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #ffffff;
        }

        .transaction-filter-card {
            padding: 18px;
        }

        .transaction-filter-form {
            display: grid;
            grid-template-columns:
                minmax(230px, 1.8fr) minmax(130px, .7fr) minmax(130px, .7fr) minmax(135px, .7fr) minmax(135px, .7fr) auto;
            gap: 12px;
            align-items: end;
        }

        .transaction-form-group label {
            display: block;
            margin-bottom: 7px;
            color: #475569;
            font-size: 10px;
            font-weight: 700;
        }

        .transaction-control {
            width: 100%;
            height: 40px;
            padding: 0 11px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            background: #ffffff;
            font-family: inherit;
            font-size: 11px;
        }

        .transaction-control:focus {
            border-color: #2563eb;
        }

        .transaction-filter-actions {
            display: flex;
            gap: 8px;
        }

        .transaction-btn {
            display: inline-flex;
            height: 40px;
            align-items: center;
            justify-content: center;
            padding: 0 13px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 10px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }

        .transaction-btn-primary {
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .transaction-btn-secondary {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
        }

        .transaction-table-header {
            padding: 17px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .transaction-table-header h2 {
            margin: 0;
            font-size: 15px;
        }

        .transaction-table-header p {
            margin: 4px 0 0;
            color: #94a3b8;
            font-size: 10px;
        }

        .transaction-table-wrapper {
            overflow-x: auto;
        }

        .transaction-table {
            width: 100%;
            min-width: 1250px;
            border-collapse: collapse;
        }

        .transaction-table th {
            padding: 11px 13px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            font-size: 9px;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .transaction-table td {
            padding: 13px;
            border-bottom: 1px solid #edf2f7;
            color: #334155;
            font-size: 10px;
        }

        .transaction-table tbody tr:hover {
            background: #fafcff;
        }

        .text-right {
            text-align: right !important;
        }

        .transaction-code,
        .transaction-secondary-code {
            font-family: Consolas, Monaco, monospace;
        }

        .transaction-code {
            color: #1d4ed8;
        }

        .transaction-secondary-code {
            color: #475569;
            font-size: 9px;
        }

        .transaction-method {
            display: inline-flex;
            padding: 5px 8px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 8px;
            font-weight: 800;
        }

        .transaction-result {
            display: inline-flex;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: 800;
        }

        .transaction-result.success {
            background: #dcfce7;
            color: #15803d;
        }

        .transaction-result.failed {
            background: #fee2e2;
            color: #b91c1c;
        }

        .transaction-result.default {
            background: #f1f5f9;
            color: #475569;
        }

        .transaction-collector {
            display: grid;
            gap: 3px;
        }

        .transaction-collector strong {
            font-size: 9px;
        }

        .transaction-collector span,
        .transaction-empty {
            color: #94a3b8;
            font-size: 8px;
        }

        .transaction-detail-link {
            display: inline-flex;
            height: 29px;
            align-items: center;
            padding: 0 10px;
            border: 1px solid #bfdbfe;
            border-radius: 7px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 9px;
            text-decoration: none;
        }

        .transaction-empty-state {
            padding: 45px 20px !important;
            text-align: center;
        }

        .transaction-empty-state strong {
            display: block;
            margin-bottom: 5px;
        }

        .transaction-empty-state p {
            margin: 0;
            color: #94a3b8;
        }

        .transaction-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 10px;
        }

        .transaction-pagination-actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .transaction-page-btn {
            display: inline-flex;
            height: 31px;
            align-items: center;
            padding: 0 10px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            color: #475569;
            text-decoration: none;
        }

        .transaction-page-btn.disabled {
            opacity: .45;
        }

        @media (max-width: 1150px) {
            .transaction-filter-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .transaction-search-group {
                grid-column: span 2;
            }

            .transaction-filter-actions {
                grid-column: span 2;
                justify-content: flex-end;
            }
        }

        @media (max-width: 768px) {
            .transaction-header {
                flex-direction: column;
            }

            .transaction-filter-form {
                grid-template-columns: 1fr;
            }

            .transaction-search-group,
            .transaction-filter-actions {
                grid-column: span 1;
            }

            .transaction-filter-actions {
                flex-direction: column;
            }

            .transaction-btn {
                width: 100%;
            }

            .transaction-pagination {
                flex-direction: column;
            }
        }
    </style>

@endsection