@extends('admin.layouts.app')
@section('admin-page', 'service-results-show')

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

        $pdfUrl = $result->hasDocument('pdf') ? route('admin.service-results.document', [$result->id, 'pdf']) : null;
        $imageUrl = $result->hasDocument('image') ? route('admin.service-results.document', [$result->id, 'image']) : null;
    @endphp


    <div class="service-result-show-page">

        {{-- Breadcrumb --}}
        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Dịch vụ</span>
            <span aria-hidden="true">/</span>

            <a href="{{ route('admin.service-results.index') }}">
                Kết quả dịch vụ
            </a>

            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                {{ $service?->service_code ?? 'Chi tiết' }}
            </strong>

        </nav>


        {{-- Header --}}
        <div class="admin-info-header result-header">

            <div>


                <h1>
                    Chi tiết kết quả dịch vụ
                </h1>

                <p>
                    Xem nội dung kết quả, kết luận chuyên môn,
                    thông số, người xác nhận và dữ liệu liên quan.
                </p>

            </div>


            @if ($result->verified_by_user_id)

                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'VERIFIED'])

            @else

                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'UNVERIFIED'])

            @endif

        </div>


        {{-- Back --}}
        <div class="result-top-actions">

            <a href="{{ route('admin.service-results.index') }}" class="admin-btn admin-btn-secondary">
                ← Quay lại
            </a>

            <div class="admin-actions">

                @if ($order)

                            <a href="{{ route(
                        'admin.service-orders.show',
                        $order->id
                    ) }}" class="admin-btn admin-btn-detail">
                                Xem phiếu chỉ định
                            </a>

                @endif

                <a href="{{ route(
        'admin.service-results.edit',
        $result->id
    ) }}" class="admin-btn admin-btn-secondary">
                    Sửa
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
                        Thông tin dịch vụ và phiếu chỉ định liên quan.
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



                        @include('admin.layouts.partials.status-badge', ['badgeCode' => $serviceStatus->name, 'badgeContext' => 'service'])

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

                        @include('admin.layouts.partials.status-badge', ['badgeCode' => 'UNVERIFIED'])

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
                                Các chỉ số được ghi nhận trong kết quả dịch vụ.
                            </p>

                        </div>

                    </div>


                    @if (
                                        is_array($result->result_parameters)
                                        &&
                                        count($result->result_parameters) > 0
                                    )

                                    <div class="result-parameters">
 @include('admin.layouts.partials.value-list', ['valueRows' => app(\App\Models\AuditValuePresenter::class)->rows($result->result_parameters, '', true)])
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
                                Chưa có chỉ số được ghi nhận cho kết quả này.
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
                                        {{ $pdfUrl
        ? 'Có tệp'
        : 'Chưa có tệp' }}
                                    </span>

                                </div>

                            </div>


                            @if ($pdfUrl)

                                <div class="result-file-path">
                                    Tệp PDF được bảo vệ
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
                                        {{ $imageUrl
        ? 'Có ảnh'
        : 'Chưa có ảnh' }}
                                    </span>

                                </div>

                            </div>


                            @if ($imageUrl)

                                <div class="result-file-path">
                                    Ảnh kết quả được bảo vệ
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
                    xóa vĩnh viễn. Mọi chỉnh sửa phải được thực hiện
                    có kiểm soát để bảo toàn lịch sử y tế.
                </p>

            </div>

        </div>

    </div>




@endsection
