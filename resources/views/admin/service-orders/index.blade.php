@extends('admin.layouts.app')

@section('title', 'Chỉ định dịch vụ')

@section('content')

    <div class="service-order-page">

        {{-- Breadcrumb --}}
        <div class="order-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <strong>
                Chỉ định dịch vụ
            </strong>

        </div>


        {{-- Header --}}
        <div class="order-header">

            <div>

                <div class="order-module-label">
                    DỊCH VỤ
                </div>

                <h1>
                    Chỉ định dịch vụ
                </h1>

                <p>
                    Theo dõi các phiếu chỉ định dịch vụ,
                    lượt khám, bác sĩ chỉ định và trạng thái xử lý.
                </p>

            </div>

            <div class="order-policy-badge">
                <span></span>
                Xem / Theo dõi nghiệp vụ
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
        <div class="order-card">

            <div class="order-card-header">

                <div>

                    <h2>
                        Tìm kiếm và lọc
                    </h2>

                    <p>
                        Tìm theo mã phiếu, lượt khám,
                        mã bác sĩ hoặc tên bác sĩ.
                    </p>

                </div>

            </div>


            <div class="order-card-body">

                <form method="GET" action="{{ route('admin.service-orders.index') }}">

                    <div class="order-filter-grid">

                        {{-- Search --}}
                        <div class="order-form-group order-search-group">

                            <label for="q">
                                Tìm kiếm
                            </label>

                            <input id="q" type="text" name="q" class="order-control" value="{{ request('q') }}"
                                placeholder="Mã phiếu, lượt khám, bác sĩ...">

                        </div>


                        {{-- Status --}}
                        <div class="order-form-group">

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
                                        {{ $orderStatus }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- From date --}}
                        <div class="order-form-group">

                            <label for="date_from">
                                Từ ngày
                            </label>

                            <input id="date_from" type="date" name="date_from" class="order-control"
                                value="{{ request('date_from') }}">

                        </div>


                        {{-- To date --}}
                        <div class="order-form-group">

                            <label for="date_to">
                                Đến ngày
                            </label>

                            <input id="date_to" type="date" name="date_to" class="order-control"
                                value="{{ request('date_to') }}">

                        </div>

                    </div>


                    <div class="order-filter-actions">

                        <button type="submit" class="order-btn order-btn-primary">
                            Lọc dữ liệu
                        </button>

                        <a href="{{ route('admin.service-orders.index') }}" class="order-btn order-btn-secondary">
                            Xóa bộ lọc
                        </a>

                    </div>

                </form>

            </div>

        </div>


        {{-- List --}}
        <div class="order-card">

            <div class="order-card-header order-list-header">

                <div>

                    <h2>
                        Danh sách phiếu chỉ định
                    </h2>

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
                                                <td>

                                                    <div class="order-code">
                                                        {{ $order->service_order_code }}
                                                    </div>

                                                </td>


                                                {{-- Visit --}}
                                                <td>

                                                    @if ($order->medicalVisit)

                                                        <div class="order-visit-code">
                                                            {{ $order->medicalVisit->visit_code }}
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
                                                                {{ $order->orderedByDoctor->doctor_code }}
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

                                                    @php
                                                        $statusClass = match ($order->status) {
                                                            'COMPLETED' => 'status-completed',
                                                            'ACTIVE' => 'status-active',
                                                            'CANCELLED' => 'status-cancelled',
                                                            default => 'status-default',
                                                        };
                                                    @endphp

                                                    <span class="order-status-badge {{ $statusClass }}">
                                                        {{ $order->status }}
                                                    </span>

                                                </td>


                                                {{-- Action --}}
                                                <td>

                                                    <div class="order-actions">

                                                        <a href="{{ route(
                                'admin.service-orders.show',
                                $order->id
                            ) }}" class="order-action-btn">
                                                            Chi tiết
                                                        </a>

                                                    </div>

                                                </td>

                                            </tr>

                        @empty

                            <tr>

                                <td colspan="7" class="order-empty">

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

                            <span class="order-page-btn disabled">
                                ← Trước
                            </span>

                        @else

                            <a href="{{ $orders->previousPageUrl() }}" class="order-page-btn">
                                ← Trước
                            </a>

                        @endif


                        @if ($orders->hasMorePages())

                            <a href="{{ $orders->nextPageUrl() }}" class="order-page-btn">
                                Sau →
                            </a>

                        @else

                            <span class="order-page-btn disabled">
                                Sau →
                            </span>

                        @endif

                    </div>

                </div>

            @endif

        </div>

    </div>


    <style>
        .service-order-page {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            width: 100%;

            color: var(--text);
        }

        .service-order-page * {
            box-sizing: border-box;
        }


        /* =========================
           BREADCRUMB
        ========================= */

        .order-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;

            gap: 8px;

            margin-bottom: 18px;

            color: #94a3b8;

            font-size: 13px;
        }

        .order-breadcrumb a {
            color: #64748b;

            text-decoration: none;
        }

        .order-breadcrumb a:hover {
            color: var(--primary);
        }

        .order-breadcrumb strong {
            color: #334155;
        }


        /* =========================
           HEADER
        ========================= */

        .order-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 24px;
        }

        .order-module-label {
            margin-bottom: 7px;

            color: var(--primary);

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .08em;
        }

        .order-header h1 {
            margin: 0;

            color: #0f172a;

            font-size: 29px;
            font-weight: 750;

            line-height: 1.25;
        }

        .order-header p {
            margin: 8px 0 0;

            color: var(--muted);

            font-size: 14px;

            line-height: 1.6;
        }

        .order-policy-badge {
            display: flex;
            align-items: center;

            gap: 7px;

            padding: 8px 13px;

            border: 1px solid #bfdbfe;
            border-radius: 999px;

            background: #eff6ff;

            color: #1d4ed8;

            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        .order-policy-badge span {
            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: #2563eb;
        }


        /* =========================
           ALERT
        ========================= */

        .order-alert {
            margin-bottom: 18px;

            padding: 12px 14px;

            border-radius: 10px;

            font-size: 13px;
            font-weight: 650;

            line-height: 1.5;
        }

        .order-alert-success {
            border: 1px solid #bbf7d0;

            background: #f0fdf4;

            color: #15803d;
        }

        .order-alert-error {
            border: 1px solid #fecaca;

            background: #fef2f2;

            color: #b91c1c;
        }


        /* =========================
           CARD
        ========================= */

        .order-card {
            overflow: hidden;

            margin-bottom: 22px;

            border: 1px solid var(--border);
            border-radius: 14px;

            background: #ffffff;

            box-shadow:
                0 3px 14px rgba(15, 23, 42, .035);
        }

        .order-card-header {
            padding: 18px 20px;

            border-bottom: 1px solid var(--border);
        }

        .order-card-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 15px;
            font-weight: 750;
        }

        .order-card-header p {
            margin: 5px 0 0;

            color: var(--muted);

            font-size: 12px;

            line-height: 1.5;
        }

        .order-card-body {
            padding: 20px;
        }


        /* =========================
           FILTER
        ========================= */

        .order-filter-grid {
            display: grid;

            grid-template-columns:
                minmax(280px, 1.6fr) minmax(180px, .7fr) minmax(160px, .55fr) minmax(160px, .55fr);

            gap: 15px;
        }

        .order-form-group label {
            display: block;

            margin-bottom: 7px;

            color: #334155;

            font-size: 12px;
            font-weight: 700;
        }

        .order-control {
            display: block;

            width: 100%;
            height: 42px;

            padding: 0 12px;

            border: 1px solid #cbd5e1;
            border-radius: 9px;

            outline: none;

            background: #ffffff;

            color: #0f172a;

            font-family: inherit;
            font-size: 13px;

            transition: .2s;
        }

        .order-control:hover {
            border-color: #94a3b8;
        }

        .order-control:focus {
            border-color: var(--primary);

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, .12);
        }

        .order-filter-actions {
            display: flex;

            gap: 10px;

            margin-top: 18px;
        }

        .order-btn {
            display: inline-flex;

            height: 40px;

            align-items: center;
            justify-content: center;

            padding: 0 16px;

            border-radius: 9px;

            font-family: inherit;
            font-size: 13px;
            font-weight: 700;

            text-decoration: none;

            cursor: pointer;

            transition: .2s;
        }

        .order-btn-primary {
            border: 1px solid var(--primary);

            background: var(--primary);

            color: #ffffff;
        }

        .order-btn-primary:hover {
            border-color: var(--primary-dark);

            background: var(--primary-dark);
        }

        .order-btn-secondary {
            border: 1px solid #cbd5e1;

            background: #ffffff;

            color: #475569;
        }

        .order-btn-secondary:hover {
            background: #f8fafc;

            color: #0f172a;
        }


        /* =========================
           LIST HEADER
        ========================= */

        .order-list-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;
        }

        .order-count {
            color: var(--muted);

            font-size: 12px;

            white-space: nowrap;
        }

        .order-count strong {
            color: #0f172a;

            font-size: 14px;
        }


        /* =========================
           TABLE
        ========================= */

        .order-table-wrapper {
            width: 100%;

            overflow-x: auto;
        }

        .order-table {
            width: 100%;

            border-collapse: collapse;

            font-size: 13px;
        }

        .order-table th {
            padding: 13px 14px;

            border-bottom: 1px solid var(--border);

            background: #f8fafc;

            color: #475569;

            text-align: left;

            font-size: 11px;
            font-weight: 750;

            letter-spacing: .035em;

            text-transform: uppercase;

            white-space: nowrap;
        }

        .order-table td {
            padding: 14px;

            border-bottom: 1px solid #edf2f7;

            color: #334155;

            vertical-align: middle;
        }

        .order-table tbody tr:hover {
            background: #f8fbff;
        }

        .order-table tbody tr:last-child td {
            border-bottom: none;
        }


        /* =========================
           ORDER INFO
        ========================= */

        .order-code {
            color: #1d4ed8;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 12px;
            font-weight: 800;

            white-space: nowrap;
        }

        .order-visit-code {
            color: #334155;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        .order-doctor {
            display: grid;

            gap: 3px;
        }

        .order-doctor strong {
            color: #0f172a;

            font-size: 12px;
            font-weight: 700;
        }

        .order-doctor span {
            color: #94a3b8;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 10px;
        }

        .order-date {
            color: #475569;

            font-size: 12px;

            white-space: nowrap;
        }

        .order-item-count {
            display: inline-flex;

            min-width: 30px;
            height: 28px;

            align-items: center;
            justify-content: center;

            padding: 0 8px;

            border-radius: 8px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 11px;
            font-weight: 800;
        }

        .order-empty-value {
            color: #94a3b8;
        }


        /* =========================
           STATUS
        ========================= */

        .order-status-badge {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            min-height: 26px;

            padding: 4px 10px;

            border-radius: 999px;

            font-size: 10px;
            font-weight: 800;

            white-space: nowrap;
        }

        .status-active {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .status-completed {
            background: #dcfce7;

            color: #15803d;
        }

        .status-cancelled {
            background: #fee2e2;

            color: #b91c1c;
        }

        .status-default {
            background: #f1f5f9;

            color: #475569;
        }


        /* =========================
           ACTION
        ========================= */

        .order-actions {
            display: flex;
            align-items: center;

            gap: 8px;

            white-space: nowrap;
        }

        .order-action-btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 7px 11px;

            border: 1px solid #bfdbfe;
            border-radius: 8px;

            background: #ffffff;

            color: #2563eb;

            font-size: 12px;
            font-weight: 700;

            text-decoration: none;

            transition: .2s;
        }

        .order-action-btn:hover {
            background: #eff6ff;
        }


        /* =========================
           EMPTY
        ========================= */

        .order-empty {
            padding: 50px 20px !important;

            text-align: center;
        }

        .order-empty-icon {
            display: flex;

            width: 44px;
            height: 44px;

            align-items: center;
            justify-content: center;

            margin: 0 auto 12px;

            border-radius: 50%;

            background: #f1f5f9;

            color: #64748b;

            font-size: 18px;
            font-weight: 800;
        }

        .order-empty strong {
            display: block;

            color: #0f172a;

            font-size: 14px;
        }

        .order-empty p {
            margin: 6px 0 0;

            color: #64748b;

            font-size: 12px;
        }


        /* =========================
           PAGINATION
        ========================= */

        .order-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            padding: 15px 20px;

            border-top: 1px solid var(--border);
        }

        .order-pagination-info {
            color: #64748b;

            font-size: 12px;
        }

        .order-pagination-info strong {
            color: #0f172a;
        }

        .order-pagination-buttons {
            display: flex;

            gap: 8px;
        }

        .order-page-btn {
            display: inline-flex;

            height: 34px;

            align-items: center;
            justify-content: center;

            padding: 0 12px;

            border: 1px solid #cbd5e1;
            border-radius: 8px;

            background: #ffffff;

            color: #334155;

            font-size: 12px;
            font-weight: 650;

            text-decoration: none;
        }

        .order-page-btn:hover {
            border-color: #93c5fd;

            background: #eff6ff;

            color: #2563eb;
        }

        .order-page-btn.disabled {
            opacity: .45;

            cursor: not-allowed;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {

            .order-filter-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 768px) {

            .order-header {
                flex-direction: column;
            }

            .order-header h1 {
                font-size: 24px;
            }

            .order-filter-grid {
                grid-template-columns: 1fr;
            }

            .order-filter-actions {
                flex-direction: column;
            }

            .order-btn {
                width: 100%;
            }

            .order-list-header {
                align-items: flex-start;

                flex-direction: column;
            }

            .order-pagination {
                align-items: flex-start;

                flex-direction: column;
            }

        }
    </style>

@endsection