@extends('admin.layouts.app')

@section('title', 'Chi tiết kết quả dịch vụ')

@section('content')

    @php
        $item = $result->serviceOrderItem;
        $order = $item?->serviceOrder;
        $visit = $order?->medicalVisit;
        $service = $item?->medicalService;
        $serviceStatus = $item?->serviceStatus;
        $performer = $item?->performer;
        $doctor = $order?->orderedByDoctor;

        $pdfUrl = null;

        if ($result->result_pdf_path) {
            $pdfPath = preg_replace(
                '#^storage/#',
                '',
                ltrim($result->result_pdf_path, '/')
            );

            $pdfUrl = str_starts_with(
                $result->result_pdf_path,
                'http'
            )
                ? $result->result_pdf_path
                : asset('storage/' . $pdfPath);
        }

        $imageUrl = null;

        if ($result->result_image_path) {
            $imagePath = preg_replace(
                '#^storage/#',
                '',
                ltrim($result->result_image_path, '/')
            );

            $imageUrl = str_starts_with(
                $result->result_image_path,
                'http'
            )
                ? $result->result_image_path
                : asset('storage/' . $imagePath);
        }
    @endphp


    <div class="service-result-show-page">

        {{-- Breadcrumb --}}
        <div class="result-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <a href="{{ route('admin.service-results.index') }}">
                Kết quả dịch vụ
            </a>

            <span>/</span>

            <strong>
                {{ $service?->service_code ?? 'Chi tiết' }}
            </strong>

        </div>


        {{-- Header --}}
        <div class="result-header">

            <div>

                <div class="result-module-label">
                    KẾT QUẢ DỊCH VỤ
                </div>

                <h1>
                    Chi tiết kết quả dịch vụ
                </h1>

                <p>
                    Xem nội dung kết quả, kết luận chuyên môn,
                    thông số, người xác nhận và dữ liệu liên quan.
                </p>

            </div>


            @if ($result->verified_by_user_id)

                <span class="result-header-badge verified">
                    <span></span>
                    Đã xác nhận
                </span>

            @else

                <span class="result-header-badge pending">
                    <span></span>
                    Chưa xác nhận
                </span>

            @endif

        </div>


        {{-- Back --}}
        <div class="result-top-actions">

            <a href="{{ route('admin.service-results.index') }}" class="result-back-btn">
                ← Quay lại danh sách
            </a>

            <div class="result-top-action-group">

                @if ($order)

                            <a href="{{ route(
                        'admin.service-orders.show',
                        $order->id
                    ) }}" class="result-order-btn">
                                Xem phiếu chỉ định
                            </a>

                @endif

                <a href="{{ route(
        'admin.service-results.edit',
        $result->id
    ) }}" class="result-edit-btn">
                    Sửa kết quả
                </a>

            </div>

        </div>

        {{-- Context --}}
        <div class="result-card">

            <div class="result-card-header">

                <div>

                    <h2>
                        Thông tin dịch vụ
                    </h2>

                    <p>
                        Ngữ cảnh của kết quả trong phiếu chỉ định.
                    </p>

                </div>

            </div>


            <div class="result-info-grid">

                {{-- Order --}}
                <div class="result-info-item">

                    <div class="result-info-label">
                        Phiếu chỉ định
                    </div>

                    @if ($order)

                        <div class="result-code">
                            {{ $order->service_order_code }}
                        </div>

                    @else

                        <span class="result-empty-value">
                            —
                        </span>

                    @endif

                </div>


                {{-- Visit --}}
                <div class="result-info-item">

                    <div class="result-info-label">
                        Lượt khám
                    </div>

                    @if ($visit)

                        <div class="result-secondary-code">
                            {{ $visit->visit_code }}
                        </div>

                    @else

                        <span class="result-empty-value">
                            —
                        </span>

                    @endif

                </div>


                {{-- Service --}}
                <div class="result-info-item">

                    <div class="result-info-label">
                        Dịch vụ
                    </div>

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

                </div>


                {{-- Service status --}}
                <div class="result-info-item">

                    <div class="result-info-label">
                        Trạng thái dịch vụ
                    </div>

                    @if ($serviceStatus)

                        @php
                            $serviceStatusClass = match ($serviceStatus->name) {
                                'PENDING' => 'status-pending',
                                'PROCESSING' => 'status-processing',
                                'COMPLETED' => 'status-completed',
                                'CANCELLED' => 'status-cancelled',
                                default => 'status-default',
                            };
                        @endphp

                        <span class="result-status-badge {{ $serviceStatusClass }}">
                            {{ $serviceStatus->name }}
                        </span>

                    @else

                        <span class="result-empty-value">
                            —
                        </span>

                    @endif

                </div>


                {{-- Price --}}
                <div class="result-info-item">

                    <div class="result-info-label">
                        Giá tại thời điểm chỉ định
                    </div>

                    @if ($item)

                                    <div class="result-price">
                                        {{ number_format(
                            (float) $item->price_at_order,
                            0,
                            ',',
                            '.'
                        ) }}
                                        đ
                                    </div>

                    @else

                        <span class="result-empty-value">
                            —
                        </span>

                    @endif

                </div>


                {{-- Doctor --}}
                <div class="result-info-item">

                    <div class="result-info-label">
                        Bác sĩ chỉ định
                    </div>

                    @if ($doctor)

                        <div class="result-person">

                            <strong>
                                {{ $doctor->full_name }}
                            </strong>

                            <span>
                                {{ $doctor->doctor_code }}
                            </span>

                        </div>

                    @else

                        <span class="result-empty-value">
                            —
                        </span>

                    @endif

                </div>


                {{-- Performer --}}
                <div class="result-info-item">

                    <div class="result-info-label">
                        Người thực hiện
                    </div>

                    @if ($performer)

                        <div class="result-person">

                            <strong>
                                {{ $performer->full_name }}
                            </strong>

                            <span>
                                {{ $performer->employee_code }}
                            </span>

                        </div>

                    @else

                        <span class="result-empty-value">
                            Chưa có
                        </span>

                    @endif

                </div>


                {{-- Verifier --}}
                <div class="result-info-item">

                    <div class="result-info-label">
                        Người xác nhận
                    </div>

                    @if ($result->verifier)

                        <span class="result-verifier">
                            {{ $result->verifier->business_code }}
                        </span>

                    @else

                        <span class="result-not-verified">
                            Chưa xác nhận
                        </span>

                    @endif

                </div>

            </div>

        </div>


        {{-- Medical result --}}
        <div class="result-detail-layout">

            <div class="result-detail-main">

                {{-- Summary --}}
                <div class="result-card">

                    <div class="result-card-header">

                        <div>

                            <h2>
                                Nội dung kết quả
                            </h2>

                            <p>
                                Tóm tắt và kết luận của dịch vụ.
                            </p>

                        </div>

                    </div>


                    <div class="result-text-section">

                        <div class="result-info-label">
                            Tóm tắt kết quả
                        </div>

                        <div class="result-text-content">
                            {{ $result->result_summary
        ?? 'Chưa có nội dung tóm tắt.' }}
                        </div>

                    </div>


                    <div class="result-text-section result-conclusion-section">

                        <div class="result-info-label">
                            Kết luận dịch vụ
                        </div>

                        <div class="result-conclusion">
                            {{ $result->result_conclusion
        ?? 'Chưa có kết luận.' }}
                        </div>

                    </div>


                    @if ($result->notes)

                        <div class="result-text-section">

                            <div class="result-info-label">
                                Ghi chú
                            </div>

                            <div class="result-text-content">
                                {{ $result->notes }}
                            </div>

                        </div>

                    @endif

                </div>


                {{-- JSON Parameters --}}
                <div class="result-card">

                    <div class="result-card-header">

                        <div>

                            <h2>
                                Chỉ số / thông số kết quả
                            </h2>

                            <p>
                                Dữ liệu thông số được lưu dưới dạng JSON.
                            </p>

                        </div>

                    </div>


                    @if (
                                        is_array($result->result_parameters)
                                        &&
                                        count($result->result_parameters) > 0
                                    )

                                    <div class="result-parameters">

                                        <pre>{{ json_encode(
                            $result->result_parameters,
                            JSON_PRETTY_PRINT
                            |
                            JSON_UNESCAPED_UNICODE
                            |
                            JSON_UNESCAPED_SLASHES
                        ) }}</pre>

                                    </div>

                    @else

                        <div class="result-no-data">

                            <div class="result-no-data-icon">
                                !
                            </div>

                            <strong>
                                Chưa có thông số kết quả
                            </strong>

                            <p>
                                Kết quả này không có dữ liệu JSON
                                hoặc chưa được nhập thông số.
                            </p>

                        </div>

                    @endif

                </div>

            </div>


            {{-- Side --}}
            <div class="result-detail-side">

                {{-- Time --}}
                <div class="result-card">

                    <div class="result-card-header">

                        <div>

                            <h2>
                                Thời gian
                            </h2>

                            <p>
                                Các mốc thời gian liên quan.
                            </p>

                        </div>

                    </div>


                    <div class="result-side-list">

                        {{-- Resulted at --}}
                        <div class="result-side-item">

                            <div class="result-side-icon">
                                R
                            </div>

                            <div>

                                <div class="result-info-label">
                                    Thời gian trả kết quả
                                </div>

                                <div class="result-side-value">

                                    @if ($result->resulted_at)

                                                                    {{ $result->resulted_at->format(
                                            'd/m/Y H:i:s'
                                        ) }}

                                    @else

                                        Chưa có

                                    @endif

                                </div>

                            </div>

                        </div>


                        {{-- Started --}}
                        <div class="result-side-item">

                            <div class="result-side-icon">
                                S
                            </div>

                            <div>

                                <div class="result-info-label">
                                    Bắt đầu thực hiện
                                </div>

                                <div class="result-side-value">

                                    @if ($item?->started_at)

                                                                    {{ $item->started_at->format(
                                            'd/m/Y H:i:s'
                                        ) }}

                                    @else

                                        —

                                    @endif

                                </div>

                            </div>

                        </div>


                        {{-- Completed --}}
                        <div class="result-side-item">

                            <div class="result-side-icon">
                                F
                            </div>

                            <div>

                                <div class="result-info-label">
                                    Hoàn thành dịch vụ
                                </div>

                                <div class="result-side-value">

                                    @if ($item?->completed_at)

                                                                    {{ $item->completed_at->format(
                                            'd/m/Y H:i:s'
                                        ) }}

                                    @else

                                        —

                                    @endif

                                </div>

                            </div>

                        </div>


                        {{-- Created --}}
                        <div class="result-side-item">

                            <div class="result-side-icon">
                                C
                            </div>

                            <div>

                                <div class="result-info-label">
                                    Ngày tạo bản ghi
                                </div>

                                <div class="result-side-value">

                                    @if ($result->created_at)

                                                                    {{ $result->created_at->format(
                                            'd/m/Y H:i:s'
                                        ) }}

                                    @else

                                        —

                                    @endif

                                </div>

                            </div>

                        </div>


                        {{-- Updated --}}
                        <div class="result-side-item">

                            <div class="result-side-icon">
                                U
                            </div>

                            <div>

                                <div class="result-info-label">
                                    Cập nhật gần nhất
                                </div>

                                <div class="result-side-value">

                                    @if ($result->updated_at)

                                                                    {{ $result->updated_at->format(
                                            'd/m/Y H:i:s'
                                        ) }}

                                    @else

                                        —

                                    @endif

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- Files --}}
                <div class="result-card">

                    <div class="result-card-header">

                        <div>

                            <h2>
                                Tệp kết quả
                            </h2>

                            <p>
                                PDF và ảnh liên quan đến kết quả.
                            </p>

                        </div>

                    </div>


                    <div class="result-files">

                        {{-- PDF --}}
                        <div class="result-file-item">

                            <div class="result-file-top">

                                <div class="result-file-icon">
                                    PDF
                                </div>

                                <div>

                                    <strong>
                                        Phiếu kết quả PDF
                                    </strong>

                                    <span>
                                        {{ $result->result_pdf_path
        ? 'Có tệp'
        : 'Chưa có tệp' }}
                                    </span>

                                </div>

                            </div>


                            @if ($result->result_pdf_path)

                                <div class="result-file-path">
                                    {{ $result->result_pdf_path }}
                                </div>

                                <a href="{{ $pdfUrl }}" target="_blank" rel="noopener noreferrer" class="result-file-link">
                                    Xem PDF
                                </a>

                            @endif

                        </div>


                        {{-- Image --}}
                        <div class="result-file-item">

                            <div class="result-file-top">

                                <div class="result-file-icon image">
                                    IMG
                                </div>

                                <div>

                                    <strong>
                                        Ảnh kết quả
                                    </strong>

                                    <span>
                                        {{ $result->result_image_path
        ? 'Có ảnh'
        : 'Chưa có ảnh' }}
                                    </span>

                                </div>

                            </div>


                            @if ($result->result_image_path)

                                <div class="result-file-path">
                                    {{ $result->result_image_path }}
                                </div>

                                <a href="{{ $imageUrl }}" target="_blank" rel="noopener noreferrer" class="result-file-link">
                                    Xem ảnh
                                </a>

                            @endif

                        </div>

                    </div>

                </div>

            </div>

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
                    Kết quả dịch vụ đã được xác nhận không được
                    hard-delete. Mọi chỉnh sửa phải được thực hiện
                    có kiểm soát để bảo toàn lịch sử y tế.
                </p>

            </div>

        </div>

    </div>


    <style>
        .service-result-show-page {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            width: 100%;

            color: var(--text);
        }

        .service-result-show-page * {
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

            margin-bottom: 18px;
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
        }

        .result-header p {
            margin: 8px 0 0;

            color: var(--muted);

            font-size: 14px;

            line-height: 1.6;
        }

        .result-header-badge {
            display: inline-flex;
            align-items: center;

            gap: 7px;

            padding: 8px 13px;

            border-radius: 999px;

            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        .result-header-badge span {
            width: 7px;
            height: 7px;

            border-radius: 50%;
        }

        .result-header-badge.verified {
            border: 1px solid #bbf7d0;

            background: #f0fdf4;

            color: #15803d;
        }

        .result-header-badge.verified span {
            background: #22c55e;
        }

        .result-header-badge.pending {
            border: 1px solid #fde68a;

            background: #fffbeb;

            color: #b45309;
        }

        .result-header-badge.pending span {
            background: #f59e0b;
        }


        /* =========================
                       TOP ACTION
                    ========================= */

        .result-top-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 12px;

            margin-bottom: 20px;
        }

        .result-back-btn {
            color: #475569;

            font-size: 13px;
            font-weight: 700;

            text-decoration: none;
        }

        .result-back-btn:hover {
            color: var(--primary);
        }

        .result-order-btn {
            display: inline-flex;

            height: 38px;

            align-items: center;
            justify-content: center;

            padding: 0 14px;

            border: 1px solid #bfdbfe;
            border-radius: 9px;

            background: #ffffff;

            color: #2563eb;

            font-size: 12px;
            font-weight: 700;

            text-decoration: none;
        }

        .result-order-btn:hover {
            background: #eff6ff;
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


        /* =========================
                       INFO
                    ========================= */

        .result-info-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));
        }

        .result-info-item {
            padding: 18px 20px;

            border-right: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;
        }

        .result-info-item:nth-child(4n) {
            border-right: none;
        }

        .result-info-label {
            margin-bottom: 7px;

            color: #64748b;

            font-size: 10px;
            font-weight: 800;

            letter-spacing: .05em;

            text-transform: uppercase;
        }

        .result-code {
            display: inline-flex;

            padding: 6px 9px;

            border: 1px solid #dbeafe;
            border-radius: 8px;

            background: #eff6ff;

            color: #1d4ed8;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 11px;
            font-weight: 800;
        }

        .result-secondary-code {
            color: #334155;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 11px;
            font-weight: 750;
        }

        .result-service,
        .result-person {
            display: grid;

            gap: 3px;
        }

        .result-service strong,
        .result-person strong {
            color: #0f172a;

            font-size: 12px;
            font-weight: 700;
        }

        .result-service span,
        .result-person span {
            color: #94a3b8;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 10px;
        }

        .result-price {
            color: #15803d;

            font-size: 13px;
            font-weight: 800;
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
        }

        .result-not-verified {
            color: #b45309;

            font-size: 11px;
            font-weight: 700;
        }

        .result-empty-value {
            color: #94a3b8;

            font-size: 12px;
        }


        /* =========================
                       STATUS
                    ========================= */

        .result-status-badge {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 9px;
            font-weight: 800;
        }

        .status-pending {
            background: #fef3c7;

            color: #b45309;
        }

        .status-processing {
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
                       DETAIL LAYOUT
                    ========================= */

        .result-detail-layout {
            display: grid;

            grid-template-columns:
                minmax(0, 1.7fr) minmax(300px, .8fr);

            gap: 22px;

            align-items: start;
        }

        .result-detail-side {
            display: grid;

            gap: 0;
        }


        /* =========================
                       TEXT RESULT
                    ========================= */

        .result-text-section {
            padding: 20px;

            border-bottom: 1px solid #edf2f7;
        }

        .result-text-section:last-child {
            border-bottom: none;
        }

        .result-text-content {
            color: #334155;

            font-size: 13px;

            line-height: 1.75;

            white-space: pre-line;
        }

        .result-conclusion-section {
            background: #f8fafc;
        }

        .result-conclusion {
            color: #0f172a;

            font-size: 14px;
            font-weight: 650;

            line-height: 1.75;

            white-space: pre-line;
        }


        /* =========================
                       PARAMETERS
                    ========================= */

        .result-parameters {
            padding: 20px;

            background: #0f172a;
        }

        .result-parameters pre {
            overflow-x: auto;

            margin: 0;

            color: #e2e8f0;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 12px;

            line-height: 1.7;

            white-space: pre-wrap;
            word-break: break-word;
        }


        /* =========================
                       SIDE INFO
                    ========================= */

        .result-side-list {
            display: grid;
        }

        .result-side-item {
            display: flex;
            align-items: flex-start;

            gap: 12px;

            padding: 16px 18px;

            border-bottom: 1px solid #edf2f7;
        }

        .result-side-item:last-child {
            border-bottom: none;
        }

        .result-side-icon {
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

        .result-side-value {
            color: #334155;

            font-size: 11px;

            line-height: 1.5;
        }


        /* =========================
                       FILES
                    ========================= */

        .result-files {
            display: grid;

            gap: 0;
        }

        .result-file-item {
            padding: 16px 18px;

            border-bottom: 1px solid #edf2f7;
        }

        .result-file-item:last-child {
            border-bottom: none;
        }

        .result-file-top {
            display: flex;
            align-items: center;

            gap: 11px;
        }

        .result-file-icon {
            display: flex;

            width: 38px;
            height: 38px;

            flex: 0 0 38px;

            align-items: center;
            justify-content: center;

            border-radius: 9px;

            background: #fee2e2;

            color: #b91c1c;

            font-size: 9px;
            font-weight: 800;
        }

        .result-file-icon.image {
            background: #e0f2fe;

            color: #0369a1;
        }

        .result-file-top strong {
            display: block;

            color: #0f172a;

            font-size: 11px;
        }

        .result-file-top span {
            display: block;

            margin-top: 3px;

            color: #94a3b8;

            font-size: 10px;
        }

        .result-file-path {
            overflow-wrap: anywhere;

            margin-top: 10px;

            padding: 8px 10px;

            border-radius: 7px;

            background: #f8fafc;

            color: #64748b;

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 9px;

            line-height: 1.5;
        }

        .result-file-link {
            display: inline-flex;

            height: 32px;

            align-items: center;
            justify-content: center;

            margin-top: 10px;

            padding: 0 11px;

            border: 1px solid #bfdbfe;
            border-radius: 7px;

            background: #ffffff;

            color: #2563eb;

            font-size: 10px;
            font-weight: 700;

            text-decoration: none;
        }

        .result-file-link:hover {
            background: #eff6ff;
        }


        /* =========================
                       NO DATA
                    ========================= */

        .result-no-data {
            padding: 42px 20px;

            text-align: center;
        }

        .result-no-data-icon {
            display: flex;

            width: 42px;
            height: 42px;

            align-items: center;
            justify-content: center;

            margin: 0 auto 10px;

            border-radius: 50%;

            background: #f1f5f9;

            color: #64748b;

            font-weight: 800;
        }

        .result-no-data strong {
            display: block;

            color: #0f172a;

            font-size: 13px;
        }

        .result-no-data p {
            margin: 6px 0 0;

            color: #64748b;

            font-size: 11px;
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

            .result-info-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .result-info-item:nth-child(4n) {
                border-right: 1px solid #edf2f7;
            }

            .result-info-item:nth-child(2n) {
                border-right: none;
            }

        }


        @media (max-width: 950px) {

            .result-detail-layout {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 768px) {

            .result-header {
                flex-direction: column;
            }

            .result-header h1 {
                font-size: 24px;
            }

            .result-top-actions {
                align-items: flex-start;

                flex-direction: column;
            }

            .result-info-grid {
                grid-template-columns: 1fr;
            }

            .result-info-item {
                border-right: none !important;
            }

            .result-top-action-group {
                display: flex;
                align-items: center;
                gap: 9px;
            }

            .result-edit-btn {
                display: inline-flex;

                height: 38px;

                align-items: center;
                justify-content: center;

                padding: 0 14px;

                border: 1px solid #2563eb;
                border-radius: 9px;

                background: #2563eb;

                color: #ffffff;

                font-size: 12px;
                font-weight: 700;

                text-decoration: none;
            }

            .result-edit-btn:hover {
                background: #1d4ed8;
            }

            @media (max-width: 768px) {

                .result-top-action-group {
                    width: 100%;

                    flex-direction: column;
                    align-items: stretch;
                }

                .result-edit-btn,
                .result-order-btn {
                    width: 100%;
                }

            }
        }
    </style>

@endsection