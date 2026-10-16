@extends('admin.layouts.app')

@section('title', 'Quản lý tài khoản - Mediway')

@section('breadcrumb')
    Admin / Hệ thống / Tài khoản
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
        <a href="{{ route('admin.users.create') }}" class="users-button users-button-primary">
            + Thêm tài khoản
        </a>
    </div>

    @if(session('success'))
        <div class="users-alert users-alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="users-alert users-alert-error">{{ session('error') }}</div>
    @endif

    <div class="users-panel">
        <form method="GET" action="{{ route('admin.users.index') }}" class="users-filter">
            <div class="users-field users-search">
                <label for="search">Tìm kiếm</label>
                <input
                    id="search"
                    name="search"
                    type="text"
                    value="{{ $search }}"
                    placeholder="Mã tài khoản hoặc số điện thoại..."
                >
            </div>

            <div class="users-field">
                <label for="role_id">Vai trò</label>
                <select id="role_id" name="role_id">
                    <option value="">Tất cả vai trò</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}"
                            {{ (string) $roleId === (string) $role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="users-field">
                <label for="status">Trạng thái</label>
                <select id="status" name="status">
                    <option value="">Tất cả</option>
                    <option value="ACTIVE" {{ $status === 'ACTIVE' ? 'selected' : '' }}>
                        Hoạt động
                    </option>
                    <option value="LOCKED" {{ $status === 'LOCKED' ? 'selected' : '' }}>
                        Đã khóa
                    </option>
                </select>
            </div>

            <button class="users-button users-button-primary" type="submit">
                Lọc
            </button>
            <a href="{{ route('admin.users.index') }}" class="users-button users-button-light">
                Đặt lại
            </a>
        </form>

        <div class="users-table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
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
                            <td><strong>{{ $user->business_code }}</strong></td>
                            <td>{{ $user->phone }}</td>
                            <td>{{ $user->role?->name ?? 'Chưa xác định' }}</td>
                            <td>
                                @if($user->status === 'ACTIVE')
                                    <span class="users-badge users-badge-success">
                                        Hoạt động
                                    </span>
                                @elseif($user->status === 'LOCKED')
                                    <span class="users-badge users-badge-danger">
                                        Đã khóa
                                    </span>
                                @else
                                    <span class="users-badge users-badge-neutral">
                                        {{ $user->status }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $user->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td>
                                <div class="users-row-actions">
                                    <a href="{{ route('admin.users.show', $user) }}"
                                       class="users-link users-link-blue">
                                        Chi tiết
                                    </a>
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="users-link users-link-gray">
                                        Sửa
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="users-empty">
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
class="users-button users-button-light">Trước</a>
                @endif

                <span>Trang {{ $users->currentPage() }} / {{ $users->lastPage() }}</span>

                @if($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}"
                       class="users-button users-button-light">Sau</a>
                @else
                    <span class="users-button users-button-light users-disabled">Sau</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.users-page{width:100%;min-width:0}
.users-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px}
.users-heading .page-description{margin:7px 0 0;color:#64748b}
.users-button{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:10px 15px;border-radius:9px;font-size:13px;font-weight:650;text-decoration:none;border:1px solid transparent;cursor:pointer;font-family:inherit;white-space:nowrap}
.users-button-primary{background:#2563eb;color:#fff}
.users-button-primary:hover{background:#1d4ed8;color:#fff}
.users-button-light{background:#fff;color:#334155;border-color:#dbe2ea}
.users-button-light:hover{background:#f8fafc}
.users-panel{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:21px;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.users-filter{display:flex;align-items:end;flex-wrap:wrap;gap:12px;margin-bottom:21px}
.users-field{display:flex;flex-direction:column;gap:7px;min-width:150px}
.users-search{flex:1;min-width:210px}
.users-field label{font-size:12px;font-weight:700;color:#475569}
.users-field input,.users-field select{border:1px solid #cbd5e1;border-radius:9px;background:#fff;color:#0f172a;padding:10px 12px;min-height:40px;outline:none;width:100%}
.users-field input:focus,.users-field select:focus{border-color:#2563eb;box-shadow:0 0 0 3px #dbeafe}
.users-table-wrap{overflow-x:auto}
.users-table{width:100%;border-collapse:collapse;text-align:left;min-width:730px}
.users-table th{background:#f8fafc;color:#64748b;font-size:12px;font-weight:700;padding:14px 12px;white-space:nowrap}
.users-table td{padding:15px 12px;border-bottom:1px solid #edf2f7;color:#334155;font-size:13px}
.users-table tr:last-child td{border-bottom:0}
.users-table tbody tr:hover{background:#fafcff}
.users-action-heading{text-align:right}
.users-row-actions{display:flex;justify-content:flex-end;gap:8px}
.users-link{text-decoration:none;padding:7px 10px;border-radius:7px;font-size:12px;font-weight:700}
.users-link-blue{background:#eff6ff;color:#1d4ed8}
.users-link-gray{background:#f1f5f9;color:#475569}
.users-badge{display:inline-block;padding:6px 10px;border-radius:30px;font-size:11px;font-weight:700;white-space:nowrap}
.users-badge-success{background:#dcfce7;color:#166534}
.users-badge-danger{background:#fee2e2;color:#991b1b}
.users-badge-neutral{background:#f1f5f9;color:#475569}
.users-empty{text-align:center;padding:42px 16px!important;color:#64748b!important}
.users-footer{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;color:#64748b;font-size:12px;margin-top:19px}
.users-pagination{display:flex;align-items:center;flex-wrap:wrap;gap:10px}
.users-disabled{opacity:.45;cursor:not-allowed}
.users-alert{padding:13px 16px;border-radius:9px;margin-bottom:16px;font-size:13px}
.users-alert-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
.users-alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
@media(max-width:680px){.users-panel{padding:13px}.users-filter{flex-direction:column;align-items:stretch}.users-field{width:100%}.users-heading{align-items:flex-start}}
</style>
@endpush
