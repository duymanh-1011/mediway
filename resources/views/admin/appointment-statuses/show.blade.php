@extends('admin.layouts.app')
@section('admin-page', 'appointment-statuses-show')

@section(
    'title',
    'Chi tiết trạng thái lịch hẹn - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Cấu hình trạng thái</span>
    <span aria-hidden="true">/</span>
    <span>Trạng thái lịch hẹn</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">{{ \App\Models\AdminDisplay::label($appointmentStatus->name) }}</strong>
@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                {{ \App\Models\AdminDisplay::label($appointmentStatus->name) }}
            </h1>

            <p class="page-description">
                Chi tiết cấu hình trạng thái lịch hẹn.
            </p>

        </div>

        <div class="header-actions">

            <a
                href="{{ route(
                    'admin.appointment-statuses.index'
                ) }}"
                class="btn btn-secondary"
            >
                ← Danh sách
            </a>

            <a
                href="{{ route(
                    'admin.appointment-statuses.edit',
                    $appointmentStatus
                ) }}"
                class="btn btn-primary"
            >
                Sửa cấu hình
            </a>

        </div>

    </div>

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-label">
                Thứ tự xử lý
            </div>

            <div class="stat-value">
                {{ $appointmentStatus->process_order }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Cho phép chuyển tiếp
            </div>

            <div class="stat-text">

                @if (
                    $appointmentStatus
                        ->allows_transition
                )

                    <span class="badge badge-success">
                        Có
                    </span>

                @else

                    <span class="badge badge-secondary">
                        Không
                    </span>

                @endif

            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Hoạt động
            </div>

            <div class="stat-text">

                @if (
                    $appointmentStatus
                        ->is_active
                )

                    <span class="badge badge-success">
                        Đang hoạt động
                    </span>

                @else

                    <span class="badge badge-danger">
                        Ngừng hoạt động
                    </span>

                @endif

            </div>

        </div>

    </div>

    <div class="card">

        <h3 class="section-title">
            Thông tin trạng thái
        </h3>

        <div class="detail-row">

            <div class="detail-label">
                ID
            </div>

            <div class="detail-value">
                {{ $appointmentStatus->id }}
            </div>

        </div>

        <div class="detail-row">

            <div class="detail-label">
                Tên trạng thái
            </div>

            <div class="detail-value">
                <strong>
                    {{ \App\Models\AdminDisplay::label($appointmentStatus->name) }}
                </strong>
            </div>

        </div>

        <div class="detail-row">

            <div class="detail-label">
                Mô tả
            </div>

            <div class="detail-value">
                {{ \App\Models\AdminDisplay::text($appointmentStatus->description ?? '—') }}
            </div>

        </div>

        <div class="detail-row">

            <div class="detail-label">
                Thứ tự xử lý
            </div>

            <div class="detail-value">
                {{ $appointmentStatus
                    ->process_order }}
            </div>

        </div>

        <div class="detail-row">

            <div class="detail-label">
                Chuyển tiếp
            </div>

            <div class="detail-value">

                {{ $appointmentStatus
                    ->allows_transition
                    ? 'Được phép chuyển sang trạng thái tiếp theo'
                    : 'Không cho phép chuyển tiếp' }}

            </div>

        </div>

        <div class="detail-row">

            <div class="detail-label">
                Trạng thái hoạt động
            </div>

            <div class="detail-value">

                {{ $appointmentStatus
                    ->is_active
                    ? 'Đang hoạt động'
                    : 'Ngừng hoạt động' }}

            </div>

        </div>

        <div class="detail-row">

            <div class="detail-label">
                Cập nhật lần cuối
            </div>

            <div class="detail-value">

                {{ $appointmentStatus
                    ->updated_at
                    ?->format(
                        'd/m/Y H:i'
                    ) }}

            </div>

        </div>

    </div>

    <div class="information-box">

        <strong>Nguyên tắc:</strong>

        tên trạng thái được giữ cố định vì được sử dụng
        trong logic nghiệp vụ. Admin chỉ cấu hình mô tả,
        thứ tự xử lý, khả năng chuyển tiếp và trạng thái hoạt động.

    </div>

@endsection
