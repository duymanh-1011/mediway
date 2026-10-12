@extends('admin.layouts.app')

@section('title', 'Nhật ký hoạt động')

@section('content')

    <div class="audit-page">

        {{-- Breadcrumb --}}
        <div class="audit-breadcrumb">
            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <strong>Nhật ký hoạt động</strong>
        </div>

        {{-- Header --}}
        <div class="audit-header">
            <div>
                <div class="audit-module-label">
                    HỆ THỐNG
                </div>

                <h1>Nhật ký hoạt động</h1>

                <p>
                    Theo dõi và tra cứu các thao tác đã phát sinh
                    trong hệ thống Mediway.
                </p>
            </div>

            <div class="audit-readonly">
                <span></span>
                Dữ liệu chỉ đọc
            </div>
        </div>

        {{-- Filter --}}
        <div class="audit-card">
            <div class="audit-card-header">
                <div>
                    <h2>Bộ lọc nhật ký</h2>

                    <p>
                        Lọc theo người thực hiện, hành động,
                        đối tượng và thời gian.
                    </p>
                </div>
            </div>

            <div class="audit-card-body">
                <form method="GET" action="{{ route('admin.audit-logs.index') }}">
                    <div class="audit-filter-grid">

                        <div class="audit-form-group">
                            <label>Người thực hiện</label>

                            <select name="user" class="audit-control">
                                <option value="">
                                    Tất cả người thực hiện
                                </option>

                                @foreach ($users as $user)
                                    <option value="{{ $user->business_code }}" @selected(
                                        request('user') ===
                                        $user->business_code
                                    )>
                                        {{ $user->business_code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="audit-form-group">
                            <label>Hành động</label>

                            <select name="action" class="audit-control">
                                <option value="">
                                    Tất cả hành động
                                </option>

                                @foreach ($actions as $action)
                                    <option value="{{ $action }}" @selected(
                                        request('action') === $action
                                    )>
                                        {{ $action }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="audit-form-group">
                            <label>Đối tượng</label>

                            <select name="entity" class="audit-control">
                                <option value="">
                                    Tất cả đối tượng
                                </option>

                                @foreach ($entities as $entity)
                                    <option value="{{ $entity }}" @selected(
                                        request('entity') === $entity
                                    )>
                                        {{ $entity }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="audit-form-group">
                            <label>Từ ngày</label>

                            <input type="date" name="date_from" class="audit-control" value="{{ request('date_from') }}">
                        </div>

                        <div class="audit-form-group">
                            <label>Đến ngày</label>

                            <input type="date" name="date_to" class="audit-control" value="{{ request('date_to') }}">
                        </div>

                    </div>

                    <div class="audit-filter-actions">
                        <button type="submit" class="audit-btn audit-btn-primary">
                            Lọc dữ liệu
                        </button>

                        <a href="{{ route('admin.audit-logs.index') }}" class="audit-btn audit-btn-secondary">
                            Xóa bộ lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="audit-card">

            <div class="audit-card-header audit-list-header">
                <div>
                    <h2>Danh sách nhật ký</h2>

                    <p>
                        Các thao tác mới nhất được hiển thị trước.
                    </p>
                </div>

                <div class="audit-count">
                    Tổng cộng
                    <strong>{{ $logs->total() }}</strong>
                    bản ghi
                </div>
            </div>

            <div class="audit-table-wrapper">
                <table class="audit-table">

                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Người thực hiện</th>
                            <th>Vai trò</th>
                            <th>Hành động</th>
                            <th>Đối tượng</th>
                            <th>Mã nghiệp vụ</th>
                            <th>Mô tả</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($logs as $log)

                                            @php
                                                $badgeClass = match ($log->action) {
                                                    'CREATE' => 'badge-create',
                                                    'UPDATE' => 'badge-update',
                                                    'STATUS_CHANGE' => 'badge-status',
                                                    'LOGIN' => 'badge-login',
                                                    'DELETE' => 'badge-delete',
                                                    'PRINT' => 'badge-print',
                                                    default => 'badge-default',
                                                };

                                                $avatar = $log->user_business_code
                                                    ? mb_substr(
                                                        $log->user_business_code,
                                                        0,
                                                        1
                                                    )
                                                    : 'H';
                                            @endphp

                                            <tr>

                                                <td class="audit-time">
                                                    {{ \Carbon\Carbon::parse(
                                $log->performed_at
                            )->format('d/m/Y H:i') }}
                                                </td>

                                                <td>
                                                    <div class="audit-user">
                                                        <div class="audit-avatar">
                                                            {{ strtoupper($avatar) }}
                                                        </div>

                                                        <div>
                                                            <strong>
                                                                {{ $log->user_business_code
                                ?? 'Hệ thống' }}
                                                            </strong>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td class="audit-muted">
                                                    {{ $log->role_name_snapshot ?? '—' }}
                                                </td>

                                                <td>
                                                    <span class="audit-badge {{ $badgeClass }}">
                                                        {{ $log->action }}
                                                    </span>
                                                </td>

                                                <td>
                                                    <strong class="audit-entity">
                                                        {{ $log->entity_type }}
                                                    </strong>
                                                </td>

                                                <td>
                                                    @if ($log->entity_business_code)
                                                        <span class="audit-code">
                                                            {{ $log->entity_business_code }}
                                                        </span>
                                                    @else
                                                        <span class="audit-muted">
                                                            —
                                                        </span>
                                                    @endif
                                                </td>

                                                <td>
                                                    <div class="audit-description">
                                                        {{ \Illuminate\Support\Str::limit(
                                $log->description ?? '—',
                                65
                            ) }}
                                                    </div>
                                                </td>

                                                <td>
                                                    <a href="{{ route(
                                'admin.audit-logs.show',
                                $log->id
                            ) }}" class="audit-detail-btn">
                                                        Chi tiết
                                                    </a>
                                                </td>

                                            </tr>

                        @empty

                            <tr>
                                <td colspan="8" class="audit-empty">
                                    <div class="audit-empty-icon">
                                        !
                                    </div>

                                    <strong>
                                        Không tìm thấy nhật ký
                                    </strong>

                                    <p>
                                        Không có dữ liệu phù hợp
                                        với bộ lọc hiện tại.
                                    </p>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($logs->hasPages())
                <div class="audit-pagination">

                    <div class="audit-pagination-info">
                        Trang
                        <strong>
                            {{ $logs->currentPage() }}
                        </strong>
                        /
                        {{ $logs->lastPage() }}
                    </div>

                    <div class="audit-pagination-buttons">

                        @if ($logs->onFirstPage())
                            <span class="audit-page-btn disabled">
                                ← Trước
                            </span>
                        @else
                            <a href="{{ $logs->previousPageUrl() }}" class="audit-page-btn">
                                ← Trước
                            </a>
                        @endif

                        @if ($logs->hasMorePages())
                            <a href="{{ $logs->nextPageUrl() }}" class="audit-page-btn">
                                Sau →
                            </a>
                        @else
                            <span class="audit-page-btn disabled">
                                Sau →
                            </span>
                        @endif

                    </div>

                </div>
            @endif

        </div>

    </div>


    <style>
        .audit-page {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #eff6ff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --background: #f8fafc;

            width: 100%;
            color: var(--text);
        }

        .audit-page * {
            box-sizing: border-box;
        }


        /* =========================
                   BREADCRUMB
                ========================= */

        .audit-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 18px;

            color: #94a3b8;

            font-size: 13px;
        }

        .audit-breadcrumb a {
            color: #64748b;
            text-decoration: none;
        }

        .audit-breadcrumb a:hover {
            color: var(--primary);
        }

        .audit-breadcrumb strong {
            color: #334155;
        }


        /* =========================
                   HEADER
                ========================= */

        .audit-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;

            gap: 20px;

            margin-bottom: 24px;
        }

        .audit-module-label {
            margin-bottom: 7px;

            color: var(--primary);

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .08em;
        }

        .audit-header h1 {
            margin: 0;

            color: #0f172a;

            font-size: 29px;
            font-weight: 750;

            line-height: 1.25;
        }

        .audit-header p {
            margin: 8px 0 0;

            color: var(--muted);

            font-size: 14px;

            line-height: 1.6;
        }

        .audit-readonly {
            display: flex;
            align-items: center;

            gap: 7px;

            padding: 8px 13px;

            border: 1px solid #bfdbfe;
            border-radius: 999px;

            background: var(--primary-light);

            color: #1d4ed8;

            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        .audit-readonly span {
            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: var(--primary);
        }


        /* =========================
                   CARD
                ========================= */

        .audit-card {
            overflow: hidden;

            margin-bottom: 22px;

            border: 1px solid var(--border);
            border-radius: 14px;

            background: #ffffff;

            box-shadow:
                0 3px 14px rgba(15, 23, 42, .035);
        }

        .audit-card-header {
            padding: 18px 20px;

            border-bottom: 1px solid var(--border);
        }

        .audit-card-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 15px;
            font-weight: 750;
        }

        .audit-card-header p {
            margin: 5px 0 0;

            color: var(--muted);

            font-size: 12px;
        }

        .audit-card-body {
            padding: 20px;
        }


        /* =========================
                   FILTER
                ========================= */

        .audit-filter-grid {
            display: grid;

            grid-template-columns:
                1.2fr 1fr 1.2fr 1fr 1fr;

            gap: 15px;
        }

        .audit-form-group label {
            display: block;

            margin-bottom: 7px;

            color: #334155;

            font-size: 12px;
            font-weight: 700;
        }

        .audit-control {
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

        .audit-control:hover {
            border-color: #94a3b8;
        }

        .audit-control:focus {
            border-color: var(--primary);

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, .12);
        }

        .audit-filter-actions {
            display: flex;

            gap: 10px;

            margin-top: 18px;
        }

        .audit-btn {
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

        .audit-btn-primary {
            border: 1px solid var(--primary);

            background: var(--primary);

            color: #ffffff;
        }

        .audit-btn-primary:hover {
            border-color: var(--primary-dark);

            background: var(--primary-dark);
        }

        .audit-btn-secondary {
            border: 1px solid #cbd5e1;

            background: #ffffff;

            color: #475569;
        }

        .audit-btn-secondary:hover {
            background: #f8fafc;

            color: #0f172a;
        }


        /* =========================
                   LIST HEADER
                ========================= */

        .audit-list-header {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 20px;
        }

        .audit-count {
            color: var(--muted);

            font-size: 12px;

            white-space: nowrap;
        }

        .audit-count strong {
            color: #0f172a;

            font-size: 14px;
        }


        /* =========================
                   TABLE
                ========================= */

        .audit-table-wrapper {
            width: 100%;

            overflow-x: auto;
        }

        .audit-table {
            width: 100%;

            border-collapse: collapse;

            font-size: 13px;
        }

        .audit-table th {
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

        .audit-table td {
            padding: 14px;

            border-bottom: 1px solid #edf2f7;

            color: #334155;

            vertical-align: middle;
        }

        .audit-table tbody tr {
            transition: background .2s;
        }

        .audit-table tbody tr:hover {
            background: #f8fbff;
        }

        .audit-table tbody tr:last-child td {
            border-bottom: none;
        }

        .audit-time {
            font-weight: 600;

            white-space: nowrap;
        }

        .audit-user {
            display: flex;

            align-items: center;

            gap: 9px;

            white-space: nowrap;
        }

        .audit-user strong {
            color: #0f172a;

            font-size: 12px;
        }

        .audit-avatar {
            display: flex;

            width: 30px;
            height: 30px;

            flex: 0 0 30px;

            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 11px;
            font-weight: 800;
        }

        .audit-muted {
            color: #64748b !important;

            font-size: 12px;
        }

        .audit-entity {
            color: #334155;

            font-size: 12px;
        }

        .audit-code {
            display: inline-flex;

            padding: 5px 8px;

            border: 1px solid #dbeafe;
            border-radius: 7px;

            background: #f8fbff;

            color: #1d4ed8;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 11px;
            font-weight: 700;

            white-space: nowrap;
        }

        .audit-description {
            max-width: 300px;

            color: #475569;

            line-height: 1.45;
        }


        /* =========================
                   BADGE
                ========================= */

        .audit-badge {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            min-height: 25px;

            padding: 4px 9px;

            border-radius: 999px;

            font-size: 10px;
            font-weight: 800;

            white-space: nowrap;
        }

        .badge-create {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-update {
            background: #fef3c7;
            color: #b45309;
        }

        .badge-status {
            background: #ede9fe;
            color: #6d28d9;
        }

        .badge-login {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-delete {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-print {
            background: #cffafe;
            color: #0e7490;
        }

        .badge-default {
            background: #f1f5f9;
            color: #475569;
        }


        /* =========================
                   DETAIL BUTTON
                ========================= */

        .audit-detail-btn {
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

            white-space: nowrap;

            transition: .2s;
        }

        .audit-detail-btn:hover {
            border-color: #93c5fd;

            background: #eff6ff;
        }


        /* =========================
                   EMPTY STATE
                ========================= */

        .audit-empty {
            padding: 50px 20px !important;

            text-align: center;
        }

        .audit-empty-icon {
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
            font-weight: 700;
        }

        .audit-empty strong {
            display: block;

            color: #0f172a;

            font-size: 14px;
        }

        .audit-empty p {
            margin: 6px 0 0;

            color: #64748b;

            font-size: 12px;
        }


        /* =========================
                   PAGINATION
                ========================= */

        .audit-pagination {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 20px;

            padding: 15px 20px;

            border-top: 1px solid var(--border);
        }

        .audit-pagination-info {
            color: #64748b;

            font-size: 12px;
        }

        .audit-pagination-info strong {
            color: #0f172a;
        }

        .audit-pagination-buttons {
            display: flex;

            gap: 8px;
        }

        .audit-page-btn {
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

        .audit-page-btn:hover {
            border-color: #93c5fd;

            background: #eff6ff;

            color: #2563eb;
        }

        .audit-page-btn.disabled {
            opacity: .45;

            cursor: not-allowed;
        }


        /* =========================
                   RESPONSIVE
                ========================= */

        @media (max-width: 1200px) {
            .audit-filter-grid {
                grid-template-columns:
                    repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {

            .audit-header {
                flex-direction: column;
            }

            .audit-header h1 {
                font-size: 24px;
            }

            .audit-filter-grid {
                grid-template-columns: 1fr;
            }

            .audit-filter-actions {
                flex-direction: column;
            }

            .audit-btn {
                width: 100%;
            }

            .audit-list-header {
                align-items: flex-start;

                flex-direction: column;
            }

            .audit-pagination {
                align-items: flex-start;

                flex-direction: column;
            }
        }
    </style>

@endsection