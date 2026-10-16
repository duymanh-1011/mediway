@extends('admin.layouts.app')

@section('title', 'Khoản phải thanh toán')

@section('content')

    <div class="payable-page">

        <div class="payable-breadcrumb">
            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <strong>
                Khoản phải thanh toán
            </strong>
        </div>


        <div class="payable-header">

            <div>
                <div class="payable-module-label">
                    THANH TOÁN
                </div>

                <h1>
                    Khoản phải thanh toán
                </h1>

                <p>
                    Theo dõi phí khám, phí dịch vụ và trạng thái thanh toán.
                </p>
            </div>

            <div class="payable-header-badge">
                Xem / Trạng thái / Hủy nghiệp vụ
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


        <div class="payable-card payable-filter-card">

            <form method="GET" action="{{ route('admin.payables.index') }}" class="payable-filter-form">

                <div class="payable-form-group payable-search-group">

                    <label for="q">
                        Tìm kiếm
                    </label>

                    <input id="q" type="text" name="q" value="{{ request('q') }}" class="payable-control"
                        placeholder="Mã khoản thu, lượt khám, mô tả...">

                </div>


                <div class="payable-form-group">

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
                                    {{ $type }}
                                @endif
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="payable-form-group">

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
                                {{ $status->name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="payable-form-group">

                    <label for="date_from">
                        Từ ngày
                    </label>

                    <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}"
                        class="payable-control">

                </div>


                <div class="payable-form-group">

                    <label for="date_to">
                        Đến ngày
                    </label>

                    <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="payable-control">

                </div>


                <div class="payable-filter-actions">

                    <button type="submit" class="payable-btn payable-btn-primary">
                        Lọc dữ liệu
                    </button>

                    <a href="{{ route('admin.payables.index') }}" class="payable-btn payable-btn-secondary">
                        Đặt lại
                    </a>

                </div>

            </form>

        </div>


        <div class="payable-card">

            <div class="payable-table-header">

                <div>
                    <h2>
                        Danh sách khoản phải thanh toán
                    </h2>

                    <p>
                        Tổng cộng {{ $payables->total() }} bản ghi
                    </p>
                </div>

            </div>


            <div class="payable-table-wrapper">

                <table class="payable-table">

                    <thead>
                        <tr>
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

                                                $statusClass = match ($statusName) {
                                                    'PAID' => 'paid',
                                                    'UNPAID' => 'unpaid',
                                                    'PENDING' => 'pending',
                                                    'CANCELLED' => 'cancelled',
                                                    default => 'default',
                                                };

                                                $typeClass =
                                                    $payable->payment_type === 'EXAMINATION_FEE'
                                                    ? 'exam'
                                                    : 'service';
                                            @endphp

                                            <tr>

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
                                                            {{ $payable->payment_type }}
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

                                                    <span class="
                                                                payable-status
                                                                {{ $statusClass }}
                                                            ">
                                                        {{ $payable->payment_status_name ?? '—' }}
                                                    </span>

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


                                                <td class="text-right">

                                                    <a href="{{ route(
                                'admin.payables.show',
                                $payable->id
                            ) }}" class="payable-detail-link">
                                                        Chi tiết
                                                    </a>

                                                </td>

                                            </tr>

                        @empty

                            <tr>
                                <td colspan="10" class="payable-empty-state">

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
                            <span class="payable-page-btn disabled">
                                ← Trước
                            </span>
                        @else
                            <a href="{{ $payables->previousPageUrl() }}" class="payable-page-btn">
                                ← Trước
                            </a>
                        @endif


                        <span class="payable-page-current">
                            Trang
                            {{ $payables->currentPage() }}
                            /
                            {{ $payables->lastPage() }}
                        </span>


                        @if ($payables->hasMorePages())
                            <a href="{{ $payables->nextPageUrl() }}" class="payable-page-btn">
                                Sau →
                            </a>
                        @else
                            <span class="payable-page-btn disabled">
                                Sau →
                            </span>
                        @endif

                    </div>

                </div>

            @endif

        </div>

    </div>


    <style>
        .payable-page {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            width: 100%;
            color: var(--text);
        }

        .payable-page * {
            box-sizing: border-box;
        }

        .payable-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
            color: #94a3b8;
            font-size: 13px;
        }

        .payable-breadcrumb a {
            color: #64748b;
            text-decoration: none;
        }

        .payable-breadcrumb strong {
            color: #334155;
        }

        .payable-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 24px;
        }

        .payable-module-label {
            margin-bottom: 7px;
            color: var(--primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .payable-header h1 {
            margin: 0;
            font-size: 29px;
        }

        .payable-header p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .payable-header-badge {
            padding: 8px 13px;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
        }

        .payable-alert {
            margin-bottom: 18px;
            padding: 13px 15px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 650;
        }

        .payable-alert-success {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #15803d;
        }

        .payable-alert-error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }

        .payable-card {
            overflow: hidden;
            margin-bottom: 22px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 3px 14px rgba(15, 23, 42, .035);
        }

        .payable-filter-card {
            padding: 18px;
        }

        .payable-filter-form {
            display: grid;
            grid-template-columns:
                minmax(220px, 1.8fr) minmax(145px, .9fr) minmax(140px, .8fr) minmax(135px, .7fr) minmax(135px, .7fr) auto;
            gap: 12px;
            align-items: end;
        }

        .payable-form-group label {
            display: block;
            margin-bottom: 7px;
            color: #475569;
            font-size: 10px;
            font-weight: 700;
        }

        .payable-control {
            display: block;
            width: 100%;
            height: 40px;
            padding: 0 11px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            background: #ffffff;
            color: #0f172a;
            font-family: inherit;
            font-size: 11px;
        }

        .payable-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
        }

        .payable-filter-actions {
            display: flex;
            gap: 8px;
        }

        .payable-btn {
            display: inline-flex;
            height: 40px;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 10px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
        }

        .payable-btn-primary {
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .payable-btn-secondary {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
        }

        .payable-table-header {
            padding: 17px 20px;
            border-bottom: 1px solid var(--border);
        }

        .payable-table-header h2 {
            margin: 0;
            font-size: 15px;
        }

        .payable-table-header p {
            margin: 4px 0 0;
            color: #94a3b8;
            font-size: 10px;
        }

        .payable-table-wrapper {
            overflow-x: auto;
        }

        .payable-table {
            width: 100%;
            min-width: 1250px;
            border-collapse: collapse;
        }

        .payable-table th {
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            font-size: 9px;
            font-weight: 800;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .payable-table td {
            padding: 14px;
            border-bottom: 1px solid #edf2f7;
            color: #334155;
            font-size: 10px;
            vertical-align: middle;
        }

        .payable-table tbody tr:hover {
            background: #fafcff;
        }

        .text-center {
            text-align: center !important;
        }

        .text-right {
            text-align: right !important;
        }

        .payable-code,
        .payable-secondary-code {
            font-family: Consolas, Monaco, monospace;
        }

        .payable-code {
            color: #1d4ed8;
            font-size: 10px;
        }

        .payable-secondary-code {
            color: #475569;
            font-size: 9px;
        }

        .payable-description {
            max-width: 220px;
            line-height: 1.5;
        }

        .payable-amount {
            color: #0f172a;
            white-space: nowrap;
        }

        .payable-type,
        .payable-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: 800;
            white-space: nowrap;
        }

        .payable-type.exam {
            background: #f3e8ff;
            color: #7e22ce;
        }

        .payable-type.service {
            background: #e0f2fe;
            color: #0369a1;
        }

        .payable-status.paid {
            background: #dcfce7;
            color: #15803d;
        }

        .payable-status.unpaid {
            background: #fee2e2;
            color: #b91c1c;
        }

        .payable-status.pending {
            background: #fef3c7;
            color: #b45309;
        }

        .payable-status.cancelled {
            background: #f1f5f9;
            color: #64748b;
        }

        .payable-status.default {
            background: #f1f5f9;
            color: #475569;
        }

        .payable-time {
            display: grid;
            gap: 2px;
        }

        .payable-time strong,
        .payable-time span {
            font-size: 9px;
        }

        .payable-time span,
        .payable-empty {
            color: #94a3b8;
        }

        .payable-count {
            display: inline-flex;
            min-width: 26px;
            height: 26px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #f1f5f9;
            font-weight: 800;
        }

        .payable-detail-link {
            display: inline-flex;
            height: 30px;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
            border: 1px solid #bfdbfe;
            border-radius: 7px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 9px;
            font-weight: 750;
            text-decoration: none;
        }

        .payable-empty-state {
            padding: 50px 20px !important;
            text-align: center !important;
        }

        .payable-empty-icon {
            display: flex;
            width: 46px;
            height: 46px;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            border-radius: 12px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 18px;
            font-weight: 800;
        }

        .payable-empty-state strong {
            display: block;
            margin-bottom: 5px;
        }

        .payable-empty-state p {
            margin: 0;
            color: #94a3b8;
        }

        .payable-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            border-top: 1px solid #e2e8f0;
        }

        .payable-pagination-info,
        .payable-page-current {
            color: #64748b;
            font-size: 10px;
        }

        .payable-pagination-actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .payable-page-btn {
            display: inline-flex;
            height: 31px;
            align-items: center;
            padding: 0 10px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            color: #475569;
            font-size: 9px;
            text-decoration: none;
        }

        .payable-page-btn.disabled {
            opacity: .45;
        }

        @media (max-width: 1200px) {
            .payable-filter-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .payable-search-group {
                grid-column: span 2;
            }

            .payable-filter-actions {
                grid-column: span 2;
                justify-content: flex-end;
            }
        }

        @media (max-width: 768px) {
            .payable-header {
                flex-direction: column;
            }

            .payable-filter-form {
                grid-template-columns: 1fr;
            }

            .payable-search-group,
            .payable-filter-actions {
                grid-column: span 1;
            }

            .payable-filter-actions {
                flex-direction: column;
            }

            .payable-btn {
                width: 100%;
            }

            .payable-pagination {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>

@endsection