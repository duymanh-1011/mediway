@extends('admin.layouts.app')

@section(
    'title',
    'Chi tiết nhóm bệnh - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">

        Admin /
        Danh mục /
        Nhóm bệnh /
        {{ $diseaseGroup->disease_group_code }}

    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                {{ $diseaseGroup->name }}
            </h1>

            <p class="page-description">
                Mã nhóm bệnh:
                {{ $diseaseGroup->disease_group_code }}
            </p>

        </div>

        <div class="header-actions">

            <a
                href="{{ route(
                    'admin.disease-groups.index'
                ) }}"
                class="btn btn-secondary"
            >
                ← Danh sách
            </a>

            <a
                href="{{ route(
                    'admin.disease-groups.edit',
                    $diseaseGroup
                ) }}"
                class="btn btn-primary"
            >
                Sửa nhóm bệnh
            </a>

        </div>

    </div>

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-label">
                Tổng bệnh
            </div>

            <div class="stat-value">
                {{ $totalDiseases }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Đang hoạt động
            </div>

            <div class="stat-value success">
                {{ $activeDiseases }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Ngừng hoạt động
            </div>

            <div class="stat-value danger">
                {{ $inactiveDiseases }}
            </div>

        </div>

    </div>

    <div class="detail-grid">

        <div class="card">

            <h3 class="section-title">
                Thông tin nhóm bệnh
            </h3>

            <div class="detail-row">

                <div class="detail-label">
                    Mã nhóm
                </div>

                <div class="detail-value">
                    {{ $diseaseGroup
                        ->disease_group_code }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Tên nhóm
                </div>

                <div class="detail-value">
                    {{ $diseaseGroup->name }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Trạng thái
                </div>

                <div class="detail-value">

                    @if (
                        $diseaseGroup->status
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
                    {{ $diseaseGroup
                        ->description
                        ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Ghi chú
                </div>

                <div class="detail-value">
                    {{ $diseaseGroup
                        ->notes
                        ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Ngày tạo
                </div>

                <div class="detail-value">
                    {{ $diseaseGroup
                        ->created_at
                        ?->format(
                            'd/m/Y H:i'
                        ) }}
                </div>

            </div>

        </div>

        <div class="card">

            <h3 class="section-title">
                Danh mục bệnh thuộc nhóm
            </h3>

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>

                        <tr>
                            <th>Mã bệnh</th>
                            <th>Tên bệnh</th>
                            <th>Tên khác</th>
                            <th>Triệu chứng thường gặp</th>
                            <th>Trạng thái</th>
                        </tr>

                    </thead>

                    <tbody>

                    @forelse (
                        $diseases
                        as $disease
                    )

                        <tr>

                            <td>
                                <strong>
                                    {{ $disease
                                        ->disease_code }}
                                </strong>
                            </td>

                            <td>
                                {{ $disease->name }}
                            </td>

                            <td>
                                {{ $disease
                                    ->alternative_name
                                    ?? '—' }}
                            </td>

                            <td class="symptom-cell">
                                {{ $disease
                                    ->common_symptoms
                                    ?? '—' }}
                            </td>

                            <td>

                                @if (
                                    $disease->status
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
                                colspan="5"
                                class="empty-state"
                            >
                                Nhóm này chưa có bệnh.
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

    .symptom-cell {
        max-width: 300px;

        white-space: normal !important;

        line-height: 1.45;
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