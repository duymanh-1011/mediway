
@extends('admin.layouts.app')

@section('title', 'Quản lý nhân viên - Mediway')

@section('breadcrumb')
    Admin / Nhân sự & bệnh nhân / Nhân viên
@endsection

@section('content')
<div class="ei-page">
    <div class="ei-heading">
        <div>
            <h1 class="page-title">Quản lý nhân viên</h1>
            <p class="ei-description">
                Tra cứu nhân viên theo phòng ban, loại nhân viên và trạng thái.
            </p>
        </div>
        <a href="{{ route('admin.employees.create') }}" class="ei-btn ei-primary">
            + Thêm nhân viên
        </a>
    </div>

    @if(session('success'))
        <div class="ei-alert ei-success">{{ session('success') }}</div>
    @endif

    <div class="ei-card">
        <form action="{{ route('admin.employees.index') }}" method="GET" class="ei-filters">
            <div class="ei-field ei-search">
                <label for="search">Tìm kiếm</label>
                <input type="text" id="search" name="search"
                       maxlength="150" value="{{ $search }}"
                       placeholder="Mã NV, họ tên, SĐT, chức vụ...">
            </div>

            <div class="ei-field">
                <label for="department">Phòng ban</label>
                <select id="department" name="department">
                    <option value="">Tất cả phòng ban</option>
                    @foreach($departments as $option)
                        <option value="{{ $option }}" @selected($department === $option)>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="ei-field">
                <label for="employee_type">Loại nhân viên</label>
                <select id="employee_type" name="employee_type">
                    <option value="">Tất cả loại</option>
                    @foreach($employeeTypes as $option)
                        <option value="{{ $option }}" @selected($employeeType === $option)>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="ei-field">
                <label for="status">Trạng thái</label>
                <select id="status" name="status">
                    <option value="">Tất cả</option>
                    <option value="ACTIVE" @selected($status === 'ACTIVE')>Hoạt động</option>
                    <option value="INACTIVE" @selected($status === 'INACTIVE')>Không hoạt động</option>
                </select>
            </div>

            <button type="submit" class="ei-btn ei-primary">Lọc</button>
            <a href="{{ route('admin.employees.index') }}" class="ei-btn ei-light">Đặt lại</a>
        </form>

        @if($errors->any())
            <div class="ei-alert ei-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="ei-table-wrap">
            <table class="ei-table">
                <thead>
                    <tr>
                        <th>Mã NV</th>
                        <th>Họ và tên</th>
                        <th>Điện thoại</th>
                        <th>Phòng ban</th>
                        <th>Chức vụ</th>
                        <th>Loại nhân viên</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td><strong>{{ $employee->employee_code }}</strong></td>
                            <td>{{ $employee->full_name }}</td>
                            <td>{{ $employee->user?->phone ?? '-' }}</td>
                            <td>{{ $employee->detail?->department ?: 'Chưa cập nhật' }}</td>
                            <td>{{ $employee->detail?->position ?: '-' }}</td>
                            <td>{{ $employee->detail?->employee_type ?: '-' }}</td>
                            <td>
                                <span class="ei-status {{ $employee->status === 'ACTIVE' ? 'ei-active' : 'ei-inactive' }}">
                                    {{ $employee->status === 'ACTIVE' ? 'Hoạt động' : 'Không hoạt động' }}
                                </span>
                            </td>
                            <td>
                                <div class="ei-row-actions">
                                    <a href="{{ route('admin.employees.show', $employee) }}"
                                       class="ei-link ei-view">Chi tiết</a>
                                    <a href="{{ route('admin.employees.edit', $employee) }}"
                                       class="ei-link ei-edit">Sửa</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="ei-empty">
                                Không tìm thấy nhân viên phù hợp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="ei-footer">
            <span>Hiển thị {{ $employees->count() }} / {{ $employees->total() }} nhân viên</span>
            <div class="ei-pages">
                @if($employees->onFirstPage())
                    <span class="ei-btn ei-light ei-disabled">Trước</span>
                @else
                    <a href="{{ $employees->previousPageUrl() }}"
                       class="ei-btn ei-light">Trước</a>
                @endif

                <span>Trang {{ $employees->currentPage() }} / {{ $employees->lastPage() }}</span>

                @if($employees->hasMorePages())
                    <a href="{{ $employees->nextPageUrl() }}"
                       class="ei-btn ei-light">Sau</a>
                @else
                    <span class="ei-btn ei-light ei-disabled">Sau</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.ei-page{min-width:0}
.ei-heading{display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap;margin-bottom:22px}
.ei-description{color:#64748b;font-size:13px;margin:7px 0 0}
.ei-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:22px;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.ei-filters{display:flex;flex-wrap:wrap;align-items:end;gap:12px;margin-bottom:22px}
.ei-field{display:flex;flex-direction:column;gap:7px;min-width:145px}
.ei-search{flex:1;min-width:220px}
.ei-field label{font-size:12px;font-weight:700;color:#475569}
.ei-field input,.ei-field select{box-sizing:border-box;width:100%;min-height:41px;border:1px solid #cbd5e1;border-radius:9px;background:#fff;color:#0f172a;padding:10px 12px;font-size:13px}
.ei-btn{display:inline-flex;align-items:center;justify-content:center;min-height:41px;border:1px solid transparent;padding:10px 15px;border-radius:9px;text-decoration:none;font-family:inherit;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap}
.ei-primary{background:#2563eb;color:#fff}
.ei-light{background:#fff;border-color:#cbd5e1;color:#334155}
.ei-table-wrap{overflow-x:auto}
.ei-table{width:100%;min-width:1100px;border-collapse:collapse;text-align:left}
.ei-table th{padding:14px 12px;background:#f8fafc;color:#64748b;font-size:12px;white-space:nowrap}
.ei-table td{padding:15px 12px;border-bottom:1px solid #edf2f7;font-size:13px;color:#334155;vertical-align:middle}
.ei-table tbody tr:hover{background:#fafcff}
.ei-table tbody tr:last-child td{border-bottom:0}
.ei-status{display:inline-block;padding:6px 10px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap}
.ei-active{background:#dcfce7;color:#166534}
.ei-inactive{background:#f1f5f9;color:#475569}
.ei-row-actions{display:flex;gap:7px}
.ei-link{padding:7px 10px;border-radius:7px;text-decoration:none;font-size:12px;font-weight:700}
.ei-view{background:#eff6ff;color:#1d4ed8}
.ei-edit{background:#f1f5f9;color:#334155}
.ei-empty{text-align:center!important;padding:35px!important;color:#94a3b8!important}
.ei-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:20px;color:#64748b;font-size:12px}
.ei-pages{display:flex;align-items:center;flex-wrap:wrap;gap:10px}
.ei-disabled{opacity:.45;cursor:default}
.ei-alert{padding:13px 15px;border-radius:9px;margin-bottom:18px;font-size:13px}
.ei-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
.ei-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
@media(max-width:700px){.ei-card{padding:14px}.ei-filters{flex-direction:column;align-items:stretch}.ei-field{width:100%}}
</style>
@endpush
