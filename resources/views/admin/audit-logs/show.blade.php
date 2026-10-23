@extends('admin.layouts.app')
@section('admin-page', 'audit-logs-show')

@section('title', 'Chi tiết nhật ký hoạt động')

@section('content')

<div class="audit-show-page">

        {{-- =========================
        BREADCRUMB
        ========================== --}}
        <nav class="admin-breadcrumb" aria-label="Đường dẫn">
            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Hệ thống</span>
            <span aria-hidden="true">/</span>

            <a href="{{ route('admin.audit-logs.index') }}">
                Nhật ký hoạt động
            </a>

            <span aria-hidden="true">/</span>

            <strong aria-current="page">Chi tiết</strong>
        </nav>


        {{-- =========================
        HEADER
        ========================== --}}
        <div class="admin-info-header audit-show-header">

            <div>

                <h1>Chi tiết nhật ký hoạt động</h1>

                <p>
                    Xem thông tin đầy đủ của thao tác đã được
                    ghi nhận trong hệ thống Mediway.
                </p>
            </div>


        </div>


        {{-- =========================
        BACK
        ========================== --}}
        <div class="audit-back-wrapper">
            <a href="{{ route('admin.audit-logs.index') }}" class="admin-btn admin-btn-secondary">← Quay lại</a>
        </div>


        {{-- =========================
        CONTENT
        ========================== --}}
        <div class="audit-detail-layout">

            {{-- =========================
            LEFT
            ========================== --}}
            <div class="audit-detail-main">

                {{-- Thông tin thao tác --}}
                <div class="audit-card">

                    <div class="audit-card-header">

                        <div>
                            <h2>Thông tin thao tác</h2>

                            <p>
                                Người thực hiện, hành động,
                                đối tượng và thời gian phát sinh.
                            </p>
                        </div>

                        @include('admin.layouts.partials.status-badge', ['badgeCode' => $log->action])

                    </div>


                    <div class="audit-info-grid">

                        {{-- Người thực hiện --}}
                        <div class="audit-info-item">

                            <div class="audit-info-label">
                                Người thực hiện
                            </div>

                            <div class="audit-info-value">
                                {{ $log->user_business_code
        ?? 'Hệ thống' }}
                            </div>

                        </div>


                        {{-- Vai trò --}}
                        <div class="audit-info-item">

                            <div class="audit-info-label">
                                Vai trò
                            </div>

                            <div class="audit-info-value">
                                {{ \App\Models\AdminDisplay::label($log->role_name_snapshot
        ?? '—') }}
                            </div>

                        </div>


                        {{-- Hành động --}}
                        <div class="audit-info-item">

                            <div class="audit-info-label">
                                Hành động
                            </div>

                            <div class="audit-info-value">
                                @include('admin.layouts.partials.status-badge', ['badgeCode' => $log->action])
                            </div>

                        </div>


                        {{-- Thời gian --}}
                        <div class="audit-info-item">

                            <div class="audit-info-label">
                                Thời gian thực hiện
                            </div>

                            <div class="audit-info-value">
                                {{ \Carbon\Carbon::parse(
        $log->performed_at
    )->format('d/m/Y H:i:s') }}
                            </div>

                        </div>


                        {{-- Entity --}}
                        <div class="audit-info-item">

                            <div class="audit-info-label">
                                Loại đối tượng
                            </div>

                            <div class="audit-info-value">
                                {{ \App\Models\AdminDisplay::label($log->entity_type) }}
                            </div>

                        </div>


                        {{-- Business Code --}}
                        <div class="audit-info-item">

                            <div class="audit-info-label">
                                Mã nghiệp vụ
                            </div>

                            <div class="audit-info-value">

                                @if ($log->entity_business_code)

                                    <span class="audit-code">
                                        {{ $log->entity_business_code }}
                                    </span>

                                @else

                                    <span class="audit-muted">
                                        —
                                    </span>

                                @endif

                            </div>

                        </div>

                    </div>


                    {{-- Description --}}
                    <div class="audit-description-section">

                        <div class="audit-info-label">
                            Mô tả thao tác
                        </div>

                        <div class="audit-description-text">
                            {{ \App\Models\AdminDisplay::text($log->description ?? 'Không có mô tả.') }}
                        </div>

                    </div>

                </div>


                {{-- =========================
                BEFORE JSON
                ========================== --}}
                <div class="audit-json-card">

                    <div class="audit-json-header">

                        <div>
                            <h2>Dữ liệu trước thay đổi</h2>

                            <p>
                                Trạng thái dữ liệu trước khi
                                thao tác được thực hiện.
                            </p>
                        </div>

                        <span class="audit-json-badge">
                            Dữ liệu trước thay đổi
                        </span>

                    </div>

                    @include('admin.layouts.partials.value-list', ['valueRows' => $oldRows])

                </div>


                {{-- =========================
                AFTER JSON
                ========================== --}}
                <div class="audit-json-card">

                    <div class="audit-json-header">

                        <div>
                            <h2>Dữ liệu sau thay đổi</h2>

                            <p>
                                Trạng thái dữ liệu sau khi
                                thao tác được thực hiện.
                            </p>
                        </div>

                        <span class="audit-json-badge">
                            Dữ liệu sau thay đổi
                        </span>

                    </div>

                    @include('admin.layouts.partials.value-list', ['valueRows' => $newRows])

                </div>

            </div>


            {{-- =========================
            RIGHT - TECHNICAL INFO
            ========================== --}}
            <div class="audit-detail-side">

                <div class="audit-card">

                    <div class="audit-card-header">

                        <div>
                            <h2>Thông tin kỹ thuật</h2>

                            <p>
                                Dữ liệu phục vụ truy vết
                                và kiểm tra hệ thống.
                            </p>
                        </div>

                    </div>


                    <div class="audit-tech-list">

                        {{-- IP --}}
                        <div class="audit-tech-item">

                            <div class="audit-tech-icon">
                                IP
                            </div>

                            <div class="audit-tech-content">

                                <div class="audit-info-label">
                                    Địa chỉ IP
                                </div>

                                <div class="audit-info-value">
                                    {{ $log->ip_address
        ?? '—' }}
                                </div>

                            </div>

                        </div>


                        {{-- User Agent --}}
                        <div class="audit-tech-item">

                            <div class="audit-tech-icon">
                                UA
                            </div>

                            <div class="audit-tech-content">

                                <div class="audit-info-label">
                                    Thông tin trình duyệt
                                </div>

                                <div class="audit-user-agent">
                                    {{ $log->user_agent
        ?? '—' }}
                                </div>

                            </div>

                        </div>


                        {{-- Performed At --}}
                        <div class="audit-tech-item">

                            <div class="audit-tech-icon">
                                T
                            </div>

                            <div class="audit-tech-content">

                                <div class="audit-info-label">
                                    Thời gian thực hiện
                                </div>

                                <div class="audit-info-value">
                                    {{ \Carbon\Carbon::parse(
        $log->performed_at
    )->format('d/m/Y H:i:s') }}
                                </div>

                            </div>

                        </div>


                        {{-- Created At --}}
                        <div class="audit-tech-item">

                            <div class="audit-tech-icon">
                                DB
                            </div>

                            <div class="audit-tech-content">

                                <div class="audit-info-label">
                                    Thời điểm ghi nhật ký
                                </div>

                                <div class="audit-info-value">

                                    @if ($log->created_at)

                                                                    {{ \Carbon\Carbon::parse(
                                            $log->created_at
                                        )->format('d/m/Y H:i:s') }}

                                    @else
                                        —
                                    @endif

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- Read only notice --}}
                <div class="audit-policy-box">

                    <div class="audit-policy-icon">
                        !
                    </div>

                    <div>
                        <strong>
                            Nhật ký không thể chỉnh sửa
                        </strong>

                        <p>
                            Nhật ký hoạt động chỉ được sử dụng
                            để tra cứu và truy vết hoạt động.
                        </p>
                    </div>

                </div>

            </div>

        </div>

    </div>




@endsection
