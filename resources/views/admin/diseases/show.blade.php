@extends('admin.layouts.app')
@section('admin-page', 'diseases-show')

@section(
    'title',
    'Chi tiết bệnh - Mediway'
)

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Danh mục</span>
    <span aria-hidden="true">/</span>
    <span>Danh mục bệnh</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">@include('admin.layouts.partials.record-code', ['code' => $disease->disease_code])</strong>
@endsection

@section('content')
    @include('admin.layouts.partials.disease-catalog-tabs')

    <div class="page-header">

        <div>

            <h1 class="page-title">
                {{ $disease->name }}
            </h1>

            <p class="page-description">
                Mã bệnh:
                @include('admin.layouts.partials.record-code', ['code' => $disease->disease_code])
            </p>

        </div>

        <div class="header-actions">

            <a class="admin-btn admin-btn-detail" href="{{ route('admin.disease-groups.show', $disease->disease_group_id) }}">Xem nhóm bệnh</a>

            <a
                href="{{ route(
                    'admin.diseases.index'
                ) }}"
                class="admin-btn admin-btn-secondary"
            >← Quay lại</a>

            <a
                href="{{ route(
                    'admin.diseases.edit',
                    $disease
                ) }}"
                class="admin-btn admin-btn-secondary"
            >Sửa</a>

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

                    @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ACTIVE'])

                @else

                    @include('admin.layouts.partials.status-badge', ['badgeCode' => 'INACTIVE'])

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
                    @include('admin.layouts.partials.record-code', ['code' => $disease->disease_code])
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
