@extends('admin.layouts.app')
@section('admin-page', 'audit-logs-index')

@section('title', 'Nhật ký hoạt động')

@section('content')

    <div class="audit-page">

        {{-- Breadcrumb --}}
        <nav class="admin-breadcrumb" aria-label="Đường dẫn">
            <a href="{{ route('admin.dashboard') }}">
                Bảng điều khiển
            </a>

            <span aria-hidden="true">/</span>
            <span>Hệ thống</span>
            <span aria-hidden="true">/</span>

            <strong aria-current="page">Nhật ký hoạt động</strong>
        </nav>

        {{-- Header --}}
        <div class="admin-info-header audit-header">
            <div>

                <h1>Nhật ký hoạt động</h1>

                <p>
                    Theo dõi và tra cứu các thao tác đã phát sinh
                    trong hệ thống Mediway.
                </p>
            </div>

        </div>

        {{-- Filter --}}
        @component('admin.layouts.partials.filter-panel', [
            'title' => 'Bộ lọc nhật ký',
            'description' => 'Lọc theo người thực hiện, hành động, đối tượng và khoảng thời gian.',
        ])
            <form data-filter-fields="5" method="GET" action="{{ route('admin.audit-logs.index') }}" class="admin-filter-form">
                    <div class="audit-filter-grid admin-filter-layout">

                        <div class="audit-form-group admin-filter-field">
                            <label>Người thực hiện</label>

                            <select name="user" class="audit-control">
                                <option value="">
                                    Tất cả người thực hiện
                                </option>

                                @foreach ($users as $user)
                                    <option value="{{ $user->business_code }}" @selected(
                                        request('user') ===
                                        $user->business_code
                                    )>
                                        {{ $user->business_code }}
                                    </option>
                                @endforeach
                            </select>
@error('user')<div class="field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="audit-form-group admin-filter-field">
                            <label>Hành động</label>

                            <select name="action" class="audit-control">
                                <option value="">
                                    Tất cả hành động
                                </option>

                                @foreach ($actions as $action)
                                    <option value="{{ $action }}" @selected(
                                        request('action') === $action
                                    )>
                                        {{ \App\Models\AdminDisplay::label($action) }}
                                    </option>
                                @endforeach
                            </select>
@error('action')<div class="field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="audit-form-group admin-filter-field">
                            <label>Đối tượng</label>

                            <select name="entity" class="audit-control">
                                <option value="">
                                    Tất cả đối tượng
                                </option>

                                @foreach ($entities as $entity)
                                    <option value="{{ $entity }}" @selected(
                                        request('entity') === $entity
                                    )>
                                        {{ \App\Models\AdminDisplay::label($entity) }}
                                    </option>
                                @endforeach
                            </select>
@error('entity')<div class="field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="audit-form-group admin-filter-field">
                            <label>Từ ngày</label>

                            <input type="date" name="date_from" class="audit-control" value="{{ request('date_from') }}">
@error('date_from')<div class="field-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="audit-form-group admin-filter-field">
                            <label>Đến ngày</label>

                            <input type="date" name="date_to" class="audit-control" value="{{ request('date_to') }}">
@error('date_to')<div class="field-error">{{ $message }}</div>@enderror
                        </div>

                    </div>

                    <div class="audit-filter-actions admin-filter-layout">



                    </div>

@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.audit-logs.index'), 'searchOnly' => false])
            </form>
        @endcomponent

        {{-- Table --}}
        <div class="audit-card">

            <div class="audit-card-header audit-list-header">
                <div>
                    <h2 class="admin-list-title">Danh sách nhật ký hoạt động</h2>

                    <p>
                        Các thao tác mới nhất được hiển thị trước.
                    </p>
                </div>

                <div class="audit-count">
                    Tổng cộng
                    <strong>{{ $logs->total() }}</strong>
                    bản ghi
                </div>
            </div>

            <div class="audit-table-wrapper">
                <table class="audit-table">

                    <thead>
                        <tr>
                            <th scope="col" class="admin-row-number">STT</th>
                            <th>Thời gian</th>
                            <th>Người thực hiện</th>
                            <th>Vai trò</th>
                            <th>Hành động</th>
                            <th>Đối tượng</th>
                            <th>Mã nghiệp vụ</th>
                            <th>Mô tả</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($logs as $log)

                                            @php


                                                $avatar = $log->user_business_code
                                                    ? mb_substr(
                                                        $log->user_business_code,
                                                        0,
                                                        1
                                                    )
                                                    : 'H';
                                            @endphp

                                            <tr>

                                                @include('admin.layouts.partials.row-number', ['rows' => $logs, 'index' => $loop->index])
                                                <td class="audit-time">
                                                    {{ \Carbon\Carbon::parse(
                                $log->performed_at
                            )->format('d/m/Y H:i') }}
                                                </td>

                                                <td>
                                                    <div class="audit-user">
                                                        <div class="audit-avatar">
                                                            {{ strtoupper($avatar) }}
                                                        </div>

                                                        <div>
                                                            <strong>
                                                                @include('admin.layouts.partials.record-code', ['code' => $log->user_business_code
                                ?? 'Hệ thống'])
                                                            </strong>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td class="audit-muted">
                                                    {{ \App\Models\AdminDisplay::label($log->role_name_snapshot ?? '—') }}
                                                </td>

                                                <td>
                                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => $log->action])
                                                </td>

                                                <td>
                                                    <strong class="audit-entity">
                                                        {{ \App\Models\AdminDisplay::label($log->entity_type) }}
                                                    </strong>
                                                </td>

                                                <td>
                                                    @if ($log->entity_business_code)
                                                        <span class="audit-code">
                                                            @include('admin.layouts.partials.record-code', ['code' => $log->entity_business_code])
                                                        </span>
                                                    @else
                                                        <span class="audit-muted">
                                                            —
                                                        </span>
                                                    @endif
                                                </td>

                                                <td>
                                                    <div class="audit-description">
                                                        {{ \Illuminate\Support\Str::limit(
                                \App\Models\AdminDisplay::text($log->description ?? '—'),
                                65
                            ) }}
                                                    </div>
                                                </td>

                                                <td><div class="admin-actions admin-table-actions">
                                                    <a href="{{ route(
                                'admin.audit-logs.show',
                                $log->id
                            ) }}" class="admin-btn admin-btn-detail">Chi tiết</a>
                                                </div></td>

                                            </tr>

                        @empty

                            <tr>
                                <td colspan="9" class="audit-empty">
                                    <div class="audit-empty-icon">
                                        !
                                    </div>

                                    <strong>
                                        Không tìm thấy nhật ký
                                    </strong>

                                    <p>
                                        Không có dữ liệu phù hợp
                                        với bộ lọc hiện tại.
                                    </p>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($logs->hasPages())
                <div class="audit-pagination">

                    <div class="audit-pagination-info">
                        Trang
                        <strong>
                            {{ $logs->currentPage() }}
                        </strong>
                        /
                        {{ $logs->lastPage() }}
                    </div>

                    <div class="audit-pagination-buttons">

                        @if ($logs->onFirstPage())
                            <span class="admin-btn admin-btn-secondary" aria-disabled="true">Trước</span>
                        @else
                            <a href="{{ $logs->previousPageUrl() }}" class="admin-btn admin-btn-secondary">Trước</a>
                        @endif

                        @if ($logs->hasMorePages())
                            <a href="{{ $logs->nextPageUrl() }}" class="admin-btn admin-btn-secondary">Sau</a>
                        @else
                            <span class="admin-btn admin-btn-secondary" aria-disabled="true">Sau</span>
                        @endif

                    </div>

                </div>
            @endif

        </div>

    </div>




@endsection
