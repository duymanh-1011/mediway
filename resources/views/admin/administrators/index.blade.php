@extends('admin.layouts.app')
@section('admin-page', 'administrators-index')

@section('title', 'Quản lý quản trị viên - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Hệ thống</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Quản trị viên</strong>
@endsection

@section('content')
<div class="adminstaff-page">
    <div class="adminstaff-heading">
        <div>
            <h1 class="page-title">Quản lý quản trị viên</h1>
            <p class="page-description">
                Quản lý hồ sơ và quyền truy cập của quản trị viên Mediway.
            </p>
        </div>
        <a href="{{ route('admin.administrators.create') }}" class="admin-btn as-btn as-primary admin-btn-primary">
            + Thêm quản trị viên
        </a>
    </div>

    @if(session('success'))
        <div class="as-alert as-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="as-alert as-error">{{ session('error') }}</div>
    @endif

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc quản trị viên',
        'description' => 'Lọc theo từ khóa và trạng thái tài khoản.',
    ])
        <form action="{{ route('admin.administrators.index') }}"
              method="GET" class="as-filters admin-filter-form">
            <div class="as-field as-search admin-filter-field">
                <label for="search">Tìm kiếm</label>
                <input type="text" id="search" name="search"
                       value="{{ $search }}"
                       placeholder="Mã, họ tên, SĐT hoặc email...">
@error('search')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="as-field admin-filter-field">
                <label for="status">Trạng thái tài khoản</label>
                <select id="status" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="ACTIVE" @selected($status === 'ACTIVE')>
                        Hoạt động
                    </option>
                    <option value="LOCKED" @selected($status === 'LOCKED')>
                        Đã khóa
                    </option>
                </select>
@error('status')<div class="field-error">{{ $message }}</div>@enderror
            </div>




@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.administrators.index'), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="as-card">

        <div class="as-table-wrap">
            <table class="as-table">
                <thead>
                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Mã quản trị</th>
                        <th>Họ và tên</th>
                        <th>Số điện thoại</th>
                        <th>Email</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($administrators as $administrator)
                        <tr>
                            @include('admin.layouts.partials.row-number', ['rows' => $administrators, 'index' => $loop->index])
                            <td>
                                <strong>
                                    {{ $administrator->user?->business_code ?? '-' }}
                                </strong>
                            </td>
                            <td>{{ $administrator->full_name }}</td>
                            <td>{{ $administrator->user?->phone ?? '-' }}</td>
<td>{{ $administrator->email ?: '-' }}</td>
                            <td>
                                @if($administrator->user?->status === 'ACTIVE'
                                    && $administrator->status === 'ACTIVE')
                                    <span class="as-badge as-badge-success">
                                        Hoạt động
                                    </span>
                                @else
                                    <span class="as-badge as-badge-danger">
                                        Đã khóa / Ngừng hoạt động
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="as-actions">
                                    <a class="admin-btn admin-btn-detail as-action as-view"
                                       href="{{ route('admin.administrators.show', $administrator) }}">Chi tiết</a>
                                    <a class="admin-btn admin-btn-secondary as-action as-edit"
                                       href="{{ route('admin.administrators.edit', $administrator) }}">Sửa</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="as-empty">
                                Không tìm thấy quản trị viên phù hợp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="as-footer">
            <span>
                Hiển thị {{ $administrators->count() }} /
                {{ $administrators->total() }} quản trị viên
            </span>

            <div class="as-pagination">
                @if($administrators->onFirstPage())
                    <span class="as-btn as-light as-disabled">Trước</span>
                @else
                    <a class="as-btn as-light"
                       href="{{ $administrators->previousPageUrl() }}">Trước</a>
                @endif

                <span>
                    Trang {{ $administrators->currentPage() }} /
                    {{ $administrators->lastPage() }}
                </span>

                @if($administrators->hasMorePages())
                    <a class="as-btn as-light"
                       href="{{ $administrators->nextPageUrl() }}">Sau</a>
                @else
                    <span class="as-btn as-light as-disabled">Sau</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
