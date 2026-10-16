@extends('admin.layouts.app')

@section(
    'title',
    'Chi tiết trạng thái lịch hẹn - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">

        Admin /
        Cấu hình trạng thái /
        Trạng thái lịch hẹn /
        {{ $appointmentStatus->name }}

    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                {{ $appointmentStatus->name }}
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
                        ACTIVE
                    </span>

                @else

                    <span class="badge badge-danger">
                        INACTIVE
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
                    {{ $appointmentStatus->name }}
                </strong>
            </div>

        </div>

        <div class="detail-row">

            <div class="detail-label">
                Mô tả
            </div>

            <div class="detail-value">
                {{ $appointmentStatus
                    ->description
                    ?? '—' }}
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

@push('styles')

<style>

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;

        gap: 20px;

        margin-bottom: 20px;
    }

    .header-actions {
        display: flex;

        gap: 8px;
    }

    .stats-grid {
        display: grid;

        grid-template-columns:
            repeat(3, minmax(0, 1fr));

        gap: 14px;

        margin-bottom: 20px;
    }

    .stat-card {
        padding: 16px 18px;

        background: #ffffff;

        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }

    .stat-label {
        margin-bottom: 7px;

        color: #64748b;

        font-size: 13px;
    }

    .stat-value {
        font-size: 26px;
        font-weight: 700;
    }

    .stat-text {
        font-size: 16px;
        font-weight: 700;
    }

    .section-title {
        margin: 0 0 18px;

        font-size: 18px;
    }

    .detail-row {
        display: grid;

        grid-template-columns:
            210px
            minmax(0, 1fr);

        gap: 20px;

        padding: 13px 0;

        border-bottom: 1px solid #f1f5f9;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        color: #64748b;

        font-size: 13px;
        font-weight: 700;
    }

    .detail-value {
        color: #334155;

        font-size: 14px;
        line-height: 1.6;
    }

    .information-box {
        margin-top: 20px;

        padding: 14px 16px;

        border: 1px solid #bfdbfe;
        border-radius: 9px;

        background: #eff6ff;
        color: #1e3a8a;

        font-family:
            "Times New Roman",
            Times,
            serif;

        line-height: 1.6;
    }

    @media (max-width: 700px) {

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .detail-row {
            grid-template-columns: 1fr;
        }

    }

</style>

@endpush