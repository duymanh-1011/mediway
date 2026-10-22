@extends('admin.layouts.app')
@section('admin-page', 'payment-statuses-show')

@section('title', 'Chi tiết trạng thái thanh toán')

@section('content')

    <div class="status-show-page">

        {{-- Breadcrumb --}}
        <nav class="admin-breadcrumb" aria-label="Đường dẫn">

            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Cấu hình trạng thái</span>
            <span aria-hidden="true">/</span>

            <a href="{{ route('admin.payment-statuses.index') }}">
                Trạng thái thanh toán
            </a>

            <span aria-hidden="true">/</span>

            <strong aria-current="page">
                {{ \App\Models\AdminDisplay::label($status->name) }}
            </strong>

        </nav>


        {{-- Header --}}
        <div class="admin-info-header status-show-header">

            <div>


                <h1>
                    Chi tiết trạng thái thanh toán
                </h1>

                <p>
                    Xem thông tin và quy tắc xử lý của trạng thái thanh toán.
                </p>

            </div>


            @if ($status->is_active)

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

            <a href="{{ route('admin.payment-statuses.index') }}" class="status-back-btn">
                ← Quay lại danh sách
            </a>

            <a href="{{ route(
        'admin.payment-statuses.edit',
        $status->id
    ) }}" class="status-edit-btn">Sửa</a>

        </div>


        <div class="status-detail-layout">

            {{-- Main --}}
            <div class="status-detail-main">

                <div class="status-card">

                    <div class="status-card-header">

                        <div>

                            <h2>
                                Thông tin trạng thái
                            </h2>

                            <p>
                                Các thông tin chính dùng trong quy trình thanh toán.
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
                                {{ \App\Models\AdminDisplay::label($status->name) }}
                            </div>

                        </div>


                        {{-- Process order --}}
                        <div class="status-info-item">

                            <div class="status-info-label">
                                Thứ tự xử lý
                            </div>

                            <div class="status-order">
                                {{ $status->process_order }}
                            </div>

                        </div>


                        {{-- Service execution --}}
                        <div class="status-info-item">

                            <div class="status-info-label">
                                Cho phép thực hiện dịch vụ
                            </div>

                            @if ($status->allows_service_execution)

                                <span class="status-badge badge-yes">
                                    Cho phép
                                </span>

                            @else

                                <span class="status-badge badge-no">
                                    Không cho phép
                                </span>

                            @endif

                        </div>


                        {{-- Usage --}}
                        <div class="status-info-item">

                            <div class="status-info-label">
                                Trạng thái sử dụng
                            </div>

                            @if ($status->is_active)

                                <span class="status-badge badge-active">
                                    Đang sử dụng
                                </span>

                            @else

                                <span class="status-badge badge-inactive">
                                    Ngừng sử dụng
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

                                                                    {{ $status->created_at->format(
                                            'd/m/Y H:i:s'
                                        ) }}

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

                                                                    {{ $status->updated_at->format(
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


                <div class="status-policy-box">

                    <div class="status-policy-icon">
                        !
                    </div>

                    <div>

                        <strong>
                            Chính sách dữ liệu
                        </strong>

                        <p>
                            Không đổi tên trạng thái tùy tiện.
                            Không thay đổi thứ tự xử lý tại màn hình sửa.
                            Không hard-delete trạng thái đã được sử dụng.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>




@endsection
