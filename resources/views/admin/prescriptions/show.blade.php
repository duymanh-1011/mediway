@extends('admin.layouts.app')

@section('title', 'Chi tiết đơn thuốc')

@section('content')

    @php
        $pdfUrl = null;

        if ($prescription->prescription_pdf_path) {
            $pdfPath = preg_replace(
                '#^storage/#',
                '',
                ltrim(
                    $prescription->prescription_pdf_path,
                    '/'
                )
            );

            $pdfUrl = str_starts_with(
                $prescription->prescription_pdf_path,
                'http'
            )
                ? $prescription->prescription_pdf_path
                : asset('storage/' . $pdfPath);
        }

        $statusClass = match (
        strtoupper($prescription->status ?? '')
        ) {
            'ACTIVE' => 'active',
            'COMPLETED' => 'completed',
            'CANCELLED' => 'cancelled',
            default => 'default',
        };
    @endphp


    <div class="prescription-detail-page">

        {{-- Breadcrumb --}}
        <div class="prescription-breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>/</span>

            <a href="{{ route('admin.prescriptions.index') }}">
                Đơn thuốc
            </a>

            <span>/</span>

            <strong>
                {{ $prescription->prescription_code }}
            </strong>

        </div>


        {{-- Header --}}
        <div class="prescription-header">

            <div>

                <div class="prescription-module-label">
                    ĐƠN THUỐC
                </div>

                <h1>
                    Chi tiết đơn thuốc
                </h1>

                <p>
                    Thông tin đơn thuốc và danh sách thuốc đã kê.
                </p>

            </div>

            <span class="
                    prescription-status
                    {{ $statusClass }}
                ">
                {{ $prescription->status }}
            </span>

        </div>


        {{-- Flash --}}
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


        {{-- Actions --}}
        <div class="prescription-actions">

            <a href="{{ route('admin.prescriptions.index') }}" class="prescription-btn prescription-btn-secondary">
                ← Quay lại danh sách
            </a>


            <a href="{{ route(
        'admin.prescriptions.edit',
        $prescription->id
    ) }}" class="prescription-btn prescription-btn-primary">
                Sửa đơn thuốc
            </a>

        </div>


        {{-- General information --}}
        <div class="prescription-card">

            <div class="prescription-card-header">

                <div>

                    <h2>
                        Thông tin đơn thuốc
                    </h2>

                    <p>
                        Thông tin nghiệp vụ chính của đơn.
                    </p>

                </div>

            </div>


            <div class="prescription-info-grid">

                <div class="prescription-info-item">

                    <span>
                        Mã đơn thuốc
                    </span>

                    <strong class="prescription-code">
                        {{ $prescription->prescription_code }}
                    </strong>

                </div>


                <div class="prescription-info-item">

                    <span>
                        Lượt khám
                    </span>

                    <strong>
                        {{ $visit?->visit_code ?? '—' }}
                    </strong>

                </div>


                <div class="prescription-info-item">

                    <span>
                        Bác sĩ kê đơn
                    </span>

                    <strong>
                        {{ $doctor?->full_name ?? '—' }}
                    </strong>

                    @if ($doctor?->doctor_code)

                        <small>
                            {{ $doctor->doctor_code }}
                        </small>

                    @endif

                </div>


                <div class="prescription-info-item">

                    <span>
                        Thời gian kê
                    </span>

                    <strong>
                        {{
        $prescription->prescribed_at
        ? $prescription->prescribed_at->format(
            'd/m/Y H:i'
        )
        : '—'
                        }}
                    </strong>

                </div>


                <div class="prescription-info-item">

                    <span>
                        Trạng thái
                    </span>

                    <strong>
                        {{ $prescription->status ?? '—' }}
                    </strong>

                </div>


                <div class="prescription-info-item">

                    <span>
                        Số loại thuốc
                    </span>

                    <strong>
                        {{ $prescription->items->count() }}
                    </strong>

                </div>

            </div>

        </div>


        {{-- Instructions --}}
        <div class="prescription-card">

            <div class="prescription-card-header">

                <div>

                    <h2>
                        Hướng dẫn và ghi chú
                    </h2>

                    <p>
                        Nội dung hướng dẫn chung của đơn thuốc.
                    </p>

                </div>

            </div>


            <div class="prescription-content-grid">

                <div class="prescription-content-item">

                    <span>
                        Hướng dẫn chung
                    </span>

                    <div>
                        {{
        $prescription->general_instructions
        ?: 'Không có.'
                        }}
                    </div>

                </div>


                <div class="prescription-content-item">

                    <span>
                        Ghi chú bác sĩ
                    </span>

                    <div>
                        {{
        $prescription->doctor_notes
        ?: 'Không có.'
                        }}
                    </div>

                </div>

            </div>

        </div>


        {{-- Prescription items --}}
        <div class="prescription-card">

            <div class="prescription-card-header">

                <div>

                    <h2>
                        Thuốc trong đơn
                    </h2>

                    <p>
                        Chi tiết liều lượng và cách sử dụng.
                    </p>

                </div>

                <span class="prescription-count">
                    {{ $prescription->items->count() }} thuốc
                </span>

            </div>


            <div class="prescription-items">

                @forelse ($prescription->items as $index => $item)

                        @php
                            $medicine = $medicinesById->get(
                                $item->medicine_id
                            );
                        @endphp

                        <div class="prescription-item-card">

                            <div class="prescription-item-number">
                                {{ $index + 1 }}
                            </div>


                            <div class="prescription-item-main">

                                <div class="prescription-item-header">

                                    <div>

                                        <h3>
                                            {{ $medicine?->name ?? 'Thuốc không xác định' }}
                                        </h3>

                                        <div class="prescription-item-code">
                                            {{ $medicine?->medicine_code ?? '—' }}
                                        </div>

                                    </div>


                                    <div class="prescription-item-quantity">

                                        <span>
                                            Số lượng
                                        </span>

                                        <strong>
                                            {{
                    rtrim(
                        rtrim(
                            number_format(
                                (float) $item->quantity,
                                2,
                                '.',
                                ''
                            ),
                            '0'
                        ),
                        '.'
                    )
                                                }}

                                            {{ $item->usage_unit ?? '' }}
                                        </strong>

                                    </div>

                                </div>


                                <div class="medicine-summary-grid">

                                    <div>

                                        <span>
                                            Hoạt chất
                                        </span>

                                        <strong>
                                            {{ $medicine?->active_ingredient ?? '—' }}
                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Hàm lượng
                                        </span>

                                        <strong>
                                            {{ $medicine?->strength ?? '—' }}
                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Dạng bào chế
                                        </span>

                                        <strong>
                                            {{ $medicine?->dosage_form ?? '—' }}
                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Đường dùng
                                        </span>

                                        <strong>
                                            {{
                    $medicine?->route_of_administration
                    ?? '—'
                                                }}
                                        </strong>

                                    </div>

                                </div>


                                <div class="usage-grid">

                                    <div>

                                        <span>
                                            Liều mỗi lần
                                        </span>

                                        <strong>
                                            {{ $item->dose_per_use ?? '—' }}
                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Số lần / ngày
                                        </span>

                                        <strong>
                                            {{
                    $item->times_per_day !== null
                    ? rtrim(
                        rtrim(
                            number_format(
                                (float) $item->times_per_day,
                                2,
                                '.',
                                ''
                            ),
                            '0'
                        ),
                        '.'
                    )
                    : '—'
                                                }}
                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Số ngày
                                        </span>

                                        <strong>
                                            {{
                    $item->duration_days !== null
                    ? $item->duration_days . ' ngày'
                    : '—'
                                                }}
                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Thời điểm dùng
                                        </span>

                                        <strong>
                                            {{
                    $item->administration_time
                    ?: '—'
                                                }}
                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Cách dùng
                                        </span>

                                        <strong>
                                            {{ $item->usage_method ?? '—' }}
                                        </strong>

                                    </div>

                                </div>


                                <div class="prescription-item-text">

                                    <div>

                                        <span>
                                            Hướng dẫn sử dụng
                                        </span>

                                        <p>
                                            {{
                    $item->usage_instructions
                    ?: 'Không có.'
                                                }}
                                        </p>

                                    </div>


                                    <div>

                                        <span>
                                            Ghi chú
                                        </span>

                                        <p>
                                            {{
                    $item->notes
                    ?: 'Không có.'
                                                }}
                                        </p>

                                    </div>

                                </div>

                            </div>

                        </div>

                @empty

                    <div class="prescription-empty-state">

                        <strong>
                            Chưa có thuốc trong đơn
                        </strong>

                        <p>
                            Đơn thuốc chưa có prescription_items.
                        </p>

                    </div>

                @endforelse

            </div>

        </div>


        {{-- PDF --}}
        <div class="prescription-card">

            <div class="prescription-card-header">

                <div>

                    <h2>
                        Tệp đơn thuốc
                    </h2>

                    <p>
                        Đường dẫn PDF lưu trong dữ liệu.
                    </p>

                </div>

            </div>


            <div class="prescription-file-section">

                @if ($prescription->prescription_pdf_path)

                    <div class="prescription-file">

                        <div>

                            <span>
                                PDF đơn thuốc
                            </span>

                            <strong>
                                {{ $prescription->prescription_pdf_path }}
                            </strong>

                        </div>


                        @if ($pdfUrl)

                            <a href="{{ $pdfUrl }}" target="_blank" rel="noopener"
                                class="prescription-btn prescription-btn-secondary">
                                Mở PDF
                            </a>

                        @endif

                    </div>

                @else

                    <span class="prescription-empty">
                        Chưa có file PDF.
                    </span>

                @endif

            </div>

        </div>


        {{-- System information --}}
        <div class="prescription-card">

            <div class="prescription-card-header">

                <h2>
                    Thông tin hệ thống
                </h2>

            </div>


            <div class="prescription-system-grid">

                <div>

                    <span>
                        Tạo lúc
                    </span>

                    <strong>
                        {{
        $prescription->created_at
        ? $prescription->created_at->format(
            'd/m/Y H:i:s'
        )
        : '—'
                        }}
                    </strong>

                </div>


                <div>

                    <span>
                        Cập nhật lúc
                    </span>

                    <strong>
                        {{
        $prescription->updated_at
        ? $prescription->updated_at->format(
            'd/m/Y H:i:s'
        )
        : '—'
                        }}
                    </strong>

                </div>

            </div>

        </div>


        <div class="prescription-policy">

            <strong>
                Chính sách dữ liệu
            </strong>

            <p>
                Đơn thuốc là dữ liệu y tế.
                Không hard-delete sau khi lượt khám hoàn tất.
            </p>

        </div>

    </div>


    <style>
        .prescription-detail-page {
            --primary: #2563eb;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            width: 100%;
            color: var(--text);
        }

        .prescription-detail-page * {
            box-sizing: border-box;
        }

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

        .prescription-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
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
            font-size: 29px;
            font-weight: 750;
        }

        .prescription-header p {
            margin: 7px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .prescription-status {
            display: inline-flex;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 10px;
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

        .prescription-alert {
            margin-bottom: 18px;
            padding: 13px 15px;
            border-radius: 10px;
            font-size: 12px;
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

        .prescription-actions {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
            margin-bottom: 20px;
        }

        .prescription-btn {
            display: inline-flex;
            height: 38px;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
        }

        .prescription-btn-primary {
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .prescription-btn-secondary {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
        }

        .prescription-card {
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 3px 14px rgba(15, 23, 42, .035);
        }

        .prescription-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 17px 20px;
            border-bottom: 1px solid var(--border);
        }

        .prescription-card-header h2 {
            margin: 0;
            font-size: 15px;
        }

        .prescription-card-header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 11px;
        }

        .prescription-info-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .prescription-info-item {
            padding: 18px 20px;
            border-right: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;
        }

        .prescription-info-item:nth-child(3n) {
            border-right: none;
        }

        .prescription-info-item span,
        .prescription-content-item>span,
        .usage-grid span,
        .medicine-summary-grid span,
        .prescription-item-text span,
        .prescription-system-grid span,
        .prescription-file span,
        .prescription-item-quantity span {
            display: block;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .prescription-info-item strong {
            color: #0f172a;
            font-size: 12px;
        }

        .prescription-info-item small {
            display: block;
            margin-top: 4px;
            color: #94a3b8;
            font-size: 9px;
        }

        .prescription-code,
        .prescription-item-code {
            color: #1d4ed8 !important;
            font-family: Consolas, Monaco, monospace;
        }

        .prescription-content-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .prescription-content-item {
            min-height: 110px;
            padding: 20px;
            border-right: 1px solid #edf2f7;
        }

        .prescription-content-item:last-child {
            border-right: none;
        }

        .prescription-content-item div {
            color: #334155;
            font-size: 12px;
            line-height: 1.7;
            white-space: pre-line;
        }

        .prescription-count {
            padding: 5px 9px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-size: 10px;
            font-weight: 800;
        }

        .prescription-items {
            padding: 18px;
        }

        .prescription-item-card {
            display: flex;
            gap: 14px;
            margin-bottom: 15px;
            padding: 17px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .prescription-item-card:last-child {
            margin-bottom: 0;
        }

        .prescription-item-number {
            display: flex;
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #eff6ff;
            color: #2563eb;
            font-size: 11px;
            font-weight: 800;
        }

        .prescription-item-main {
            width: 100%;
            min-width: 0;
        }

        .prescription-item-header {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 15px;
        }

        .prescription-item-header h3 {
            margin: 0 0 4px;
            font-size: 14px;
        }

        .prescription-item-code {
            font-size: 10px;
        }

        .prescription-item-quantity {
            text-align: right;
        }

        .prescription-item-quantity strong {
            font-size: 12px;
        }

        .medicine-summary-grid,
        .usage-grid {
            display: grid;
            gap: 12px;
            margin-bottom: 14px;
        }

        .medicine-summary-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            padding: 13px;
            border-radius: 10px;
            background: #f8fafc;
        }

        .usage-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .medicine-summary-grid strong,
        .usage-grid strong {
            color: #334155;
            font-size: 10px;
        }

        .prescription-item-text {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .prescription-item-text>div {
            padding: 12px;
            border: 1px solid #edf2f7;
            border-radius: 9px;
        }

        .prescription-item-text p {
            margin: 0;
            color: #475569;
            font-size: 10px;
            line-height: 1.6;
            white-space: pre-line;
        }

        .prescription-file-section {
            padding: 20px;
        }

        .prescription-file {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .prescription-file strong {
            font-size: 11px;
            word-break: break-all;
        }

        .prescription-system-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .prescription-system-grid>div {
            padding: 18px 20px;
            border-right: 1px solid #edf2f7;
        }

        .prescription-system-grid>div:last-child {
            border-right: none;
        }

        .prescription-system-grid strong {
            font-size: 11px;
        }

        .prescription-policy {
            padding: 15px 17px;
            border: 1px solid #fde68a;
            border-radius: 11px;
            background: #fffbeb;
        }

        .prescription-policy strong {
            display: block;
            margin-bottom: 5px;
            color: #92400e;
            font-size: 11px;
        }

        .prescription-policy p {
            margin: 0;
            color: #78350f;
            font-size: 10px;
            line-height: 1.6;
        }

        .prescription-empty-state {
            padding: 35px 15px;
            text-align: center;
        }

        .prescription-empty-state p,
        .prescription-empty {
            color: #94a3b8;
            font-size: 10px;
        }

        @media (max-width: 1000px) {
            .prescription-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .prescription-info-item:nth-child(3n) {
                border-right: 1px solid #edf2f7;
            }

            .prescription-info-item:nth-child(2n) {
                border-right: none;
            }

            .medicine-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .usage-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {

            .prescription-header,
            .prescription-item-header,
            .prescription-file {
                flex-direction: column;
            }

            .prescription-actions {
                flex-direction: column;
            }

            .prescription-btn {
                width: 100%;
            }

            .prescription-info-grid,
            .prescription-content-grid,
            .prescription-item-text,
            .prescription-system-grid,
            .medicine-summary-grid,
            .usage-grid {
                grid-template-columns: 1fr;
            }

            .prescription-info-item,
            .prescription-content-item,
            .prescription-system-grid>div {
                border-right: none;
            }

            .prescription-item-quantity {
                text-align: left;
            }
        }
    </style>

@endsection