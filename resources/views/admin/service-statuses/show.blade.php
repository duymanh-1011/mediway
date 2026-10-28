@extends('admin.layouts.app')
@section('admin-page', 'service-statuses-show')

@section('title', 'Chi tiết trạng thái dịch vụ')

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

            <a href="{{ route('admin.service-statuses.index') }}">
                Trạng thái dịch vụ
            </a>

            <span aria-hidden="true">/</span>

            <strong aria-current="page">@include('admin.layouts.partials.status-badge', ['badgeCode' => $status->name, 'badgeContext' => 'service'])</strong>

        </nav>


        {{-- Header --}}
        <div class="admin-info-header status-show-header">

            <div>


                <h1>
                    Chi tiết trạng thái dịch vụ
                </h1>

                <p>
                    Xem thông tin cấu hình và quy tắc xử lý
                    của trạng thái dịch vụ.
                </p>

            </div>


            {{-- Trạng thái tự động --}}
            @if ($status->is_active)

                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ACTIVE'])

            @else

                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'INACTIVE'])

            @endif

        </div>


        {{-- Actions --}}
        <div class="status-top-actions">

            <a href="{{ route('admin.service-statuses.index') }}" class="admin-btn admin-btn-secondary">← Quay lại</a>

            <a href="{{ route(
        'admin.service-statuses.edit',
        $status->id
    ) }}" class="admin-btn admin-btn-secondary">Sửa</a>

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

                            <div class="status-name">@include('admin.layouts.partials.status-badge', ['badgeCode' => $status->name, 'badgeContext' => 'service'])</div>

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
                                Cho phép chuyển trạng thái
                            </div>

                            @if ($status->allows_transition)

                                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ALLOWED'])

                            @else

                                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'DENIED'])

                            @endif

                        </div>


                        {{-- Usage status - AUTO --}}
                        <div class="status-info-item">

                            <div class="status-info-label">
                                Trạng thái
                            </div>

                            @if ($status->is_active)

                                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ACTIVE'])

                            @else

                                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'INACTIVE'])

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

                                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ACTIVE'])

                            @else

                                @include('admin.layouts.partials.status-badge', ['badgeCode' => 'INACTIVE'])

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
                            Không xóa vĩnh viễn trạng thái đã được sử dụng.
                            Khi không muốn trạng thái tiếp tục được lựa chọn
                            cho nghiệp vụ mới, hãy ngừng kích hoạt cấu hình.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>




@endsection
