@extends('admin.layouts.app')

@section('title', 'Chi tiết nhật ký hoạt động')

@section('content')

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

        $oldValues = null;
        $newValues = null;

        if (!empty($log->old_values)) {
            $oldValues = is_string($log->old_values)
                ? json_decode($log->old_values, true)
                : $log->old_values;
        }

        if (!empty($log->new_values)) {
            $newValues = is_string($log->new_values)
                ? json_decode($log->new_values, true)
                : $log->new_values;
        }
    @endphp

    <div class="audit-show-page">

        {{-- =========================
        BREADCRUMB
        ========================== --}}
        <div class="audit-breadcrumb">
            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <a href="{{ route('admin.audit-logs.index') }}">
                Nhật ký hoạt động
            </a>

            <span>/</span>

            <strong>Chi tiết</strong>
        </div>


        {{-- =========================
        HEADER
        ========================== --}}
        <div class="audit-show-header">

            <div>
                <div class="audit-module-label">
                    NHẬT KÝ HỆ THỐNG
                </div>

                <h1>Chi tiết nhật ký hoạt động</h1>

                <p>
                    Xem thông tin đầy đủ của thao tác đã được
                    ghi nhận trong hệ thống Mediway.
                </p>
            </div>

            <div class="audit-readonly">
                <span></span>
                Chỉ đọc · Bất biến
            </div>

        </div>


        {{-- =========================
        BACK
        ========================== --}}
        <div class="audit-back-wrapper">
            <a href="{{ route('admin.audit-logs.index') }}" class="audit-back-btn">
                <span>←</span>
                Quay lại danh sách
            </a>
        </div>


        {{-- =========================
        CONTENT
        ========================== --}}
        <div class="audit-detail-layout">

            {{-- =========================
            LEFT
            ========================== --}}
            <div class="audit-detail-main">

                {{-- Thông tin thao tác --}}
                <div class="audit-card">

                    <div class="audit-card-header">

                        <div>
                            <h2>Thông tin thao tác</h2>

                            <p>
                                Người thực hiện, hành động,
                                đối tượng và thời gian phát sinh.
                            </p>
                        </div>

                        <span class="audit-badge {{ $badgeClass }}">
                            {{ $log->action }}
                        </span>

                    </div>


                    <div class="audit-info-grid">

                        {{-- Người thực hiện --}}
                        <div class="audit-info-item">

                            <div class="audit-info-label">
                                Người thực hiện
                            </div>

                            <div class="audit-info-value">
                                {{ $log->user_business_code
        ?? 'Hệ thống' }}
                            </div>

                        </div>


                        {{-- Vai trò --}}
                        <div class="audit-info-item">

                            <div class="audit-info-label">
                                Vai trò
                            </div>

                            <div class="audit-info-value">
                                {{ $log->role_name_snapshot
        ?? '—' }}
                            </div>

                        </div>


                        {{-- Hành động --}}
                        <div class="audit-info-item">

                            <div class="audit-info-label">
                                Hành động
                            </div>

                            <div class="audit-info-value">
                                <span class="audit-badge {{ $badgeClass }}">
                                    {{ $log->action }}
                                </span>
                            </div>

                        </div>


                        {{-- Thời gian --}}
                        <div class="audit-info-item">

                            <div class="audit-info-label">
                                Thời gian thực hiện
                            </div>

                            <div class="audit-info-value">
                                {{ \Carbon\Carbon::parse(
        $log->performed_at
    )->format('d/m/Y H:i:s') }}
                            </div>

                        </div>


                        {{-- Entity --}}
                        <div class="audit-info-item">

                            <div class="audit-info-label">
                                Loại đối tượng
                            </div>

                            <div class="audit-info-value">
                                {{ $log->entity_type }}
                            </div>

                        </div>


                        {{-- Business Code --}}
                        <div class="audit-info-item">

                            <div class="audit-info-label">
                                Mã nghiệp vụ
                            </div>

                            <div class="audit-info-value">

                                @if ($log->entity_business_code)

                                    <span class="audit-code">
                                        {{ $log->entity_business_code }}
                                    </span>

                                @else

                                    <span class="audit-muted">
                                        —
                                    </span>

                                @endif

                            </div>

                        </div>

                    </div>


                    {{-- Description --}}
                    <div class="audit-description-section">

                        <div class="audit-info-label">
                            Mô tả thao tác
                        </div>

                        <div class="audit-description-text">
                            {{ $log->description
        ?? 'Không có mô tả.' }}
                        </div>

                    </div>

                </div>


                {{-- =========================
                BEFORE JSON
                ========================== --}}
                <div class="audit-json-card">

                    <div class="audit-json-header">

                        <div>
                            <h2>Dữ liệu trước thay đổi</h2>

                            <p>
                                Trạng thái dữ liệu trước khi
                                thao tác được thực hiện.
                            </p>
                        </div>

                        <span class="audit-json-badge">
                            BEFORE JSON
                        </span>

                    </div>

                    @if (!empty($log->old_values))

                                    <pre class="audit-json-content">{{ json_encode(
                            $oldValues,
                            JSON_PRETTY_PRINT
                            | JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                        ) }}</pre>

                    @else

                        <div class="audit-json-empty">

                            <div class="audit-json-empty-icon">
                                { }
                            </div>

                            <strong>
                                Không có dữ liệu trước thay đổi
                            </strong>

                            <p>
                                Thao tác này không ghi nhận
                                giá trị trước đó.
                            </p>

                        </div>

                    @endif

                </div>


                {{-- =========================
                AFTER JSON
                ========================== --}}
                <div class="audit-json-card">

                    <div class="audit-json-header">

                        <div>
                            <h2>Dữ liệu sau thay đổi</h2>

                            <p>
                                Trạng thái dữ liệu sau khi
                                thao tác được thực hiện.
                            </p>
                        </div>

                        <span class="audit-json-badge">
                            AFTER JSON
                        </span>

                    </div>

                    @if (!empty($log->new_values))

                                    <pre class="audit-json-content">{{ json_encode(
                            $newValues,
                            JSON_PRETTY_PRINT
                            | JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                        ) }}</pre>

                    @else

                        <div class="audit-json-empty">

                            <div class="audit-json-empty-icon">
                                { }
                            </div>

                            <strong>
                                Không có dữ liệu sau thay đổi
                            </strong>

                            <p>
                                Thao tác này không ghi nhận
                                giá trị sau thay đổi.
                            </p>

                        </div>

                    @endif

                </div>

            </div>


            {{-- =========================
            RIGHT - TECHNICAL INFO
            ========================== --}}
            <div class="audit-detail-side">

                <div class="audit-card">

                    <div class="audit-card-header">

                        <div>
                            <h2>Thông tin kỹ thuật</h2>

                            <p>
                                Dữ liệu phục vụ truy vết
                                và kiểm tra hệ thống.
                            </p>
                        </div>

                    </div>


                    <div class="audit-tech-list">

                        {{-- IP --}}
                        <div class="audit-tech-item">

                            <div class="audit-tech-icon">
                                IP
                            </div>

                            <div class="audit-tech-content">

                                <div class="audit-info-label">
                                    IP Address
                                </div>

                                <div class="audit-info-value">
                                    {{ $log->ip_address
        ?? '—' }}
                                </div>

                            </div>

                        </div>


                        {{-- User Agent --}}
                        <div class="audit-tech-item">

                            <div class="audit-tech-icon">
                                UA
                            </div>

                            <div class="audit-tech-content">

                                <div class="audit-info-label">
                                    User Agent
                                </div>

                                <div class="audit-user-agent">
                                    {{ $log->user_agent
        ?? '—' }}
                                </div>

                            </div>

                        </div>


                        {{-- Performed At --}}
                        <div class="audit-tech-item">

                            <div class="audit-tech-icon">
                                T
                            </div>

                            <div class="audit-tech-content">

                                <div class="audit-info-label">
                                    Thời gian thực hiện
                                </div>

                                <div class="audit-info-value">
                                    {{ \Carbon\Carbon::parse(
        $log->performed_at
    )->format('d/m/Y H:i:s') }}
                                </div>

                            </div>

                        </div>


                        {{-- Created At --}}
                        <div class="audit-tech-item">

                            <div class="audit-tech-icon">
                                DB
                            </div>

                            <div class="audit-tech-content">

                                <div class="audit-info-label">
                                    Thời điểm ghi log
                                </div>

                                <div class="audit-info-value">

                                    @if ($log->created_at)

                                                                    {{ \Carbon\Carbon::parse(
                                            $log->created_at
                                        )->format('d/m/Y H:i:s') }}

                                    @else
                                        —
                                    @endif

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- Read only notice --}}
                <div class="audit-policy-box">

                    <div class="audit-policy-icon">
                        !
                    </div>

                    <div>
                        <strong>
                            Nhật ký không thể chỉnh sửa
                        </strong>

                        <p>
                            Dữ liệu Audit Log chỉ được sử dụng
                            để tra cứu và truy vết hoạt động.
                        </p>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <style>
        .audit-show-page {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #eff6ff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            width: 100%;

            color: var(--text);
        }

        .audit-show-page * {
            box-sizing: border-box;
        }


        /* =========================
               BREADCRUMB
            ========================= */

        .audit-breadcrumb {
            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 8px;

            margin-bottom: 18px;

            color: #94a3b8;

            font-size: 13px;
        }

        .audit-breadcrumb a {
            color: #64748b;

            text-decoration: none;

            transition: .2s;
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

        .audit-show-header {
            display: flex;

            align-items: flex-start;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 18px;
        }

        .audit-module-label {
            margin-bottom: 7px;

            color: var(--primary);

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .08em;
        }

        .audit-show-header h1 {
            margin: 0;

            color: #0f172a;

            font-size: 29px;
            font-weight: 750;

            line-height: 1.25;
        }

        .audit-show-header p {
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

            background: #eff6ff;

            color: #1d4ed8;

            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        .audit-readonly span {
            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: #2563eb;
        }


        /* =========================
               BACK
            ========================= */

        .audit-back-wrapper {
            margin-bottom: 20px;
        }

        .audit-back-btn {
            display: inline-flex;

            align-items: center;

            gap: 7px;

            color: #475569;

            font-size: 13px;
            font-weight: 700;

            text-decoration: none;

            transition: .2s;
        }

        .audit-back-btn span {
            font-size: 17px;
        }

        .audit-back-btn:hover {
            color: var(--primary);
        }


        /* =========================
               LAYOUT
            ========================= */

        .audit-detail-layout {
            display: grid;

            grid-template-columns:
                minmax(0, 2fr) minmax(300px, .8fr);

            gap: 22px;

            align-items: start;
        }

        .audit-detail-main {
            display: grid;

            gap: 22px;
        }

        .audit-detail-side {
            display: grid;

            gap: 18px;
        }


        /* =========================
               CARD
            ========================= */

        .audit-card {
            overflow: hidden;

            border: 1px solid var(--border);
            border-radius: 14px;

            background: #ffffff;

            box-shadow:
                0 3px 14px rgba(15, 23, 42, .035);
        }

        .audit-card-header {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 16px;

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

            line-height: 1.5;
        }


        /* =========================
               INFO GRID
            ========================= */

        .audit-info-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .audit-info-item {
            min-width: 0;

            padding: 18px 20px;

            border-right: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;
        }

        .audit-info-item:nth-child(2n) {
            border-right: none;
        }

        .audit-info-label {
            margin-bottom: 7px;

            color: #64748b;

            font-size: 10px;
            font-weight: 800;

            letter-spacing: .05em;

            text-transform: uppercase;
        }

        .audit-info-value {
            color: #0f172a;

            font-size: 13px;
            font-weight: 650;

            line-height: 1.5;

            word-break: break-word;
        }

        .audit-muted {
            color: #94a3b8;
        }


        /* =========================
               DESCRIPTION
            ========================= */

        .audit-description-section {
            padding: 18px 20px;

            background: #fbfdff;
        }

        .audit-description-text {
            color: #334155;

            font-size: 13px;

            line-height: 1.7;
        }


        /* =========================
               BUSINESS CODE
            ========================= */

        .audit-code {
            display: inline-flex;

            padding: 5px 9px;

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
        }


        /* =========================
               BADGES
            ========================= */

        .audit-badge {
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
               JSON
            ========================= */

        .audit-json-card {
            overflow: hidden;

            border: 1px solid var(--border);
            border-radius: 14px;

            background: #ffffff;

            box-shadow:
                0 3px 14px rgba(15, 23, 42, .035);
        }

        .audit-json-header {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 15px;

            padding: 17px 20px;

            border-bottom: 1px solid var(--border);
        }

        .audit-json-header h2 {
            margin: 0;

            color: #0f172a;

            font-size: 14px;
            font-weight: 750;
        }

        .audit-json-header p {
            margin: 5px 0 0;

            color: #64748b;

            font-size: 12px;
        }

        .audit-json-badge {
            padding: 5px 9px;

            border-radius: 999px;

            background: #f1f5f9;

            color: #64748b;

            font-size: 9px;
            font-weight: 800;

            letter-spacing: .05em;

            white-space: nowrap;
        }

        .audit-json-content {
            overflow-x: auto;

            max-height: 420px;

            margin: 0;

            padding: 20px;

            background: #0f172a;

            color: #dbeafe;

            font-family:
                Consolas,
                Monaco,
                "Courier New",
                monospace;

            font-size: 12px;

            line-height: 1.7;

            white-space: pre-wrap;

            word-break: break-word;
        }

        .audit-json-empty {
            padding: 38px 20px;

            text-align: center;
        }

        .audit-json-empty-icon {
            display: flex;

            width: 45px;
            height: 45px;

            align-items: center;
            justify-content: center;

            margin: 0 auto 12px;

            border-radius: 10px;

            background: #f1f5f9;

            color: #64748b;

            font-family: Consolas, monospace;

            font-size: 13px;
            font-weight: 800;
        }

        .audit-json-empty strong {
            display: block;

            color: #334155;

            font-size: 13px;
        }

        .audit-json-empty p {
            margin: 6px 0 0;

            color: #94a3b8;

            font-size: 12px;
        }


        /* =========================
               TECHNICAL INFO
            ========================= */

        .audit-tech-list {
            display: grid;
        }

        .audit-tech-item {
            display: flex;

            align-items: flex-start;

            gap: 12px;

            padding: 17px 18px;

            border-bottom: 1px solid #edf2f7;
        }

        .audit-tech-item:last-child {
            border-bottom: none;
        }

        .audit-tech-icon {
            display: flex;

            width: 34px;
            height: 34px;

            flex: 0 0 34px;

            align-items: center;
            justify-content: center;

            border-radius: 9px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 9px;
            font-weight: 850;
        }

        .audit-tech-content {
            min-width: 0;

            flex: 1;
        }

        .audit-user-agent {
            color: #475569;

            font-size: 12px;

            line-height: 1.65;

            word-break: break-word;
        }


        /* =========================
               POLICY
            ========================= */

        .audit-policy-box {
            display: flex;

            align-items: flex-start;

            gap: 12px;

            padding: 16px;

            border: 1px solid #bfdbfe;
            border-radius: 12px;

            background: #eff6ff;
        }

        .audit-policy-icon {
            display: flex;

            width: 29px;
            height: 29px;

            flex: 0 0 29px;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #2563eb;

            color: #ffffff;

            font-size: 12px;
            font-weight: 800;
        }

        .audit-policy-box strong {
            display: block;

            margin-bottom: 5px;

            color: #1e3a8a;

            font-size: 12px;
        }

        .audit-policy-box p {
            margin: 0;

            color: #475569;

            font-size: 11px;

            line-height: 1.6;
        }


        /* =========================
               RESPONSIVE
            ========================= */

        @media (max-width: 1100px) {

            .audit-detail-layout {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 768px) {

            .audit-show-header {
                flex-direction: column;
            }

            .audit-show-header h1 {
                font-size: 24px;
            }

            .audit-info-grid {
                grid-template-columns: 1fr;
            }

            .audit-info-item {
                border-right: none;
            }

            .audit-card-header,
            .audit-json-header {
                align-items: flex-start;

                flex-direction: column;
            }

        }
    </style>

@endsection