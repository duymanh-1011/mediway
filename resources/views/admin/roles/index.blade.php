@extends('admin.layouts.app')
@section('admin-page', 'roles-index')

@section('title', 'Quản lý vai trò - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Hệ thống</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Vai trò</strong>
@endsection

@section('content')
<div class="roles-page">
    <div class="roles-heading">
        <div>
            <h1 class="page-title">Quản lý vai trò</h1>
            <p class="page-description">
                Quản lý các nhóm tài khoản và vai trò trong hệ thống Mediway.
            </p>
        </div>
        <a href="{{ route('admin.roles.create') }}" class="admin-btn admin-btn-primary">Thêm vai trò</a>
    </div>

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc vai trò',
        'description' => 'Lọc theo từ khóa và trạng thái.',
    ])
        <form data-filter-fields="2" action="{{ route('admin.roles.index') }}" method="GET" class="roles-filters admin-filter-form">
            <div class="filter-field admin-filter-field">
                <label for="search">Tìm kiếm</label>
                <input type="text" id="search" name="search"
                       value="{{ $search }}"
                       placeholder="Tên vai trò hoặc mô tả...">
@error('search')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="filter-field admin-filter-field">
                <label for="status">Trạng thái</label>
                <select id="status" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="ACTIVE" {{ $status === 'ACTIVE' ? 'selected' : '' }}>Đang hoạt động</option>
                    <option value="INACTIVE" {{ $status === 'INACTIVE' ? 'selected' : '' }}>
                        Ngừng hoạt động
                    </option>
                </select>
@error('status')<div class="field-error">{{ $message }}</div>@enderror
            </div>




@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.roles.index'), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách vai trò'])


        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Tên vai trò</th>
                        <th>Mô tả</th>
                        <th>Số tài khoản</th>
                        <th>Trạng thái</th>
                        <th>Loại</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            @include('admin.layouts.partials.row-number', ['rows' => $roles, 'index' => $loop->index])
                            <td><strong>{{ \App\Models\AdminDisplay::label($role->name) }}</strong></td>
                            <td>{{ $role->description ?: 'Chưa có mô tả' }}</td>
                            <td>{{ $role->users_count }}</td>
                            <td>
                                @include('admin.layouts.partials.status-badge', ['badgeCode' => $role->status])
</td>
                            <td>
                                @if ($role->isSystemRole())
                                    <span class="badge">Hệ thống</span>
                                @else
                                    <span class="badge badge-warning">Tùy chỉnh</span>
                                @endif
                            </td>
                            <td><div class="admin-actions admin-table-actions">
                                <div class="role-actions">
                                    <a href="{{ route('admin.roles.show', $role) }}"
                                       class="admin-btn admin-btn-detail">Chi tiết</a>

                                    <a href="{{ route('admin.roles.edit', $role) }}"
                                       class="admin-btn admin-btn-secondary">Sửa</a>

                                    @if (!$role->isSystemRole() && $role->users_count === 0)
                                        <form novalidate method="POST"
                                              action="{{ route('admin.roles.destroy', $role) }}"
                                              onsubmit="return confirm('Bạn chắc chắn muốn xóa vai trò này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-btn admin-btn-danger">
                                                Xóa
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="role-empty">
                                Không tìm thấy vai trò phù hợp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="roles-footer">
            <span>
                Hiển thị {{ $roles->count() }} / {{ $roles->total() }} vai trò
            </span>

            <div class="role-pagination">
                @if ($roles->onFirstPage())
                    <span class="admin-btn admin-btn-secondary" aria-disabled="true">Trước</span>
                @else
                    <a class="admin-btn admin-btn-secondary" href="{{ $roles->previousPageUrl() }}">
                        Trước
                    </a>
                @endif

                <span>Trang {{ $roles->currentPage() }} / {{ $roles->lastPage() }}</span>

                @if ($roles->hasMorePages())
                    <a class="admin-btn admin-btn-secondary" href="{{ $roles->nextPageUrl() }}">
                        Sau
                    </a>
                @else
                    <span class="admin-btn admin-btn-secondary" aria-disabled="true">Sau</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
