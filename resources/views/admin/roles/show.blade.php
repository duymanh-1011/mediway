@extends('admin.layouts.app')
@section('admin-page', 'roles-show')

@section('title', 'Chi tiết vai trò - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Hệ thống</span>
    <span aria-hidden="true">/</span>
    <a href="{{ route('admin.roles.index') }}">Vai trò</a>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Chi tiết</strong>
@endsection

@section('content')
<div class="role-detail-page">

    <div class="role-detail-header">
        <div>
            <h1 class="page-title">
                Chi tiết vai trò: {{ \App\Models\AdminDisplay::label($role->name) }}
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
                <strong>{{ \App\Models\AdminDisplay::label($role->name) }}</strong>
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
                <form novalidate class="admin-u-86de7ac6"
                    action="{{ route('admin.roles.destroy', $role) }}"
                    method="POST"
                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa vai trò này?');"

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
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Mã tài khoản</th>
                        <th>Số điện thoại</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            @include('admin.layouts.partials.row-number', ['rows' => $users, 'index' => $loop->index])
                            <td>
                                <strong>{{ $user->business_code }}</strong>
                            </td>

                            <td>{{ $user->phone }}</td>

                            <td>
                                <span class="badge {{ $user->status === 'ACTIVE' ? 'badge-success' : 'badge-danger' }}">
                                    {{ \App\Models\AdminDisplay::label($user->status) }}
                                </span>
                            </td>

                            <td>
                                {{ $user->created_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="role-empty">
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
