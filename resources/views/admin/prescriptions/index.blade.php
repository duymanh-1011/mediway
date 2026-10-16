@extends('admin.layouts.app')

@section('title', 'Đơn thuốc')

@section('content')

    <div class="prescription-page">

        {{-- =====================================================
        BREADCRUMB
        ====================================================== --}}

        <div class="prescription-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <strong>
                Đơn thuốc
            </strong>

        </div>


        {{-- =====================================================
        HEADER
        ====================================================== --}}

        <div class="prescription-header">

            <div>

                <div class="prescription-module-label">
                    ĐƠN THUỐC
                </div>

                <h1>
                    Danh sách đơn thuốc
                </h1>

                <p>
                    Theo dõi đơn thuốc đã kê theo lượt khám,
                    bác sĩ, thời gian và trạng thái.
                </p>

            </div>

            <div class="prescription-header-badge">
                Xem / Sửa có kiểm soát
            </div>

        </div>


        {{-- =====================================================
        FLASH MESSAGE
        ====================================================== --}}

        @if (session('success'))

            <div class="prescription-alert prescription-alert-success">
                {{ session('success') }}
            </div>

        @endif


        @if (session('error'))

            <div class="prescription-alert prescription-alert-error">
                {{ session('error') }}
            </div>

        @endif


        {{-- =====================================================
        FILTER
        ====================================================== --}}

        <div class="prescription-card prescription-filter-card">

            <form method="GET" action="{{ route('admin.prescriptions.index') }}" class="prescription-filter-form">

                {{-- Search --}}
                <div class="prescription-form-group prescription-search-group">

                    <label for="q">
                        Tìm kiếm
                    </label>

                    <input id="q" type="text" name="q" value="{{ request('q') }}" class="prescription-control"
                        placeholder="Mã đơn, lượt khám, mã hoặc tên bác sĩ...">

                </div>


                {{-- Status --}}
                <div class="prescription-form-group">

                    <label for="status">
                        Trạng thái
                    </label>

                    <select id="status" name="status" class="prescription-control">

                        <option value="">
                            Tất cả
                        </option>

                        @foreach ($statuses as $status)

                            <option value="{{ $status }}" @selected(
                                request('status') === $status
                            )>
                                {{ $status }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- Date from --}}
                <div class="prescription-form-group">

                    <label for="date_from">
                        Từ ngày
                    </label>

                    <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}"
                        class="prescription-control">

                </div>


                {{-- Date to --}}
                <div class="prescription-form-group">

                    <label for="date_to">
                        Đến ngày
                    </label>

                    <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}"
                        class="prescription-control">

                </div>


                {{-- Buttons --}}
                <div class="prescription-filter-actions">

                    <button type="submit" class="prescription-btn prescription-btn-primary">
                        Lọc dữ liệu
                    </button>

                    <a href="{{ route('admin.prescriptions.index') }}" class="prescription-btn prescription-btn-secondary">
                        Đặt lại
                    </a>

                </div>

            </form>

        </div>


        {{-- =====================================================
        TABLE
        ====================================================== --}}

        <div class="prescription-card">

            <div class="prescription-table-header">

                <div>

                    <h2>
                        Đơn thuốc
                    </h2>

                    <p>
                        Tổng cộng {{ $prescriptions->total() }} bản ghi
                    </p>

                </div>

            </div>


            <div class="prescription-table-wrapper">

                <table class="prescription-table">

                    <thead>

                        <tr>

                            <th>
                                Mã đơn
                            </th>

                            <th>
                                Lượt khám
                            </th>

                            <th>
                                Bác sĩ kê đơn
                            </th>

                            <th>
                                Thời gian kê
                            </th>

                            <th class="text-center">
                                Số thuốc
                            </th>

                            <th>
                                Hướng dẫn chung
                            </th>

                            <th>
                                Trạng thái
                            </th>

                            <th class="text-right">
                                Thao tác
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($prescriptions as $prescription)

                                            <tr>

                                                {{-- Prescription code --}}
                                                <td>

                                                    <div class="prescription-code">
                                                        {{ $prescription->prescription_code }}
                                                    </div>

                                                </td>


                                                {{-- Visit --}}
                                                <td>

                                                    @if ($prescription->visit_code)

                                                        <span class="prescription-secondary-code">
                                                            {{ $prescription->visit_code }}
                                                        </span>

                                                    @else

                                                        <span class="prescription-empty">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Doctor --}}
                                                <td>

                                                    @if (
                                                            $prescription->doctor_code
                                                            ||
                                                            $prescription->doctor_name
                                                        )

                                                        <div class="prescription-doctor">

                                                            <strong>
                                                                {{ $prescription->doctor_name ?? '—' }}
                                                            </strong>

                                                            @if ($prescription->doctor_code)

                                                                <span>
                                                                    {{ $prescription->doctor_code }}
                                                                </span>

                                                            @endif

                                                        </div>

                                                    @else

                                                        <span class="prescription-empty">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Prescribed at --}}
                                                <td>

                                                    @if ($prescription->prescribed_at)

                                                        <div class="prescription-time">

                                                            <strong>
                                                                {{ $prescription->prescribed_at->format('d/m/Y') }}
                                                            </strong>

                                                            <span>
                                                                {{ $prescription->prescribed_at->format('H:i') }}
                                                            </span>

                                                        </div>

                                                    @else

                                                        <span class="prescription-empty">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Item count --}}
                                                <td class="text-center">

                                                    <span class="prescription-count">
                                                        {{ $prescription->items_count }}
                                                    </span>

                                                </td>


                                                {{-- General instructions --}}
                                                <td>

                                                    @if ($prescription->general_instructions)

                                                                        <div class="prescription-instruction">
                                                                            {{
                                                        \Illuminate\Support\Str::limit(
                                                            $prescription->general_instructions,
                                                            70
                                                        )
                                                                                    }}
                                                                        </div>

                                                    @else

                                                        <span class="prescription-empty">
                                                            —
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Status --}}
                                                <td>

                                                    @php
                                                        $statusClass = match (
                                                        strtoupper(
                                                            $prescription->status ?? ''
                                                        )
                                                        ) {
                                                            'ACTIVE'
                                                            => 'active',

                                                            'COMPLETED'
                                                            => 'completed',

                                                            'CANCELLED'
                                                            => 'cancelled',

                                                            default
                                                            => 'default',
                                                        };
                                                    @endphp

                                                    <span class="
                                                                prescription-status
                                                                {{ $statusClass }}
                                                            ">
                                                        {{ $prescription->status ?? '—' }}
                                                    </span>

                                                </td>


                                                {{-- Action --}}
                                                <td class="text-right">

                                                    <a href="{{ route(
                                'admin.prescriptions.show',
                                $prescription->id
                            ) }}" class="prescription-detail-link">
                                                        Chi tiết
                                                    </a>

                                                </td>

                                            </tr>

                        @empty

                            <tr>

                                <td colspan="8" class="prescription-empty-state">

                                    <div class="prescription-empty-icon">
                                        Rx
                                    </div>

                                    <strong>
                                        Chưa có đơn thuốc phù hợp
                                    </strong>

                                    <p>
                                        Không tìm thấy dữ liệu theo
                                        điều kiện tìm kiếm hiện tại.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- =====================================================
            PAGINATION
            ====================================================== --}}

            @if ($prescriptions->hasPages())

                <div class="prescription-pagination">

                    <div class="prescription-pagination-info">

                        Hiển thị

                        <strong>
                            {{ $prescriptions->firstItem() }}
                        </strong>

                        -

                        <strong>
                            {{ $prescriptions->lastItem() }}
                        </strong>

                        trên

                        <strong>
                            {{ $prescriptions->total() }}
                        </strong>

                        bản ghi

                    </div>


                    <div class="prescription-pagination-actions">

                        @if ($prescriptions->onFirstPage())

                            <span class="
                                            prescription-page-btn
                                            disabled
                                        ">
                                ← Trước
                            </span>

                        @else

                            <a href="{{ $prescriptions->previousPageUrl() }}" class="prescription-page-btn">
                                ← Trước
                            </a>

                        @endif


                        <span class="prescription-page-current">

                            Trang
                            {{ $prescriptions->currentPage() }}
                            /
                            {{ $prescriptions->lastPage() }}

                        </span>


                        @if ($prescriptions->hasMorePages())

                            <a href="{{ $prescriptions->nextPageUrl() }}" class="prescription-page-btn">
                                Sau →
                            </a>

                        @else

                            <span class="
                                            prescription-page-btn
                                            disabled
                                        ">
                                Sau →
                            </span>

                        @endif

                    </div>

                </div>

            @endif

        </div>

    </div>


    <style>
        .prescription-page {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            width: 100%;

            color: var(--text);
        }

        .prescription-page * {
            box-sizing: border-box;
        }


        /* =====================================================
           BREADCRUMB
        ====================================================== */

        .prescription-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;

            gap: 8px;

            margin-bottom: 18px;

            color: #94a3b8;

            font-size: 13px;
        }

        .prescription-breadcrumb a {
            color: #64748b;

            text-decoration: none;
        }

        .prescription-breadcrumb a:hover {
            color: var(--primary);
        }

        .prescription-breadcrumb strong {
            color: #334155;
        }


        /* =====================================================
           HEADER
        ====================================================== */

        .prescription-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 24px;
        }

        .prescription-module-label {
            margin-bottom: 7px;

            color: var(--primary);

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .08em;
        }

        .prescription-header h1 {
            margin: 0;

            color: #0f172a;

            font-size: 29px;
            font-weight: 750;
        }

        .prescription-header p {
            margin: 8px 0 0;

            color: #64748b;

            font-size: 14px;

            line-height: 1.6;
        }

        .prescription-header-badge {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 8px 13px;

            border: 1px solid #bfdbfe;
            border-radius: 999px;

            background: #eff6ff;

            color: #1d4ed8;

            font-size: 11px;
            font-weight: 750;

            white-space: nowrap;
        }


        /* =====================================================
           ALERT
        ====================================================== */

        .prescription-alert {
            margin-bottom: 18px;

            padding: 13px 15px;

            border-radius: 10px;

            font-size: 12px;
            font-weight: 650;
        }

        .prescription-alert-success {
            border: 1px solid #bbf7d0;

            background: #f0fdf4;

            color: #15803d;
        }

        .prescription-alert-error {
            border: 1px solid #fecaca;

            background: #fef2f2;

            color: #b91c1c;
        }


        /* =====================================================
           CARD
        ====================================================== */

        .prescription-card {
            overflow: hidden;

            margin-bottom: 22px;

            border: 1px solid var(--border);
            border-radius: 14px;

            background: #ffffff;

            box-shadow:
                0 3px 14px rgba(15, 23, 42, .035);
        }


        /* =====================================================
           FILTER
        ====================================================== */

        .prescription-filter-card {
            padding: 18px;
        }

        .prescription-filter-form {
            display: grid;

            grid-template-columns:
                minmax(220px, 2fr) minmax(150px, .8fr) minmax(145px, .7fr) minmax(145px, .7fr) auto;

            gap: 13px;

            align-items: end;
        }

        .prescription-form-group {
            min-width: 0;
        }

        .prescription-form-group label {
            display: block;

            margin-bottom: 7px;

            color: #475569;

            font-size: 11px;
            font-weight: 700;
        }

        .prescription-control {
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
            font-size: 12px;

            transition: .2s;
        }

        .prescription-control:focus {
            border-color: var(--primary);

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, .1);
        }

        .prescription-filter-actions {
            display: flex;

            gap: 8px;
        }


        /* =====================================================
           BUTTONS
        ====================================================== */

        .prescription-btn {
            display: inline-flex;

            height: 40px;

            align-items: center;
            justify-content: center;

            padding: 0 14px;

            border-radius: 8px;

            font-family: inherit;
            font-size: 11px;
            font-weight: 700;

            text-decoration: none;

            cursor: pointer;

            white-space: nowrap;
        }

        .prescription-btn-primary {
            border: 1px solid var(--primary);

            background: var(--primary);

            color: #ffffff;
        }

        .prescription-btn-primary:hover {
            background: var(--primary-dark);
        }

        .prescription-btn-secondary {
            border: 1px solid #cbd5e1;

            background: #ffffff;

            color: #475569;
        }

        .prescription-btn-secondary:hover {
            background: #f8fafc;
        }


        /* =====================================================
           TABLE HEADER
        ====================================================== */

        .prescription-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            padding: 17px 20px;

            border-bottom: 1px solid var(--border);
        }

        .prescription-table-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 15px;
            font-weight: 750;
        }

        .prescription-table-header p {
            margin: 4px 0 0;

            color: #94a3b8;

            font-size: 10px;
        }


        /* =====================================================
           TABLE
        ====================================================== */

        .prescription-table-wrapper {
            overflow-x: auto;
        }

        .prescription-table {
            width: 100%;

            border-collapse: collapse;

            min-width: 1050px;
        }

        .prescription-table th {
            padding: 12px 15px;

            border-bottom: 1px solid #e2e8f0;

            background: #f8fafc;

            color: #64748b;

            font-size: 10px;
            font-weight: 800;

            letter-spacing: .035em;

            text-align: left;

            text-transform: uppercase;

            white-space: nowrap;
        }

        .prescription-table td {
            padding: 14px 15px;

            border-bottom: 1px solid #edf2f7;

            color: #334155;

            font-size: 11px;

            vertical-align: middle;
        }

        .prescription-table tbody tr:last-child td {
            border-bottom: none;
        }

        .prescription-table tbody tr:hover {
            background: #fafcff;
        }

        .text-center {
            text-align: center !important;
        }

        .text-right {
            text-align: right !important;
        }


        /* =====================================================
           DATA
        ====================================================== */

        .prescription-code {
            color: #1d4ed8;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 11px;
            font-weight: 800;
        }

        .prescription-secondary-code {
            color: #475569;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 10px;
            font-weight: 700;
        }

        .prescription-doctor {
            display: grid;

            gap: 3px;
        }

        .prescription-doctor strong {
            color: #0f172a;

            font-size: 11px;
            font-weight: 700;
        }

        .prescription-doctor span {
            color: #94a3b8;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 9px;
        }

        .prescription-time {
            display: grid;

            gap: 2px;
        }

        .prescription-time strong {
            color: #334155;

            font-size: 10px;
        }

        .prescription-time span {
            color: #94a3b8;

            font-size: 10px;
        }

        .prescription-count {
            display: inline-flex;

            min-width: 27px;
            height: 27px;

            align-items: center;
            justify-content: center;

            padding: 0 7px;

            border-radius: 999px;

            background: #f1f5f9;

            color: #334155;

            font-size: 10px;
            font-weight: 800;
        }

        .prescription-instruction {
            max-width: 240px;

            color: #475569;

            font-size: 10px;

            line-height: 1.55;
        }

        .prescription-empty {
            color: #94a3b8;
        }


        /* =====================================================
           STATUS
        ====================================================== */

        .prescription-status {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            min-width: 70px;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 9px;
            font-weight: 800;
        }

        .prescription-status.active {
            background: #dcfce7;

            color: #15803d;
        }

        .prescription-status.completed {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .prescription-status.cancelled {
            background: #fee2e2;

            color: #b91c1c;
        }

        .prescription-status.default {
            background: #f1f5f9;

            color: #475569;
        }


        /* =====================================================
           ACTION
        ====================================================== */

        .prescription-detail-link {
            display: inline-flex;

            height: 31px;

            align-items: center;
            justify-content: center;

            padding: 0 11px;

            border: 1px solid #bfdbfe;
            border-radius: 7px;

            background: #eff6ff;

            color: #1d4ed8;

            font-size: 10px;
            font-weight: 750;

            text-decoration: none;

            white-space: nowrap;
        }

        .prescription-detail-link:hover {
            border-color: #93c5fd;

            background: #dbeafe;
        }


        /* =====================================================
           EMPTY STATE
        ====================================================== */

        .prescription-empty-state {
            padding: 55px 20px !important;

            text-align: center !important;
        }

        .prescription-empty-icon {
            display: flex;

            width: 48px;
            height: 48px;

            align-items: center;
            justify-content: center;

            margin: 0 auto 12px;

            border-radius: 13px;

            background: #eff6ff;

            color: #2563eb;

            font-family:
                Georgia,
                serif;

            font-size: 17px;
            font-weight: 800;
        }

        .prescription-empty-state strong {
            display: block;

            margin-bottom: 5px;

            color: #334155;

            font-size: 12px;
        }

        .prescription-empty-state p {
            margin: 0;

            color: #94a3b8;

            font-size: 10px;
        }


        /* =====================================================
           PAGINATION
        ====================================================== */

        .prescription-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            padding: 15px 20px;

            border-top: 1px solid #e2e8f0;

            background: #ffffff;
        }

        .prescription-pagination-info {
            color: #64748b;

            font-size: 10px;
        }

        .prescription-pagination-actions {
            display: flex;
            align-items: center;

            gap: 7px;
        }

        .prescription-page-btn {
            display: inline-flex;

            height: 32px;

            align-items: center;
            justify-content: center;

            padding: 0 11px;

            border: 1px solid #cbd5e1;
            border-radius: 7px;

            background: #ffffff;

            color: #475569;

            font-size: 10px;
            font-weight: 650;

            text-decoration: none;
        }

        .prescription-page-btn:hover {
            border-color: #93c5fd;

            color: #2563eb;
        }

        .prescription-page-btn.disabled {
            opacity: .45;

            cursor: not-allowed;
        }

        .prescription-page-current {
            padding: 0 4px;

            color: #64748b;

            font-size: 10px;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1150px) {

            .prescription-filter-form {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .prescription-search-group {
                grid-column: span 2;
            }

            .prescription-filter-actions {
                grid-column: span 2;

                justify-content: flex-end;
            }

        }


        @media (max-width: 768px) {

            .prescription-header {
                flex-direction: column;
            }

            .prescription-header h1 {
                font-size: 24px;
            }

            .prescription-filter-form {
                grid-template-columns: 1fr;
            }

            .prescription-search-group,
            .prescription-filter-actions {
                grid-column: span 1;
            }

            .prescription-filter-actions {
                flex-direction: column;
            }

            .prescription-btn {
                width: 100%;
            }

            .prescription-pagination {
                flex-direction: column;

                align-items: stretch;
            }

            .prescription-pagination-actions {
                justify-content: space-between;
            }

        }
    </style>

@endsection