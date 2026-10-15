
@extends('admin.layouts.app')

@section('title', 'Quản lý bác sĩ - Mediway')

@section('breadcrumb')
    Admin / Nhân sự & bệnh nhân / Bác sĩ
@endsection

@section('content')
<div class="doctors-page">
    <div class="dx-heading">
        <div>
            <h1 class="page-title">Quản lý bác sĩ</h1>
            <p class="dx-description">
                Danh sách bác sĩ, tài khoản liên kết và hồ sơ chuyên môn.
            </p>
        </div>

        <a href="{{ route('admin.doctors.create') }}" class="dx-btn dx-primary">
            + Thêm bác sĩ
        </a>
    </div>

    @if(session('success'))
        <div class="dx-alert">{{ session('success') }}</div>
    @endif

    <div class="dx-card">
        <form method="GET" action="{{ route('admin.doctors.index') }}"
              class="dx-filters">
            <div class="dx-field dx-search">
                <label for="search">Tìm kiếm</label>
                <input type="text" id="search" name="search"
                       value="{{ $search }}"
                       placeholder="Mã BS, họ tên, SĐT, giấy phép...">
            </div>

            <div class="dx-field">
                <label for="status">Trạng thái</label>
                <select id="status" name="status">
                    <option value="">Tất cả</option>
                    <option value="ACTIVE" @selected($status === 'ACTIVE')>
                        Hoạt động
                    </option>
                    <option value="INACTIVE" @selected($status === 'INACTIVE')>
                        Không hoạt động
                    </option>
                </select>
            </div>

            <button type="submit" class="dx-btn dx-primary">Tìm kiếm</button>
            <a href="{{ route('admin.doctors.index') }}" class="dx-btn dx-light">
                Đặt lại
            </a>
        </form>

        @if($errors->any())
            <div class="dx-alert dx-alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="dx-table-wrap">
            <table class="dx-table">
                <thead>
                    <tr>
                        <th>Mã bác sĩ</th>
                        <th>Họ và tên</th>
                        <th>Số điện thoại</th>
                        <th>Chuyên môn</th>
                        <th>Giấy phép hành nghề</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($doctors as $doctor)
                        <tr>
                            <td><strong>{{ $doctor->doctor_code }}</strong></td>
                            <td>{{ $doctor->full_name }}</td>
                            <td>{{ $doctor->user?->phone ?? '-' }}</td>
                            <td>
                                {{ $doctor->detail?->professional_qualification ?: 'Chưa cập nhật' }}
                            </td>
                            <td>
                                {{ $doctor->detail?->practice_license_number ?: '-' }}
                            </td>
                            <td>
                                <span class="dx-status {{ $doctor->status === 'ACTIVE' ? 'dx-active' : 'dx-inactive' }}">
                                    {{ $doctor->status === 'ACTIVE' ? 'Hoạt động' : 'Không hoạt động' }}
                                </span>
                            </td>
                            <td>
                                <div class="dx-actions">
                                    <a href="{{ route('admin.doctors.show', $doctor) }}"
                                       class="dx-action dx-view">Chi tiết</a>

                                    <a href="{{ route('admin.doctors.edit', $doctor) }}"
                                       class="dx-action dx-edit">Sửa</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="dx-empty">
                                Chưa có bác sĩ phù hợp với điều kiện tìm kiếm.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="dx-footer">
            <span>
                Hiển thị {{ $doctors->count() }} / {{ $doctors->total() }} bác sĩ
            </span>

            <div class="dx-pagination">
                @if($doctors->onFirstPage())
                    <span class="dx-btn dx-light dx-disabled">Trước</span>
                @else
                    <a href="{{ $doctors->previousPageUrl() }}"
                       class="dx-btn dx-light">Trước</a>
                @endif

                <span>
                    Trang {{ $doctors->currentPage() }} / {{ $doctors->lastPage() }}
                </span>

                @if($doctors->hasMorePages())
                    <a href="{{ $doctors->nextPageUrl() }}"
                       class="dx-btn dx-light">Sau</a>
                @else
                    <span class="dx-btn dx-light dx-disabled">Sau</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.doctors-page{min-width:0}
.dx-heading{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px;margin-bottom:22px}
.dx-description{font-size:13px;color:#64748b;margin:7px 0 0}
.dx-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:23px;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.dx-filters{display:flex;align-items:end;gap:12px;flex-wrap:wrap;margin-bottom:22px}
.dx-field{display:flex;flex-direction:column;gap:7px;min-width:170px}
.dx-search{flex:1;min-width:220px}
.dx-field label{font-size:12px;color:#475569;font-weight:700}
.dx-field input,.dx-field select{box-sizing:border-box;width:100%;min-height:41px;border:1px solid #cbd5e1;border-radius:9px;padding:10px 12px;background:#fff;color:#0f172a;font-size:13px}
.dx-btn{display:inline-flex;justify-content:center;align-items:center;min-height:41px;border:1px solid transparent;padding:10px 15px;border-radius:9px;text-decoration:none;font-size:13px;font-weight:700;font-family:inherit;cursor:pointer;white-space:nowrap}
.dx-primary{background:#2563eb;color:#fff}
.dx-light{background:#fff;border-color:#cbd5e1;color:#334155}
.dx-table-wrap{overflow-x:auto}
.dx-table{width:100%;min-width:1000px;border-collapse:collapse;text-align:left}
.dx-table th{padding:14px 12px;background:#f8fafc;color:#64748b;font-size:12px;white-space:nowrap}
.dx-table td{padding:15px 12px;color:#334155;border-bottom:1px solid #edf2f7;font-size:13px;vertical-align:middle}
.dx-table tbody tr:hover{background:#fafcff}
.dx-table tbody tr:last-child td{border-bottom:0}
.dx-status{display:inline-block;padding:6px 10px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap}
.dx-active{background:#dcfce7;color:#166534}
.dx-inactive{background:#f1f5f9;color:#475569}
.dx-actions{display:flex;gap:7px}
.dx-action{padding:7px 10px;border-radius:7px;font-size:12px;font-weight:700;text-decoration:none}
.dx-view{background:#eff6ff;color:#1d4ed8}
.dx-edit{background:#f1f5f9;color:#334155}
.dx-empty{text-align:center!important;padding:35px!important;color:#94a3b8!important}
.dx-footer{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-top:20px;color:#64748b;font-size:12px}
.dx-pagination{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.dx-disabled{opacity:.45;cursor:default}
.dx-alert{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:9px;padding:13px 15px;font-size:13px;margin-bottom:18px}
.dx-alert-error{background:#fef2f2;border-color:#fecaca;color:#991b1b}
@media(max-width:700px){.dx-card{padding:14px}.dx-filters{align-items:stretch;flex-direction:column}.dx-field{width:100%}}
</style>
@endpush
