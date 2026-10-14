@extends('admin.layouts.app')

@section('title', 'Kết quả dịch vụ')

@section('content')

    <div class="service-result-page">

        {{-- Breadcrumb --}}
        <div class="result-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <strong>
                Kết quả dịch vụ
            </strong>

        </div>


        {{-- Header --}}
        <div class="result-header">

            <div>

                <div class="result-module-label">
                    DỊCH VỤ
                </div>

                <h1>
                    Kết quả dịch vụ
                </h1>

                <p>
                    Tra cứu kết quả của các dịch vụ đã thực hiện
                    trong quá trình khám bệnh.
                </p>

            </div>

            <div class="result-policy-badge">
                <span></span>
                Xem / Sửa có kiểm soát
            </div>

        </div>


        {{-- Flash --}}
        @if (session('success'))

            <div class="result-alert result-alert-success">
                {{ session('success') }}
            </div>

        @endif


        @if (session('error'))

            <div class="result-alert result-alert-error">
                {{ session('error') }}
            </div>

        @endif


        {{-- Filter --}}
        <div class="result-card">

            <div class="result-card-header">

                <div>

                    <h2>
                        Tìm kiếm và lọc
                    </h2>

                    <p>
                        Tìm theo phiếu chỉ định, lượt khám,
                        dịch vụ, nội dung kết quả hoặc người xác nhận.
                    </p>

                </div>

            </div>


            <div class="result-card-body">

                <form method="GET" action="{{ route('admin.service-results.index') }}">

                    <div class="result-filter-grid">

                        {{-- Search --}}
                        <div class="result-form-group result-search-group">

                            <label for="q">
                                Tìm kiếm
                            </label>

                            <input id="q" type="text" name="q" class="result-control" value="{{ request('q') }}"
                                placeholder="Mã phiếu, lượt khám, dịch vụ, kết quả...">

                        </div>


                        {{-- Verifier --}}
                        <div class="result-form-group">

                            <label for="verified_by_user_id">
                                Người xác nhận
                            </label>

                            <select id="verified_by_user_id" name="verified_by_user_id" class="result-control">

                                <option value="">
                                    Tất cả
                                </option>

                                @foreach ($verifiers as $verifier)

                                    <option value="{{ $verifier->id }}" @selected(
                                        (string) request('verified_by_user_id')
                                        ===
                                        (string) $verifier->id
                                    )>
                                        {{ $verifier->business_code }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- From date --}}
                        <div class="result-form-group">

                            <label for="date_from">
                                Từ ngày
                            </label>

                            <input id="date_from" type="date" name="date_from" class="result-control"
                                value="{{ request('date_from') }}">

                        </div>


                        {{-- To date --}}
                        <div class="result-form-group">

                            <label for="date_to">
                                Đến ngày
                            </label>

                            <input id="date_to" type="date" name="date_to" class="result-control"
                                value="{{ request('date_to') }}">

                        </div>

                    </div>


                    <div class="result-filter-actions">

                        <button type="submit" class="result-btn result-btn-primary">
                            Lọc dữ liệu
                        </button>

                        <a href="{{ route('admin.service-results.index') }}" class="result-btn result-btn-secondary">
                            Xóa bộ lọc
                        </a>

                    </div>

                </form>

            </div>

        </div>


        {{-- Result list --}}
        <div class="result-card">

            <div class="result-card-header result-list-header">

                <div>

                    <h2>
                        Danh sách kết quả dịch vụ
                    </h2>

                    <p>
                        Kết quả được sắp xếp theo thời gian trả kết quả mới nhất.
                    </p>

                </div>


                <div class="result-count">

                    Tổng cộng

                    <strong>
                        {{ $results->total() }}
                    </strong>

                    kết quả

                </div>

            </div>


            <div class="result-table-wrapper">

                <table class="result-table">

                    <thead>

                        <tr>

                            <th>
                                Phiếu chỉ định
                            </th>

                            <th>
                                Lượt khám
                            </th>

                            <th>
                                Dịch vụ
                            </th>

                            <th>
                                Tóm tắt kết quả
                            </th>

                            <th>
                                Kết luận
                            </th>

                            <th>
                                Người xác nhận
                            </th>

                            <th>
                                Thời gian trả
                            </th>

                            <th>
                                Thao tác
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($results as $result)

                                            @php
                                                $item = $result->serviceOrderItem;
                                                $order = $item?->serviceOrder;
                                                $visit = $order?->medicalVisit;
                                                $service = $item?->medicalService;
                                            @endphp

                                            <tr>

                                                {{-- Order --}}
                                                <td>

                                                    @if ($order)

                                                        <div class="result-code">
                                                            {{ $order->service_order_code }}
                                                        </div>

                                                    @else

                                                        <span class="result-empty-value">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Visit --}}
                                                <td>

                                                    @if ($visit)

                                                        <div class="result-visit-code">
                                                            {{ $visit->visit_code }}
                                                        </div>

                                                    @else

                                                        <span class="result-empty-value">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Service --}}
                                                <td>

                                                    @if ($service)

                                                        <div class="result-service">

                                                            <strong>
                                                                {{ $service->name }}
                                                            </strong>

                                                            <span>
                                                                {{ $service->service_code }}
                                                            </span>

                                                        </div>

                                                    @else

                                                        <span class="result-empty-value">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Summary --}}
                                                <td>

                                                    <div class="result-text result-text-summary">

                                                        {{ \Illuminate\Support\Str::limit(
                                $result->result_summary ?? '—',
                                80
                            ) }}

                                                    </div>

                                                </td>


                                                {{-- Conclusion --}}
                                                <td>

                                                    <div class="result-text">

                                                        {{ \Illuminate\Support\Str::limit(
                                $result->result_conclusion ?? '—',
                                80
                            ) }}

                                                    </div>

                                                </td>


                                                {{-- Verifier --}}
                                                <td>

                                                    @if ($result->verifier)

                                                        <span class="result-verifier">
                                                            {{ $result->verifier->business_code }}
                                                        </span>

                                                    @else

                                                        <span class="result-not-verified">
                                                            Chưa xác nhận
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Resulted at --}}
                                                <td>

                                                    <div class="result-date">

                                                        @if ($result->resulted_at)

                                                                                    {{ $result->resulted_at->format(
                                                                'd/m/Y H:i'
                                                            ) }}

                                                        @else

                                                            —

                                                        @endif

                                                    </div>

                                                </td>


                                                {{-- Action --}}
                                                <td>

                                                    <div class="result-actions">

                                                        <a href="{{ route(
                                'admin.service-results.show',
                                $result->id
                            ) }}" class="result-action-btn">
                                                            Chi tiết
                                                        </a>

                                                    </div>

                                                </td>

                                            </tr>

                        @empty

                            <tr>

                                <td colspan="8" class="result-empty">

                                    <div class="result-empty-icon">
                                        !
                                    </div>

                                    <strong>
                                        Không tìm thấy kết quả dịch vụ
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
            @if ($results->hasPages())

                <div class="result-pagination">

                    <div class="result-pagination-info">

                        Trang

                        <strong>
                            {{ $results->currentPage() }}
                        </strong>

                        /

                        {{ $results->lastPage() }}

                    </div>


                    <div class="result-pagination-buttons">

                        @if ($results->onFirstPage())

                            <span class="result-page-btn disabled">
                                ← Trước
                            </span>

                        @else

                            <a href="{{ $results->previousPageUrl() }}" class="result-page-btn">
                                ← Trước
                            </a>

                        @endif


                        @if ($results->hasMorePages())

                            <a href="{{ $results->nextPageUrl() }}" class="result-page-btn">
                                Sau →
                            </a>

                        @else

                            <span class="result-page-btn disabled">
                                Sau →
                            </span>

                        @endif

                    </div>

                </div>

            @endif

        </div>


        {{-- Policy --}}
        <div class="result-policy-box">

            <div class="result-policy-icon">
                !
            </div>

            <div>

                <strong>
                    Chính sách dữ liệu
                </strong>

                <p>
                    Kết quả dịch vụ đã được xác nhận không được hard-delete.
                    Các chỉnh sửa nghiệp vụ phải được thực hiện có kiểm soát.
                </p>

            </div>

        </div>

    </div>


    <style>
        .service-result-page {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            width: 100%;

            color: var(--text);
        }

        .service-result-page * {
            box-sizing: border-box;
        }


        /* =========================
           BREADCRUMB
        ========================= */

        .result-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;

            gap: 8px;

            margin-bottom: 18px;

            color: #94a3b8;

            font-size: 13px;
        }

        .result-breadcrumb a {
            color: #64748b;

            text-decoration: none;
        }

        .result-breadcrumb a:hover {
            color: var(--primary);
        }

        .result-breadcrumb strong {
            color: #334155;
        }


        /* =========================
           HEADER
        ========================= */

        .result-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 24px;
        }

        .result-module-label {
            margin-bottom: 7px;

            color: var(--primary);

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .08em;
        }

        .result-header h1 {
            margin: 0;

            color: #0f172a;

            font-size: 29px;
            font-weight: 750;

            line-height: 1.25;
        }

        .result-header p {
            margin: 8px 0 0;

            color: var(--muted);

            font-size: 14px;

            line-height: 1.6;
        }

        .result-policy-badge {
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

        .result-policy-badge span {
            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: #2563eb;
        }


        /* =========================
           ALERT
        ========================= */

        .result-alert {
            margin-bottom: 18px;

            padding: 12px 14px;

            border-radius: 10px;

            font-size: 13px;
            font-weight: 650;
        }

        .result-alert-success {
            border: 1px solid #bbf7d0;

            background: #f0fdf4;

            color: #15803d;
        }

        .result-alert-error {
            border: 1px solid #fecaca;

            background: #fef2f2;

            color: #b91c1c;
        }


        /* =========================
           CARD
        ========================= */

        .result-card {
            overflow: hidden;

            margin-bottom: 22px;

            border: 1px solid var(--border);
            border-radius: 14px;

            background: #ffffff;

            box-shadow:
                0 3px 14px rgba(15, 23, 42, .035);
        }

        .result-card-header {
            padding: 18px 20px;

            border-bottom: 1px solid var(--border);
        }

        .result-card-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 15px;
            font-weight: 750;
        }

        .result-card-header p {
            margin: 5px 0 0;

            color: var(--muted);

            font-size: 12px;

            line-height: 1.5;
        }

        .result-card-body {
            padding: 20px;
        }


        /* =========================
           FILTER
        ========================= */

        .result-filter-grid {
            display: grid;

            grid-template-columns:
                minmax(300px, 1.5fr) minmax(170px, .65fr) minmax(160px, .55fr) minmax(160px, .55fr);

            gap: 15px;
        }

        .result-form-group label {
            display: block;

            margin-bottom: 7px;

            color: #334155;

            font-size: 12px;
            font-weight: 700;
        }

        .result-control {
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

        .result-control:hover {
            border-color: #94a3b8;
        }

        .result-control:focus {
            border-color: var(--primary);

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, .12);
        }

        .result-filter-actions {
            display: flex;

            gap: 10px;

            margin-top: 18px;
        }

        .result-btn {
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
        }

        .result-btn-primary {
            border: 1px solid var(--primary);

            background: var(--primary);

            color: #ffffff;
        }

        .result-btn-primary:hover {
            background: var(--primary-dark);
        }

        .result-btn-secondary {
            border: 1px solid #cbd5e1;

            background: #ffffff;

            color: #475569;
        }


        /* =========================
           LIST HEADER
        ========================= */

        .result-list-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;
        }

        .result-count {
            color: var(--muted);

            font-size: 12px;

            white-space: nowrap;
        }

        .result-count strong {
            color: #0f172a;

            font-size: 14px;
        }


        /* =========================
           TABLE
        ========================= */

        .result-table-wrapper {
            width: 100%;

            overflow-x: auto;
        }

        .result-table {
            width: 100%;

            border-collapse: collapse;

            font-size: 13px;
        }

        .result-table th {
            padding: 13px 14px;

            border-bottom: 1px solid var(--border);

            background: #f8fafc;

            color: #475569;

            text-align: left;

            font-size: 11px;
            font-weight: 750;

            text-transform: uppercase;

            letter-spacing: .035em;

            white-space: nowrap;
        }

        .result-table td {
            padding: 14px;

            border-bottom: 1px solid #edf2f7;

            color: #334155;

            vertical-align: middle;
        }

        .result-table tbody tr:hover {
            background: #f8fbff;
        }

        .result-table tbody tr:last-child td {
            border-bottom: none;
        }


        /* =========================
           DATA
        ========================= */

        .result-code,
        .result-visit-code {
            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 11px;
            font-weight: 750;

            white-space: nowrap;
        }

        .result-code {
            color: #1d4ed8;
        }

        .result-visit-code {
            color: #475569;
        }

        .result-service {
            display: grid;

            min-width: 180px;

            gap: 3px;
        }

        .result-service strong {
            color: #0f172a;

            font-size: 12px;
            font-weight: 700;
        }

        .result-service span {
            color: #94a3b8;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 10px;
        }

        .result-text {
            max-width: 260px;

            color: #475569;

            font-size: 12px;

            line-height: 1.55;
        }

        .result-text-summary {
            color: #334155;

            font-weight: 600;
        }

        .result-verifier {
            display: inline-flex;

            padding: 5px 8px;

            border-radius: 7px;

            background: #eff6ff;

            color: #1d4ed8;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 10px;
            font-weight: 750;

            white-space: nowrap;
        }

        .result-not-verified {
            color: #b45309;

            font-size: 10px;
            font-weight: 700;

            white-space: nowrap;
        }

        .result-date {
            color: #475569;

            font-size: 11px;

            white-space: nowrap;
        }

        .result-empty-value {
            color: #94a3b8;
        }


        /* =========================
           ACTION
        ========================= */

        .result-actions {
            display: flex;

            align-items: center;

            gap: 8px;

            white-space: nowrap;
        }

        .result-action-btn {
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
        }

        .result-action-btn:hover {
            background: #eff6ff;
        }


        /* =========================
           EMPTY
        ========================= */

        .result-empty {
            padding: 50px 20px !important;

            text-align: center;
        }

        .result-empty-icon {
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

        .result-empty strong {
            display: block;

            color: #0f172a;

            font-size: 14px;
        }

        .result-empty p {
            margin: 6px 0 0;

            color: #64748b;

            font-size: 12px;
        }


        /* =========================
           PAGINATION
        ========================= */

        .result-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            padding: 15px 20px;

            border-top: 1px solid var(--border);
        }

        .result-pagination-info {
            color: #64748b;

            font-size: 12px;
        }

        .result-pagination-info strong {
            color: #0f172a;
        }

        .result-pagination-buttons {
            display: flex;

            gap: 8px;
        }

        .result-page-btn {
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

        .result-page-btn:hover {
            border-color: #93c5fd;

            background: #eff6ff;

            color: #2563eb;
        }

        .result-page-btn.disabled {
            opacity: .45;

            cursor: not-allowed;
        }


        /* =========================
           POLICY
        ========================= */

        .result-policy-box {
            display: flex;
            align-items: flex-start;

            gap: 12px;

            padding: 16px;

            border: 1px solid #fde68a;
            border-radius: 12px;

            background: #fffbeb;
        }

        .result-policy-icon {
            display: flex;

            width: 30px;
            height: 30px;

            flex: 0 0 30px;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #f59e0b;

            color: #ffffff;

            font-size: 12px;
            font-weight: 800;
        }

        .result-policy-box strong {
            display: block;

            margin-bottom: 5px;

            color: #92400e;

            font-size: 12px;
        }

        .result-policy-box p {
            margin: 0;

            color: #78350f;

            font-size: 11px;

            line-height: 1.65;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {

            .result-filter-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 768px) {

            .result-header {
                flex-direction: column;
            }

            .result-header h1 {
                font-size: 24px;
            }

            .result-filter-grid {
                grid-template-columns: 1fr;
            }

            .result-filter-actions {
                flex-direction: column;
            }

            .result-btn {
                width: 100%;
            }

            .result-list-header {
                align-items: flex-start;

                flex-direction: column;
            }

            .result-pagination {
                align-items: flex-start;

                flex-direction: column;
            }

        }
    </style>

@endsection