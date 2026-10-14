@extends('admin.layouts.app')

@section(
    'title',
    'Chi tiết bệnh - Mediway Admin'
)

@section('breadcrumb')

    <div class="breadcrumb">

        Admin /
        Danh mục /
        Danh mục bệnh /
        {{ $disease->disease_code }}

    </div>

@endsection

@section('content')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                {{ $disease->name }}
            </h1>

            <p class="page-description">
                Mã bệnh:
                {{ $disease->disease_code }}
            </p>

        </div>

        <div class="header-actions">

            <a
                href="{{ route(
                    'admin.diseases.index'
                ) }}"
                class="btn btn-secondary"
            >
                ← Danh sách
            </a>

            <a
                href="{{ route(
                    'admin.diseases.edit',
                    $disease
                ) }}"
                class="btn btn-primary"
            >
                Sửa bệnh
            </a>

        </div>

    </div>

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-label">
                Số lần được dùng trong chẩn đoán
            </div>

            <div class="stat-value">
                {{ $diagnosisCount }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Nhóm bệnh
            </div>

            <div class="stat-text">
                {{ $disease
                    ->diseaseGroup
                    ?->name
                    ?? '—' }}
            </div>

        </div>

        <div class="stat-card">

            <div class="stat-label">
                Trạng thái
            </div>

            <div class="stat-text">

                @if (
                    $disease->status
                    === 'ACTIVE'
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
            Thông tin bệnh
        </h3>

        <div class="detail-grid">

            <div class="detail-row">

                <div class="detail-label">
                    Mã bệnh
                </div>

                <div class="detail-value">
                    {{ $disease->disease_code }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Tên bệnh
                </div>

                <div class="detail-value">
                    {{ $disease->name }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Tên thay thế
                </div>

                <div class="detail-value">
                    {{ $disease
                        ->alternative_name
                        ?? '—' }}
                </div>

            </div>

            <div class="detail-row">

                <div class="detail-label">
                    Nhóm bệnh
                </div>

                <div class="detail-value">
                    {{ $disease
                        ->diseaseGroup
                        ?->name
                        ?? '—' }}
                </div>

            </div>

        </div>

        <div class="detail-section">

            <div class="detail-label">
                Mô tả
            </div>

            <div class="detail-value long-text">
                {{ $disease->description ?? '—' }}
            </div>

        </div>

        <div class="detail-section">

            <div class="detail-label">
                Triệu chứng thường gặp
            </div>

            <div class="detail-value long-text">
                {{ $disease->common_symptoms ?? '—' }}
            </div>

        </div>

        <div class="detail-section">

            <div class="detail-label">
                Ghi chú chuyên môn
            </div>

            <div class="detail-value long-text">
                {{ $disease->professional_notes ?? '—' }}
            </div>

        </div>

        <div class="detail-section">

            <div class="detail-label">
                Ngày tạo
            </div>

            <div class="detail-value">
                {{ $disease
                    ->created_at
                    ?->format('d/m/Y H:i') }}
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
        color: #64748b;

        font-size: 13px;

        margin-bottom: 7px;
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

    .detail-grid {
        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 0 30px;
    }

    .detail-row,
    .detail-section {
        padding: 13px 0;

        border-bottom: 1px solid #f1f5f9;
    }

    .detail-label {
        margin-bottom: 5px;

        color: #64748b;

        font-size: 13px;
        font-weight: 700;
    }

    .detail-value {
        color: #334155;

        font-size: 14px;
        line-height: 1.6;
    }

    .long-text {
        white-space: pre-line;
    }

    @media (max-width: 800px) {

        .stats-grid,
        .detail-grid {
            grid-template-columns: 1fr;
        }

    }

</style>

@endpush