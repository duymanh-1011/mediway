@extends('admin.layouts.app')

@section(
    'title',
    'Chi tiết nhóm dịch vụ - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">

        Admin /
        Danh mục /
        Nhóm dịch vụ /
        {{ $serviceGroup->service_group_code }}

    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                {{ $serviceGroup->name }}
            </h1>

            <p class="page-description">
                Mã nhóm:
                {{ $serviceGroup->service_group_code }}
            </p>

        </div>

        <div class="header-actions">

            <a
                href="{{ route(
                    'admin.service-groups.index'
                ) }}"
                class="btn btn-secondary"
            >
                ← Danh sách
            </a>

            <a
                href="{{ route(
                    'admin.service-groups.edit',
                    $serviceGroup
                ) }}"
                class="btn btn-primary"
            >
                Sửa nhóm dịch vụ
            </a>

        </div>

    </div>

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-label">
                Tổng dịch vụ
            </div>

            <div class="stat-value">
                {{ $totalServices }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Đang hoạt động
            </div>

            <div class="stat-value success">
                {{ $activeServices }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Ngừng hoạt động
            </div>

            <div class="stat-value danger">
                {{ $inactiveServices }}
            </div>

        </div>

    </div>

    <div class="detail-grid">

        <div class="card">

            <h3 class="section-title">
                Thông tin nhóm dịch vụ
            </h3>

            <div class="detail-row">

                <div class="detail-label">
                    Mã nhóm
                </div>

                <div class="detail-value">
                    {{ $serviceGroup
                        ->service_group_code }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Tên nhóm
                </div>

                <div class="detail-value">
                    {{ $serviceGroup->name }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Thứ tự hiển thị
                </div>

                <div class="detail-value">
                    {{ $serviceGroup
                        ->display_order }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Trạng thái
                </div>

                <div class="detail-value">

                    @if (
                        $serviceGroup->status
                        === 'ACTIVE'
                    )

                        <span
                            class="badge badge-success"
                        >
                            ACTIVE
                        </span>

                    @else

                        <span
                            class="badge badge-danger"
                        >
                            INACTIVE
                        </span>

                    @endif

                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Mô tả
                </div>

                <div class="detail-value">
                    {{ $serviceGroup
                        ->description
                        ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Ghi chú
                </div>

                <div class="detail-value">
                    {{ $serviceGroup
                        ->notes
                        ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Ngày tạo
                </div>

                <div class="detail-value">
                    {{ $serviceGroup
                        ->created_at
                        ?->format(
                            'd/m/Y H:i'
                        ) }}
                </div>

            </div>

        </div>

        <div class="card">

            <h3 class="section-title">
                Dịch vụ thuộc nhóm
            </h3>

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>

                        <tr>
                            <th>Mã dịch vụ</th>
                            <th>Tên dịch vụ</th>
                            <th>Giá</th>
                            <th>Đơn vị</th>
                            <th>Thời lượng</th>
                            <th>Trạng thái</th>
                        </tr>

                    </thead>

                    <tbody>

                    @forelse (
                        $medicalServices
                        as $service
                    )

                        <tr>

                            <td>
                                <strong>
                                    {{ $service
                                        ->service_code }}
                                </strong>
                            </td>

                            <td>
                                {{ $service->name }}
                            </td>

                            <td>
                                {{
                                    number_format(
                                        $service->price,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                                đ
                            </td>

                            <td>
                                {{ $service->unit }}
                            </td>

                            <td>

                                @if (
                                    $service
                                        ->estimated_duration_minutes
                                )

                                    {{ $service
                                        ->estimated_duration_minutes }}
                                    phút

                                @else
                                    —
                                @endif

                            </td>

                            <td>

                                @if (
                                    $service->status
                                    === 'ACTIVE'
                                )

                                    <span
                                        class="badge badge-success"
                                    >
                                        ACTIVE
                                    </span>

                                @else

                                    <span
                                        class="badge badge-danger"
                                    >
                                        INACTIVE
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="empty-state"
                            >
                                Nhóm này chưa có dịch vụ y tế.
                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

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
        margin-bottom: 6px;

        color: #64748b;

        font-size: 12px;
    }

    .stat-value {
        font-size: 26px;
        font-weight: 700;
    }

    .stat-value.success {
        color: #059669;
    }

    .stat-value.danger {
        color: #dc2626;
    }

    .detail-grid {
        display: grid;

        grid-template-columns:
            minmax(300px, 0.75fr)
            minmax(0, 1.5fr);

        gap: 20px;
    }

    .section-title {
        margin:
            0
            0
            16px;

        font-size: 17px;
    }

    .detail-row {
        display: grid;

        grid-template-columns:
            140px
            minmax(0, 1fr);

        gap: 15px;

        padding: 12px 0;

        border-bottom: 1px solid #f1f5f9;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        color: #64748b;

        font-size: 12px;
        font-weight: 700;
    }

    .detail-value {
        color: #334155;

        font-size: 13px;
        line-height: 1.55;
    }

    .empty-state {
        padding: 30px !important;

        text-align: center !important;

        color: #94a3b8 !important;
    }

    @media (max-width: 1000px) {

        .detail-grid {
            grid-template-columns: 1fr;
        }

    }

    @media (max-width: 700px) {

        .stats-grid {
            grid-template-columns: 1fr;
        }

    }

</style>

@endpush