@extends('admin.layouts.app')
@section('admin-page', 'users-index')

@section('title', 'Quản lý tài khoản - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Hệ thống</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Tài khoản</strong>
@endsection

@section('content')
<div class="users-page">
    <div class="users-heading">
        <div>
            <h1 class="page-title">Quản lý tài khoản</h1>
            <p class="page-description">
                Theo dõi tài khoản đăng nhập, vai trò và trạng thái sử dụng hệ thống.
            </p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="admin-btn admin-btn-primary">Thêm tài khoản</a>
    </div>

    @if(session('success'))
        <div class="users-alert users-alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="users-alert users-alert-error">{{ session('error') }}</div>
    @endif

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc tài khoản',
        'description' => 'Lọc theo từ khóa, vai trò và trạng thái.',
    ])
        <form data-filter-fields="3" method="GET" action="{{ route('admin.users.index') }}" class="users-filter admin-filter-form">
            <div class="users-field users-search admin-filter-field">
                <label for="search">Tìm kiếm</label>
                <input
                    id="search"
                    name="search"
                    type="text"
                    value="{{ $search }}"
                    placeholder="Mã tài khoản hoặc số điện thoại..."
                >
@error('search')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="users-field admin-filter-field">
                <label for="role_id">Vai trò</label>
                <select id="role_id" name="role_id">
                    <option value="">Tất cả vai trò</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}"
                            {{ (string) $roleId === (string) $role->id ? 'selected' : '' }}>
                            {{ \App\Models\AdminDisplay::label($role->name) }}
                        </option>
                    @endforeach
                </select>
@error('role_id')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="users-field admin-filter-field">
                <label for="status">Trạng thái</label>
                <select id="status" name="status">
                    <option value="">Tất cả</option>
                    <option value="ACTIVE" {{ $status === 'ACTIVE' ? 'selected' : '' }}>Đang hoạt động</option>
                    <option value="LOCKED" {{ $status === 'LOCKED' ? 'selected' : '' }}>
                        Đã khóa
                    </option>
                </select>
@error('status')<div class="field-error">{{ $message }}</div>@enderror
            </div>




@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.users.index'), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="users-panel">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách tài khoản'])


        <div class="users-table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Mã tài khoản</th>
                        <th>Số điện thoại</th>
<th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th class="users-action-heading">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            @include('admin.layouts.partials.row-number', ['rows' => $users, 'index' => $loop->index])
                            <td><strong>@include('admin.layouts.partials.record-code', ['code' => $user->business_code])</strong></td>
                            <td>{{ $user->phone }}</td>
                            <td>{{ \App\Models\AdminDisplay::label($user->role?->name ?? 'Chưa xác định') }}</td>
                            <td>
                                @if($user->status === 'ACTIVE')
                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => 'ACTIVE'])
                                @elseif($user->status === 'LOCKED')
                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => 'LOCKED'])
                                @else
                                    @include('admin.layouts.partials.status-badge', ['badgeCode' => $user->status])
                                @endif
                            </td>
                            <td>{{ $user->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td><div class="admin-actions admin-table-actions">
                                <div class="users-row-actions">
                                    <a href="{{ route('admin.users.show', $user) }}"
                                       class="admin-btn admin-btn-detail">Chi tiết</a>
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="admin-btn admin-btn-secondary">Sửa</a>
                                </div>
                            </div></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="users-empty">
                                Không tìm thấy tài khoản phù hợp với điều kiện tìm kiếm.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="users-footer">
            <span>
                Hiển thị {{ $users->count() }} / {{ $users->total() }} tài khoản
            </span>

            <div class="users-pagination">
                @if($users->onFirstPage())
                    <span class="users-button users-button-light users-disabled">Trước</span>
                @else
                    <a href="{{ $users->previousPageUrl() }}"
class="admin-btn admin-btn-secondary">Trước</a>
                @endif

                <span>Trang {{ $users->currentPage() }} / {{ $users->lastPage() }}</span>

                @if($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}"
                       class="admin-btn admin-btn-secondary">Sau</a>
                @else
                    <span class="users-button users-button-light users-disabled">Sau</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
