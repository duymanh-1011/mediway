@extends('admin.layouts.app')

@section('title', 'Trạng thái dịch vụ')

@section('content')

    <div class="status-page">

        {{-- Breadcrumb --}}
        <div class="status-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <strong>
                Trạng thái dịch vụ
            </strong>

        </div>


        {{-- Header --}}
        <div class="status-header">

            <div>

                <div class="status-module-label">
                    CẤU HÌNH TRẠNG THÁI
                </div>

                <h1>
                    Trạng thái dịch vụ
                </h1>

                <p>
                    Quản lý cấu hình luồng xử lý và trạng thái sử dụng
                    của các chỉ định dịch vụ.
                </p>

            </div>

            <div class="status-policy">
                <span></span>
                Xem / Sửa cấu hình
            </div>

        </div>


        {{-- Filter --}}
        <div class="status-card">

            <div class="status-card-header">

                <div>

                    <h2>
                        Tìm kiếm và lọc
                    </h2>

                    <p>
                        Tìm theo tên, mô tả hoặc lọc theo trạng thái sử dụng.
                    </p>

                </div>

            </div>


            <div class="status-card-body">

                <form method="GET" action="{{ route('admin.service-statuses.index') }}">

                    <div class="status-filter-grid">

                        {{-- Search --}}
                        <div class="status-form-group">

                            <label for="q">
                                Tìm kiếm
                            </label>

                            <input id="q" type="text" name="q" class="status-control" value="{{ request('q') }}"
                                placeholder="Tên trạng thái hoặc mô tả...">

                        </div>


                        {{-- Usage Status --}}
                        <div class="status-form-group">

                            <label for="is_active">
                                Trạng thái sử dụng
                            </label>

                            <select id="is_active" name="is_active" class="status-control">

                                <option value="">
                                    Tất cả
                                </option>

                                <option value="1" @selected(request('is_active') === '1')>
                                    Đang sử dụng
                                </option>

                                <option value="0" @selected(request('is_active') === '0')>
                                    Ngừng sử dụng
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="status-filter-actions">

                        <button type="submit" class="status-btn status-btn-primary">
                            Lọc dữ liệu
                        </button>

                        <a href="{{ route('admin.service-statuses.index') }}" class="status-btn status-btn-secondary">
                            Xóa bộ lọc
                        </a>

                    </div>

                </form>

            </div>

        </div>


        {{-- Table --}}
        <div class="status-card">

            <div class="status-card-header status-list-header">

                <div>

                    <h2>
                        Danh sách trạng thái dịch vụ
                    </h2>

                    <p>
                        Các trạng thái được sắp xếp theo thứ tự xử lý.
                    </p>

                </div>


                <div class="status-count">

                    Tổng cộng

                    <strong>
                        {{ $statuses->total() }}
                    </strong>

                    trạng thái

                </div>

            </div>


            <div class="status-table-wrapper">

                <table class="status-table">

                    <thead>

                        <tr>

                            <th>
                                Thứ tự
                            </th>

                            <th>
                                Tên trạng thái
                            </th>

                            <th>
                                Mô tả
                            </th>

                            <th>
                                Cho phép chuyển tiếp
                            </th>

                            <th>
                                Trạng thái sử dụng
                            </th>

                            <th>
                                Thao tác
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($statuses as $status)

                                            <tr>

                                                {{-- Process Order --}}
                                                <td>

                                                    <div class="status-order">
                                                        {{ $status->process_order }}
                                                    </div>

                                                </td>


                                                {{-- Name --}}
                                                <td>

                                                    <div class="status-name">
                                                        {{ $status->name }}
                                                    </div>

                                                </td>


                                                {{-- Description --}}
                                                <td>

                                                    <div class="status-description">
                                                        {{ $status->description ?? '—' }}
                                                    </div>

                                                </td>


                                                {{-- Allows Transition --}}
                                                <td>

                                                    @if ($status->allows_transition)

                                                        <span class="status-badge badge-yes">
                                                            Cho phép
                                                        </span>

                                                    @else

                                                        <span class="status-badge badge-no">
                                                            Không cho phép
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Usage Status --}}
                                                <td>

                                                    @if ($status->is_active)

                                                        <span class="status-badge badge-active">
                                                            Đang sử dụng
                                                        </span>

                                                    @else

                                                        <span class="status-badge badge-inactive">
                                                            Ngừng sử dụng
                                                        </span>

                                                    @endif

                                                </td>


                                                {{-- Actions --}}
                                                <td>

                                                    <div class="status-actions">

                                                        <a href="{{ route(
                                'admin.service-statuses.show',
                                $status->id
                            ) }}" class="status-action-btn action-view">
                                                            Chi tiết
                                                        </a>

                                                        <a href="{{ route(
                                'admin.service-statuses.edit',
                                $status->id
                            ) }}" class="status-action-btn action-edit">
                                                            Sửa
                                                        </a>

                                                    </div>

                                                </td>

                                            </tr>

                        @empty

                            <tr>

                                <td colspan="6" class="status-empty">

                                    <div class="status-empty-icon">
                                        !
                                    </div>

                                    <strong>
                                        Không tìm thấy trạng thái
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
            @if ($statuses->hasPages())

                <div class="status-pagination">

                    <div class="status-pagination-info">

                        Trang

                        <strong>
                            {{ $statuses->currentPage() }}
                        </strong>

                        /

                        {{ $statuses->lastPage() }}

                    </div>


                    <div class="status-pagination-buttons">

                        @if ($statuses->onFirstPage())

                            <span class="status-page-btn disabled">
                                ← Trước
                            </span>

                        @else

                            <a href="{{ $statuses->previousPageUrl() }}" class="status-page-btn">
                                ← Trước
                            </a>

                        @endif


                        @if ($statuses->hasMorePages())

                            <a href="{{ $statuses->nextPageUrl() }}" class="status-page-btn">
                                Sau →
                            </a>

                        @else

                            <span class="status-page-btn disabled">
                                Sau →
                            </span>

                        @endif

                    </div>

                </div>

            @endif

        </div>

    </div>


    <style>
        .status-page {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #eff6ff;

            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            width: 100%;

            color: var(--text);
        }

        .status-page * {
            box-sizing: border-box;
        }


        /* =========================
           BREADCRUMB
        ========================= */

        .status-breadcrumb {
            display: flex;
            align-items: center;

            gap: 8px;

            margin-bottom: 18px;

            color: #94a3b8;

            font-size: 13px;
        }

        .status-breadcrumb a {
            color: #64748b;

            text-decoration: none;
        }

        .status-breadcrumb a:hover {
            color: var(--primary);
        }

        .status-breadcrumb strong {
            color: #334155;
        }


        /* =========================
           HEADER
        ========================= */

        .status-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 24px;
        }

        .status-module-label {
            margin-bottom: 7px;

            color: var(--primary);

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .08em;
        }

        .status-header h1 {
            margin: 0;

            color: #0f172a;

            font-size: 29px;
            font-weight: 750;

            line-height: 1.25;
        }

        .status-header p {
            margin: 8px 0 0;

            color: var(--muted);

            font-size: 14px;

            line-height: 1.6;
        }

        .status-policy {
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

        .status-policy span {
            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: #2563eb;
        }


        /* =========================
           CARD
        ========================= */

        .status-card {
            overflow: hidden;

            margin-bottom: 22px;

            border: 1px solid var(--border);
            border-radius: 14px;

            background: #ffffff;

            box-shadow:
                0 3px 14px rgba(15, 23, 42, .035);
        }

        .status-card-header {
            padding: 18px 20px;

            border-bottom: 1px solid var(--border);
        }

        .status-card-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 15px;
            font-weight: 750;
        }

        .status-card-header p {
            margin: 5px 0 0;

            color: var(--muted);

            font-size: 12px;
        }

        .status-card-body {
            padding: 20px;
        }


        /* =========================
           FILTER
        ========================= */

        .status-filter-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 2fr) minmax(220px, .8fr);

            gap: 15px;
        }

        .status-form-group label {
            display: block;

            margin-bottom: 7px;

            color: #334155;

            font-size: 12px;
            font-weight: 700;
        }

        .status-control {
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

        .status-control:hover {
            border-color: #94a3b8;
        }

        .status-control:focus {
            border-color: var(--primary);

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, .12);
        }

        .status-filter-actions {
            display: flex;

            gap: 10px;

            margin-top: 18px;
        }

        .status-btn {
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

        .status-btn-primary {
            border: 1px solid var(--primary);

            background: var(--primary);

            color: #ffffff;
        }

        .status-btn-primary:hover {
            border-color: var(--primary-dark);

            background: var(--primary-dark);
        }

        .status-btn-secondary {
            border: 1px solid #cbd5e1;

            background: #ffffff;

            color: #475569;
        }

        .status-btn-secondary:hover {
            background: #f8fafc;

            color: #0f172a;
        }


        /* =========================
           LIST HEADER
        ========================= */

        .status-list-header {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 20px;
        }

        .status-count {
            color: var(--muted);

            font-size: 12px;

            white-space: nowrap;
        }

        .status-count strong {
            color: #0f172a;

            font-size: 14px;
        }


        /* =========================
           TABLE
        ========================= */

        .status-table-wrapper {
            width: 100%;

            overflow-x: auto;
        }

        .status-table {
            width: 100%;

            border-collapse: collapse;

            font-size: 13px;
        }

        .status-table th {
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

        .status-table td {
            padding: 14px;

            border-bottom: 1px solid #edf2f7;

            color: #334155;

            vertical-align: middle;
        }

        .status-table tbody tr:hover {
            background: #f8fbff;
        }

        .status-table tbody tr:last-child td {
            border-bottom: none;
        }


        /* =========================
           ORDER
        ========================= */

        .status-order {
            display: flex;

            width: 32px;
            height: 32px;

            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background: #eff6ff;

            color: #2563eb;

            font-weight: 800;
        }


        /* =========================
           NAME
        ========================= */

        .status-name {
            color: #0f172a;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 12px;
            font-weight: 750;

            white-space: nowrap;
        }


        /* =========================
           DESCRIPTION
        ========================= */

        .status-description {
            max-width: 360px;

            color: #475569;

            line-height: 1.5;
        }


        /* =========================
           BADGES
        ========================= */

        .status-badge {
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

        .badge-yes {
            background: #dcfce7;

            color: #15803d;
        }

        .badge-no {
            background: #f1f5f9;

            color: #475569;
        }

        .badge-active {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .badge-inactive {
            background: #fee2e2;

            color: #b91c1c;
        }


        /* =========================
           ACTIONS
        ========================= */

        .status-actions {
            display: flex;
            align-items: center;

            gap: 8px;

            white-space: nowrap;
        }

        .status-action-btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 7px 11px;

            border-radius: 8px;

            font-size: 12px;
            font-weight: 700;

            text-decoration: none;

            transition: .2s;
        }

        .action-view {
            border: 1px solid #bfdbfe;

            background: #ffffff;

            color: #2563eb;
        }

        .action-view:hover {
            background: #eff6ff;
        }

        .action-edit {
            border: 1px solid #fde68a;

            background: #fffbeb;

            color: #b45309;
        }

        .action-edit:hover {
            background: #fef3c7;
        }


        /* =========================
           EMPTY
        ========================= */

        .status-empty {
            padding: 50px 20px !important;

            text-align: center;
        }

        .status-empty-icon {
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

        .status-empty strong {
            display: block;

            color: #0f172a;

            font-size: 14px;
        }

        .status-empty p {
            margin: 6px 0 0;

            color: #64748b;

            font-size: 12px;
        }


        /* =========================
           PAGINATION
        ========================= */

        .status-pagination {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 20px;

            padding: 15px 20px;

            border-top: 1px solid var(--border);
        }

        .status-pagination-info {
            color: #64748b;

            font-size: 12px;
        }

        .status-pagination-info strong {
            color: #0f172a;
        }

        .status-pagination-buttons {
            display: flex;

            gap: 8px;
        }

        .status-page-btn {
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

        .status-page-btn:hover {
            border-color: #93c5fd;

            background: #eff6ff;

            color: #2563eb;
        }

        .status-page-btn.disabled {
            opacity: .45;

            cursor: not-allowed;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .status-header {
                flex-direction: column;
            }

            .status-filter-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 768px) {

            .status-header h1 {
                font-size: 24px;
            }

            .status-filter-actions {
                flex-direction: column;
            }

            .status-btn {
                width: 100%;
            }

            .status-list-header {
                align-items: flex-start;

                flex-direction: column;
            }

            .status-pagination {
                align-items: flex-start;

                flex-direction: column;
            }

        }
    </style>

@endsection