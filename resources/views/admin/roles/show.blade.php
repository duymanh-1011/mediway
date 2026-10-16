@extends('admin.layouts.app')

@section('title', 'Chi tiết vai trò - Mediway')

@section('breadcrumb')
    <div class="breadcrumb">
        Admin / Hệ thống /
        <a href="{{ route('admin.roles.index') }}">Vai trò</a>
        / Chi tiết
    </div>
@endsection

@section('content')
<div class="role-detail-page">

    <div class="role-detail-header">
        <div>
            <h1 class="page-title">
                Chi tiết vai trò: {{ $role->name }}
            </h1>

            <p class="page-description">
                Xem thông tin và các tài khoản thuộc vai trò này.
            </p>
        </div>

        <div class="role-detail-actions">
            <a href="{{ route('admin.roles.index') }}"
               class="btn btn-secondary">
                ← Danh sách
            </a>

            <a href="{{ route('admin.roles.edit', $role) }}"
               class="btn btn-primary">
                Chỉnh sửa
            </a>
        </div>
    </div>

    <div class="role-detail-grid">
        <div class="card">
            <h2 class="role-section-title">Thông tin vai trò</h2>

            <div class="role-detail-field">
                <span>ID</span>
                <strong>#{{ $role->id }}</strong>
            </div>

            <div class="role-detail-field">
                <span>Tên vai trò</span>
                <strong>{{ $role->name }}</strong>
            </div>

            <div class="role-detail-field">
                <span>Loại vai trò</span>
                <div>
                    @if ($role->isSystemRole())
                        <span class="badge">Vai trò hệ thống</span>
                    @else
                        <span class="badge badge-warning">Vai trò tùy chỉnh</span>
                    @endif
                </div>
            </div>

            <div class="role-detail-field">
                <span>Trạng thái</span>
                <div>
                    <span class="badge {{ $role->status === 'ACTIVE' ? 'badge-success' : 'badge-danger' }}">
                        {{ $role->status === 'ACTIVE' ? 'Hoạt động' : 'Ngừng hoạt động' }}
                    </span>
                </div>
            </div>

            <div class="role-detail-field">
                <span>Mô tả</span>
                <div>{{ $role->description ?: 'Chưa có mô tả' }}</div>
            </div>

            <div class="role-detail-field">
                <span>Ngày tạo</span>
                <div>{{ $role->created_at?->format('d/m/Y H:i') ?? '-' }}</div>
            </div>

            <div class="role-detail-field">
                <span>Cập nhật lần cuối</span>
                <div>{{ $role->updated_at?->format('d/m/Y H:i') ?? '-' }}</div>
            </div>
        </div>

        <div class="card role-summary-card">
            <h2 class="role-section-title">Thống kê tài khoản</h2>

            <div class="role-big-count">
{{ number_format($role->users_count) }}
            </div>

            <p>Tài khoản đang được gán vai trò này</p>

            @if ($role->isSystemRole())
                <div class="role-notice">
                    Vai trò hệ thống được bảo vệ, không thể đổi tên,
                    vô hiệu hóa hoặc xóa.
                </div>
            @elseif ($role->users_count > 0)
                <div class="role-notice">
                    Vai trò đang được sử dụng, không thể vô hiệu hóa
                    hoặc xóa cho đến khi không còn tài khoản liên kết.
                </div>
            @else
                <div class="role-notice">
                    Vai trò chưa có tài khoản liên kết.
                    Có thể chỉnh sửa hoặc xóa theo quy định.
                </div>
            @endif

            @if (!$role->isSystemRole() && $role->users_count === 0)
                <form
                    action="{{ route('admin.roles.destroy', $role) }}"
                    method="POST"
                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa vai trò này?');"
                    style="margin-top:18px;"
                >
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        Xóa vai trò
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="card role-users-card">
        <h2 class="role-section-title">
            Danh sách tài khoản thuộc vai trò
        </h2>

        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã tài khoản</th>
                        <th>Số điện thoại</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->business_code }}</strong>
                            </td>

                            <td>{{ $user->phone }}</td>

                            <td>
                                <span class="badge {{ $user->status === 'ACTIVE' ? 'badge-success' : 'badge-danger' }}">
                                    {{ $user->status }}
                                </span>
                            </td>

                            <td>
                                {{ $user->created_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="role-empty">
                                Chưa có tài khoản nào thuộc vai trò này.
                            </td>
</tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="role-users-pagination">
            <span>
                Hiển thị {{ $users->count() }} /
                {{ $users->total() }} tài khoản
            </span>

            <div class="role-pagination-actions">
                @if ($users->onFirstPage())
                    <span class="btn btn-secondary role-disabled">Trước</span>
                @else
                    <a href="{{ $users->previousPageUrl() }}"
                       class="btn btn-secondary">Trước</a>
                @endif

                <span>
                    Trang {{ $users->currentPage() }} /
                    {{ $users->lastPage() }}
                </span>

                @if ($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}"
                       class="btn btn-secondary">Sau</a>
                @else
                    <span class="btn btn-secondary role-disabled">Sau</span>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .role-detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 22px;
    }

    .role-detail-header .page-description { margin-bottom: 0; }

    .role-detail-actions { display: flex; gap: 10px; }

    .role-detail-grid {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(250px, 1fr);
        gap: 18px;
        margin-bottom: 20px;
    }

    .role-section-title {
        margin: 0 0 18px;
        font-size: 17px;
        font-weight: 700;
    }

    .role-detail-field {
        display: grid;
        grid-template-columns: 170px minmax(0, 1fr);
        gap: 12px;
        padding: 13px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
        overflow-wrap: anywhere;
    }

    .role-detail-field:last-child { border-bottom: none; }

    .role-detail-field > span { color: #64748b; }
    .role-detail-field > strong { color: #0f172a; }

    .role-big-count {
        font-size: 46px;
        font-weight: 800;
        color: #2563eb;
        line-height: 1.2;
    }

    .role-summary-card p {
        color: #64748b;
        font-size: 13px;
        margin: 8px 0 20px;
    }

    .role-notice {
        padding: 14px;
        border-radius: 9px;
        background: #eff6ff;
        color: #1e40af;
        font-size: 13px;
        line-height: 1.6;
    }

    .role-empty {
        text-align: center;
        padding: 30px !important;
        color: #64748b;
    }

    .role-users-pagination {
        margin-top: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        font-size: 13px;
        color: #64748b;
    }

    .role-pagination-actions {
        display: flex;
gap: 12px;
        align-items: center;
    }

    .role-disabled { opacity: .45; cursor: default; }

    @media (max-width: 900px) {
        .role-detail-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 600px) {
        .role-detail-field { grid-template-columns: 1fr; gap: 5px; }
    }
</style>
@endpush