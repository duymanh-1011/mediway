@extends('admin.layouts.app')
@section('admin-page', 'prescriptions-show')

@section('title', 'Chi tiết đơn thuốc')

@section('content')

    @php
        $pdfUrl = $prescription->hasPdf() ? route('admin.prescriptions.document', $prescription->id) : null;

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
        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Đơn thuốc</span>
            <span aria-hidden="true">/</span>

            <a href="{{ route('admin.prescriptions.index') }}">
                Đơn thuốc
            </a>

            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                {{ $prescription->prescription_code }}
            </strong>

        </nav>


        {{-- Header --}}
        <div class="admin-info-header prescription-header">

            <div>


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
                {{ \App\Models\AdminDisplay::label($prescription->status) }}
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


            @if ($prescription->medicalVisit->status?->name !== 'COMPLETED')
            <a href="{{ route(
        'admin.prescriptions.edit',
        $prescription->id
    ) }}" class="prescription-btn prescription-btn-primary">
                Sửa đơn thuốc
            </a>
            @else
                <p class="form-help">Lượt khám đã hoàn tất. Đơn thuốc được khóa chỉnh sửa.</p>
            @endif

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
                        {{ \App\Models\AdminDisplay::label($prescription->status ?? '—') }}
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

                @if ($pdfUrl)

                    <div class="prescription-file">

                        <div>

                            <span>
                                PDF đơn thuốc
                            </span>

                            <strong>
                                Tệp PDF đơn thuốc được bảo vệ
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
                        Chưa có tệp PDF.
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




@endsection
