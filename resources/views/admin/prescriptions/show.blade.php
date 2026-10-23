@extends('admin.layouts.app')
@section('admin-page', 'prescriptions-show')

@section('title', 'Chi tiết đơn thuốc')

@section('content')

    @php
        $pdfUrl = $prescription->hasPdf() ? route('admin.prescriptions.document', $prescription->id) : null;


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
                @include('admin.layouts.partials.record-code', ['code' => $prescription->prescription_code])
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

            @include('admin.layouts.partials.status-badge', ['badgeCode' => $prescription->status])

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

            <a href="{{ route('admin.prescriptions.index') }}" class="admin-btn admin-btn-secondary">← Quay lại</a>


            @if ($prescription->medicalVisit->status?->name !== 'COMPLETED')
            <a href="{{ route(
        'admin.prescriptions.edit',
        $prescription->id
    ) }}" class="admin-btn admin-btn-secondary">Sửa</a>
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
                        @include('admin.layouts.partials.record-code', ['code' => $prescription->prescription_code])
                    </strong>

                </div>


                <div class="prescription-info-item">

                    <span>
                        Lượt khám
                    </span>

                    <strong>
                        @include('admin.layouts.partials.record-code', ['code' => $visit?->visit_code ?? '—'])
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
                            @include('admin.layouts.partials.record-code', ['code' => $doctor->doctor_code])
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

                    <strong>@include('admin.layouts.partials.status-badge', ['badgeCode' => $prescription->status ?? '—'])</strong>

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


            <div class="admin-table-scroll">
                <table class="admin-table admin-prescription-table">
                    <thead>
                        <tr><th scope="col" class="admin-row-number">STT</th><th>Thuốc</th><th>Liều mỗi lần</th><th>Số lần/ngày</th><th>Số ngày</th><th>Thời điểm dùng</th><th>Cách dùng</th><th>Số lượng</th><th>Hướng dẫn và ghi chú</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($prescription->items as $item)
                            @php($medicine = $medicinesById->get($item->medicine_id))
                            <tr>
                                @include('admin.layouts.partials.row-number', ['rows' => $prescription->items, 'index' => $loop->index])
                                <td class="admin-medicine-info">
                                    <strong>{{ $medicine?->name ?? 'Thuốc không xác định' }}</strong>
                                    <p>@include('admin.layouts.partials.record-code', ['code' => $medicine?->medicine_code ?? '—'])</p>
                                    <p>Hoạt chất: {{ $medicine?->active_ingredient ?? '—' }}</p>
                                    <p>Hàm lượng: {{ $medicine?->strength ?? '—' }}</p>
                                    <p>Dạng bào chế: {{ $medicine?->dosage_form ?? '—' }}</p>
                                    <p>Đường dùng: {{ $medicine?->route_of_administration ?? '—' }}</p>
                                </td>
                                <td>{{ $item->dose_per_use ?? '—' }}</td>
                                <td>{{ $item->times_per_day === null ? '—' : rtrim(rtrim(number_format((float) $item->times_per_day, 2, '.', ''), '0'), '.') }}</td>
                                <td>{{ $item->duration_days === null ? '—' : $item->duration_days.' ngày' }}</td>
                                <td>{{ $item->administration_time ?: '—' }}</td>
                                <td>{{ $item->usage_method ?? '—' }}</td>
                                <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.') }} {{ $item->usage_unit }}</td>
                                <td><p>Hướng dẫn: {{ $item->usage_instructions ?: 'Chưa có.' }}</p><p>Ghi chú: {{ $item->notes ?: 'Chưa có.' }}</p></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="empty-state">Chưa có thuốc trong đơn.</td></tr>
                        @endforelse
                    </tbody>
                </table>
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
                        Tệp đơn thuốc đã lưu, chỉ người có quyền mới được xem.
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
                                class="admin-btn admin-btn-detail">
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
                Không xóa vĩnh viễn sau khi lượt khám hoàn tất.
            </p>

        </div>

    </div>




@endsection
