@extends('admin.layouts.app')

@section('title', 'Quản lý vai trò - Mediway')

@section('breadcrumb')
    <div class="breadcrumb">
        Admin / Hệ thống / Vai trò
    </div>
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
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
            + Thêm vai trò
        </a>
    </div>

    <div class="card">
        <form action="{{ route('admin.roles.index') }}" method="GET" class="roles-filters">
            <div class="filter-field">
                <label for="search">Tìm kiếm</label>
                <input type="text" id="search" name="search"
                       value="{{ $search }}"
                       placeholder="Tên vai trò hoặc mô tả...">
            </div>

            <div class="filter-field">
                <label for="status">Trạng thái</label>
                <select id="status" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="ACTIVE" {{ $status === 'ACTIVE' ? 'selected' : '' }}>
                        Hoạt động
                    </option>
                    <option value="INACTIVE" {{ $status === 'INACTIVE' ? 'selected' : '' }}>
                        Ngừng hoạt động
                    </option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                Đặt lại
            </a>
        </form>

        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
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
                            <td>#{{ $role->id }}</td>
                            <td><strong>{{ $role->name }}</strong></td>
                            <td>{{ $role->description ?: 'Chưa có mô tả' }}</td>
                            <td>{{ $role->users_count }}</td>
                            <td>
                                <span class="badge {{ $role->status === 'ACTIVE' ? 'badge-success' : 'badge-danger' }}">
                                    {{ $role->status === 'ACTIVE' ? 'Hoạt động' : 'Ngừng hoạt động' }}
                                </span>
</td>
                            <td>
                                @if ($role->isSystemRole())
                                    <span class="badge">Hệ thống</span>
                                @else
                                    <span class="badge badge-warning">Tùy chỉnh</span>
                                @endif
                            </td>
                            <td>
                                <div class="role-actions">
                                    <a href="{{ route('admin.roles.show', $role) }}"
                                       class="role-action role-view">Chi tiết</a>

                                    <a href="{{ route('admin.roles.edit', $role) }}"
                                       class="role-action role-edit">Sửa</a>

                                    @if (!$role->isSystemRole() && $role->users_count === 0)
                                        <form method="POST"
                                              action="{{ route('admin.roles.destroy', $role) }}"
                                              onsubmit="return confirm('Bạn chắc chắn muốn xóa vai trò này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="role-action role-delete">
                                                Xóa
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
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
                    <span class="btn btn-secondary">Trước</span>
                @else
                    <a class="btn btn-secondary" href="{{ $roles->previousPageUrl() }}">
                        Trước
                    </a>
                @endif

                <span>Trang {{ $roles->currentPage() }} / {{ $roles->lastPage() }}</span>

                @if ($roles->hasMorePages())
                    <a class="btn btn-secondary" href="{{ $roles->nextPageUrl() }}">
                        Sau
                    </a>
                @else
                    <span class="btn btn-secondary">Sau</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.roles-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }

    .roles-heading .page-description { margin-bottom: 0; }

    .roles-filters {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: 12px;
        margin-bottom: 20px;
    }

    .filter-field { display: flex; flex-direction: column; gap: 7px; }
    .filter-field:first-child { flex: 1; min-width: 200px; }

    .filter-field label { font-size: 12px; font-weight: 700; color: #475569; }

    .filter-field input,
    .filter-field select {
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
        color: #0f172a;
        outline: none;
        min-height: 39px;
    }

    .filter-field input:focus,
    .filter-field select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px #dbeafe;
    }

    .role-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 7px; }
    .role-actions form { margin: 0; }

    .role-action {
        display: inline-flex;
        align-items: center;
        border: 1px solid transparent;
        border-radius: 6px;
        padding: 6px 9px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        font-family: inherit;
    }

    .role-view { background: #eff6ff; color: #1d4ed8; }
    .role-edit { background: #f1f5f9; color: #334155; }
    .role-delete { background: #fef2f2; color: #b91c1c; }

    .role-empty { text-align: center; color: #64748b; padding: 36px !important; }

    .roles-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 20px;
        color: #64748b;
        font-size: 13px;
    }

    .role-pagination { display: flex; align-items: center; gap: 12px; }
    .role-pagination span.btn { opacity: .45; cursor: default; }

    @media (max-width: 700px) {
        .roles-heading { align-items: flex-start; flex-direction: column; }
        .roles-filters { flex-direction: column; align-items: stretch; }
        .filter-field { width: 100%; }
    }
</style>
@endpush