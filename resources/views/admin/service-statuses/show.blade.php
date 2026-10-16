@extends('admin.layouts.app')

@section('title', 'Chi tiết trạng thái dịch vụ')

@section('content')

    <div class="status-show-page">

        {{-- Breadcrumb --}}
        <div class="status-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <a href="{{ route('admin.service-statuses.index') }}">
                Trạng thái dịch vụ
            </a>

            <span>/</span>

            <strong>
                {{ $status->name }}
            </strong>

        </div>


        {{-- Header --}}
        <div class="status-show-header">

            <div>

                <div class="status-module-label">
                    CẤU HÌNH TRẠNG THÁI
                </div>

                <h1>
                    Chi tiết trạng thái dịch vụ
                </h1>

                <p>
                    Xem thông tin cấu hình và quy tắc xử lý
                    của trạng thái dịch vụ.
                </p>

            </div>


            {{-- Trạng thái sử dụng tự động --}}
            @if ($status->is_in_use)

                <span class="status-active-badge active">
                    <span></span>
                    Đang sử dụng
                </span>

            @else

                <span class="status-active-badge inactive">
                    <span></span>
                    Ngừng sử dụng
                </span>

            @endif

        </div>


        {{-- Actions --}}
        <div class="status-top-actions">

            <a href="{{ route('admin.service-statuses.index') }}" class="status-back-btn">
                ← Quay lại danh sách
            </a>

            <a href="{{ route(
        'admin.service-statuses.edit',
        $status->id
    ) }}" class="status-edit-btn">
                Sửa cấu hình
            </a>

        </div>


        <div class="status-detail-layout">

            {{-- Main information --}}
            <div class="status-detail-main">

                <div class="status-card">

                    <div class="status-card-header">

                        <div>
                            <h2>
                                Thông tin trạng thái
                            </h2>

                            <p>
                                Các thông tin chính dùng trong
                                quy trình thực hiện dịch vụ.
                            </p>
                        </div>

                    </div>


                    <div class="status-info-grid">

                        {{-- Name --}}
                        <div class="status-info-item">

                            <div class="status-info-label">
                                Tên trạng thái
                            </div>

                            <div class="status-name">
                                {{ $status->name }}
                            </div>

                        </div>


                        {{-- Process Order --}}
                        <div class="status-info-item">

                            <div class="status-info-label">
                                Thứ tự xử lý
                            </div>

                            <div class="status-order">
                                {{ $status->process_order }}
                            </div>

                        </div>


                        {{-- Transition --}}
                        <div class="status-info-item">

                            <div class="status-info-label">
                                Cho phép chuyển tiếp
                            </div>

                            @if ($status->allows_transition)

                                <span class="status-badge badge-yes">
                                    Cho phép
                                </span>

                            @else

                                <span class="status-badge badge-no">
                                    Không cho phép
                                </span>

                            @endif

                        </div>


                        {{-- Usage status - AUTO --}}
                        <div class="status-info-item">

                            <div class="status-info-label">
                                Trạng thái sử dụng
                            </div>

                            @if ($status->is_in_use)

                                <span class="status-badge badge-active">
                                    Đang sử dụng
                                </span>

                            @else

                                <span class="status-badge badge-inactive">
                                    Ngừng sử dụng
                                </span>

                            @endif

                            <div class="status-usage-note">
                                {{ $status->usage_count }}
                                chi tiết chỉ định đang ở trạng thái này
                            </div>

                        </div>


                        {{-- Configuration Active --}}
                        <div class="status-info-item">

                            <div class="status-info-label">
                                Kích hoạt cấu hình
                            </div>

                            @if ($status->is_active)

                                <span class="status-badge badge-config-active">
                                    Đang kích hoạt
                                </span>

                            @else

                                <span class="status-badge badge-config-inactive">
                                    Ngừng kích hoạt
                                </span>

                            @endif

                        </div>

                    </div>


                    {{-- Description --}}
                    <div class="status-description-section">

                        <div class="status-info-label">
                            Mô tả
                        </div>

                        <div class="status-description-text">
                            {{ $status->description
        ?? 'Chưa có mô tả cho trạng thái này.' }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- Side --}}
            <div class="status-detail-side">

                <div class="status-card">

                    <div class="status-card-header">

                        <div>
                            <h2>
                                Thông tin hệ thống
                            </h2>

                            <p>
                                Thời gian tạo và cập nhật bản ghi.
                            </p>
                        </div>

                    </div>


                    <div class="status-system-list">

                        <div class="status-system-item">

                            <div class="status-system-icon">
                                C
                            </div>

                            <div>

                                <div class="status-info-label">
                                    Ngày tạo
                                </div>

                                <div class="status-system-value">

                                    @if ($status->created_at)

                                                                    {{ \Carbon\Carbon::parse(
                                            $status->created_at
                                        )->format('d/m/Y H:i:s') }}

                                    @else
                                        —
                                    @endif

                                </div>

                            </div>

                        </div>


                        <div class="status-system-item">

                            <div class="status-system-icon">
                                U
                            </div>

                            <div>

                                <div class="status-info-label">
                                    Cập nhật gần nhất
                                </div>

                                <div class="status-system-value">

                                    @if ($status->updated_at)

                                                                    {{ \Carbon\Carbon::parse(
                                            $status->updated_at
                                        )->format('d/m/Y H:i:s') }}

                                    @else
                                        —
                                    @endif

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- Policy --}}
                <div class="status-policy-box">

                    <div class="status-policy-icon">
                        !
                    </div>

                    <div>

                        <strong>
                            Chính sách cấu hình
                        </strong>

                        <p>
                            Tên trạng thái không được thay đổi tùy tiện.
                            Không hard-delete trạng thái đã được sử dụng.
                            Khi không muốn trạng thái tiếp tục được lựa chọn
                            cho nghiệp vụ mới, hãy ngừng kích hoạt cấu hình.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <style>
        .status-show-page {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #eff6ff;

            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            width: 100%;

            color: var(--text);
        }

        .status-show-page * {
            box-sizing: border-box;
        }


        /* =========================
           BREADCRUMB
        ========================= */

        .status-breadcrumb {
            display: flex;

            align-items: center;

            flex-wrap: wrap;

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

        .status-show-header {
            display: flex;

            align-items: flex-start;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 18px;
        }

        .status-module-label {
            margin-bottom: 7px;

            color: var(--primary);

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .08em;
        }

        .status-show-header h1 {
            margin: 0;

            color: #0f172a;

            font-size: 29px;
            font-weight: 750;

            line-height: 1.25;
        }

        .status-show-header p {
            margin: 8px 0 0;

            color: var(--muted);

            font-size: 14px;

            line-height: 1.6;
        }


        /* =========================
           STATUS HEADER BADGE
        ========================= */

        .status-active-badge {
            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 8px 13px;

            border-radius: 999px;

            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        .status-active-badge span {
            width: 7px;
            height: 7px;

            border-radius: 50%;
        }

        .status-active-badge.active {
            border: 1px solid #bbf7d0;

            background: #f0fdf4;

            color: #15803d;
        }

        .status-active-badge.active span {
            background: #22c55e;
        }

        .status-active-badge.inactive {
            border: 1px solid #fecaca;

            background: #fef2f2;

            color: #b91c1c;
        }

        .status-active-badge.inactive span {
            background: #ef4444;
        }


        /* =========================
           TOP ACTIONS
        ========================= */

        .status-top-actions {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 12px;

            margin-bottom: 20px;
        }

        .status-back-btn {
            color: #475569;

            font-size: 13px;
            font-weight: 700;

            text-decoration: none;
        }

        .status-back-btn:hover {
            color: var(--primary);
        }

        .status-edit-btn {
            display: inline-flex;

            height: 40px;

            align-items: center;
            justify-content: center;

            padding: 0 16px;

            border: 1px solid #fde68a;
            border-radius: 9px;

            background: #fffbeb;

            color: #b45309;

            font-size: 13px;
            font-weight: 700;

            text-decoration: none;

            transition: .2s;
        }

        .status-edit-btn:hover {
            background: #fef3c7;
        }


        /* =========================
           LAYOUT
        ========================= */

        .status-detail-layout {
            display: grid;

            grid-template-columns:
                minmax(0, 2fr) minmax(300px, .8fr);

            gap: 22px;

            align-items: start;
        }

        .status-detail-side {
            display: grid;

            gap: 18px;
        }


        /* =========================
           CARD
        ========================= */

        .status-card {
            overflow: hidden;

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


        /* =========================
           INFO
        ========================= */

        .status-info-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .status-info-item {
            padding: 18px 20px;

            border-right: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;
        }

        .status-info-item:nth-child(2n) {
            border-right: none;
        }

        .status-info-label {
            margin-bottom: 7px;

            color: #64748b;

            font-size: 10px;
            font-weight: 800;

            letter-spacing: .05em;

            text-transform: uppercase;
        }

        .status-name {
            display: inline-flex;

            padding: 6px 10px;

            border: 1px solid #dbeafe;
            border-radius: 8px;

            background: #eff6ff;

            color: #1d4ed8;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 12px;
            font-weight: 750;
        }

        .status-order {
            display: flex;

            width: 34px;
            height: 34px;

            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background: #eff6ff;

            color: #2563eb;

            font-weight: 800;
        }


        /* =========================
           BADGES
        ========================= */

        .status-badge {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 5px 10px;

            border-radius: 999px;

            font-size: 10px;
            font-weight: 800;
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
            background: #dcfce7;

            color: #15803d;
        }

        .badge-inactive {
            background: #fee2e2;

            color: #b91c1c;
        }

        .badge-config-active {
            background: #dbeafe;

            color: #1d4ed8;
        }

        .badge-config-inactive {
            background: #f1f5f9;

            color: #64748b;
        }

        .status-usage-note {
            margin-top: 7px;

            color: #64748b;

            font-size: 11px;

            line-height: 1.5;
        }


        /* =========================
           DESCRIPTION
        ========================= */

        .status-description-section {
            padding: 18px 20px;

            background: #fbfdff;
        }

        .status-description-text {
            color: #334155;

            font-size: 13px;

            line-height: 1.7;
        }


        /* =========================
           SYSTEM INFO
        ========================= */

        .status-system-list {
            display: grid;
        }

        .status-system-item {
            display: flex;

            align-items: flex-start;

            gap: 12px;

            padding: 17px 18px;

            border-bottom: 1px solid #edf2f7;
        }

        .status-system-item:last-child {
            border-bottom: none;
        }

        .status-system-icon {
            display: flex;

            width: 34px;
            height: 34px;

            flex: 0 0 34px;

            align-items: center;
            justify-content: center;

            border-radius: 9px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 10px;
            font-weight: 800;
        }

        .status-system-value {
            color: #334155;

            font-size: 12px;

            line-height: 1.5;
        }


        /* =========================
           POLICY
        ========================= */

        .status-policy-box {
            display: flex;

            align-items: flex-start;

            gap: 12px;

            padding: 16px;

            border: 1px solid #fde68a;
            border-radius: 12px;

            background: #fffbeb;
        }

        .status-policy-icon {
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

        .status-policy-box strong {
            display: block;

            margin-bottom: 5px;

            color: #92400e;

            font-size: 12px;
        }

        .status-policy-box p {
            margin: 0;

            color: #78350f;

            font-size: 11px;

            line-height: 1.65;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1050px) {

            .status-detail-layout {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 768px) {

            .status-show-header {
                flex-direction: column;
            }

            .status-show-header h1 {
                font-size: 24px;
            }

            .status-top-actions {
                align-items: flex-start;

                flex-direction: column;
            }

            .status-info-grid {
                grid-template-columns: 1fr;
            }

            .status-info-item {
                border-right: none;
            }

        }
    </style>

@endsection