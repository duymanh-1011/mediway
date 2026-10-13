@extends('admin.layouts.app')

@section('title', 'Quản lý quản trị viên - Mediway')

@section('breadcrumb')
    Admin / Hệ thống / Quản trị viên
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
        <a href="{{ route('admin.administrators.create') }}" class="as-btn as-primary">
            + Thêm quản trị viên
        </a>
    </div>

    @if(session('success'))
        <div class="as-alert as-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="as-alert as-error">{{ session('error') }}</div>
    @endif

    <div class="as-card">
        <form action="{{ route('admin.administrators.index') }}"
              method="GET" class="as-filters">
            <div class="as-field as-search">
                <label for="search">Tìm kiếm</label>
                <input type="text" id="search" name="search"
                       value="{{ $search }}"
                       placeholder="Mã, họ tên, SĐT hoặc email...">
            </div>

            <div class="as-field">
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
            </div>

            <button type="submit" class="as-btn as-primary">Tìm kiếm</button>
            <a href="{{ route('admin.administrators.index') }}"
               class="as-btn as-light">Đặt lại</a>
        </form>

        <div class="as-table-wrap">
            <table class="as-table">
                <thead>
                    <tr>
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
                                    <a class="as-action as-view"
                                       href="{{ route('admin.administrators.show', $administrator) }}">
                                        Chi tiết
                                    </a>
                                    <a class="as-action as-edit"
                                       href="{{ route('admin.administrators.edit', $administrator) }}">
                                        Sửa
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="as-empty">
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

@push('styles')
<style>
.adminstaff-page{min-width:0}
.adminstaff-heading{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px;margin-bottom:22px}
.adminstaff-heading .page-description{color:#64748b;margin:7px 0 0}
.as-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:22px;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.as-filters{display:flex;align-items:end;flex-wrap:wrap;gap:12px;margin-bottom:22px}
.as-field{display:flex;flex-direction:column;gap:7px;min-width:170px}
.as-search{flex:1;min-width:230px}
.as-field label{font-size:12px;color:#475569;font-weight:700}
.as-field input,.as-field select{min-height:41px;width:100%;border:1px solid #cbd5e1;border-radius:9px;background:#fff;padding:10px 12px;outline:none;color:#0f172a}
.as-field input:focus,.as-field select:focus{border-color:#2563eb;box-shadow:0 0 0 3px #dbeafe}
.as-btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 15px;min-height:41px;border-radius:9px;border:1px solid transparent;text-decoration:none;font-size:13px;font-weight:700;font-family:inherit;cursor:pointer;white-space:nowrap}
.as-primary{background:#2563eb;color:#fff}
.as-primary:hover{background:#1d4ed8;color:#fff}
.as-light{background:#fff;color:#334155;border-color:#cbd5e1}
.as-light:hover{background:#f8fafc}
.as-table-wrap{overflow-x:auto}
.as-table{width:100%;border-collapse:collapse;min-width:760px;text-align:left}
.as-table th{font-size:12px;color:#64748b;background:#f8fafc;padding:14px 12px}
.as-table td{padding:15px 12px;font-size:13px;border-bottom:1px solid #edf2f7;color:#334155}
.as-table tr:last-child td{border-bottom:0}
.as-table tbody tr:hover{background:#fafcff}
.as-badge{display:inline-block;border-radius:30px;padding:6px 10px;font-size:11px;font-weight:700}
.as-badge-success{background:#dcfce7;color:#166534}
.as-badge-danger{background:#fee2e2;color:#991b1b}
.as-actions{display:flex;gap:8px}
.as-action{font-size:12px;font-weight:700;text-decoration:none;padding:7px 10px;border-radius:7px}
.as-view{background:#eff6ff;color:#1d4ed8}
.as-edit{background:#f1f5f9;color:#334155}
.as-empty{text-align:center;padding:40px!important;color:#64748b!important}
.as-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:20px;font-size:12px;color:#64748b}
.as-pagination{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.as-disabled{opacity:.45;cursor:default}
.as-alert{padding:13px 16px;margin-bottom:16px;border-radius:9px;font-size:13px}
.as-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
.as-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
@media(max-width:680px){.as-card{padding:14px}.as-filters{flex-direction:column;align-items:stretch}.as-field{width:100%}}
</style>
@endpush