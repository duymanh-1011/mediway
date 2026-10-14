@extends('admin.layouts.app')

@section('title', 'Quản lý bệnh nhân - Mediway')

@section('breadcrumb')
    Admin / Nhân sự & bệnh nhân / Bệnh nhân
@endsection

@section('content')
<div class="patients-page">
    <div class="patients-heading">
        <div>
            <h1 class="page-title">Quản lý bệnh nhân</h1>
            <p class="page-description">
                Danh sách hồ sơ bệnh nhân và thông tin liên kết tài khoản.
            </p>
        </div>

        <a href="{{ route('admin.patients.create') }}" class="pt-btn pt-primary">
            + Thêm bệnh nhân
        </a>
    </div>

    @if(session('success'))
        <div class="pt-alert pt-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="pt-alert pt-danger">{{ session('error') }}</div>
    @endif

    <div class="pt-card">
        <form method="GET"
              action="{{ route('admin.patients.index') }}"
              class="pt-filters">

            <div class="pt-field pt-search">
                <label for="search">Tìm kiếm</label>
                <input type="text"
                       id="search"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Mã BN, họ tên, SĐT hoặc CCCD...">
            </div>

            <div class="pt-field">
                <label for="status">Trạng thái hồ sơ</label>
                <select id="status" name="status">
                    <option value="">Tất cả</option>
                    <option value="ACTIVE" @selected($status === 'ACTIVE')>
                        Hoạt động
                    </option>
                </select>
            </div>

            <button type="submit" class="pt-btn pt-primary">
                Tìm kiếm
            </button>

            <a href="{{ route('admin.patients.index') }}"
               class="pt-btn pt-light">
                Đặt lại
            </a>
        </form>

        <div class="pt-table-wrap">
            <table class="pt-table">
                <thead>
                    <tr>
                        <th>Mã bệnh nhân</th>
                        <th>Họ và tên</th>
                        <th>Ngày sinh</th>
                        <th>Giới tính</th>
                        <th>Số điện thoại</th>
                        <th>Tài khoản</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($patients as $patient)
                        <tr>
                            <td>
                                <strong>{{ $patient->patient_code }}</strong>
                            </td>

                            <td>{{ $patient->full_name }}</td>

                            <td>
                                {{ $patient->date_of_birth?->format('d/m/Y') ?? '-' }}
                            </td>

                            <td>{{ $patient->gender ?? '-' }}</td>

                            <td>{{ $patient->user?->phone ?? '-' }}</td>

                            <td>
                                @if($patient->user)
                                    <span class="pt-badge pt-blue">
                                        Đã liên kết
                                    </span>
                                @else
                                    <span class="pt-badge pt-neutral">
                                        Chưa liên kết
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if($patient->status === 'ACTIVE')
                                    <span class="pt-badge pt-green">
                                        Hoạt động
                                    </span>
                                @else
                                    <span class="pt-badge pt-neutral">
                                        {{ $patient->status }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="pt-actions">
                                    <a href="{{ route('admin.patients.show', $patient) }}"
                                       class="pt-action pt-view">
                                        Chi tiết
                                    </a>

                                    <a href="{{ route('admin.patients.edit', $patient) }}"
                                       class="pt-action pt-edit">
                                        Sửa
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="pt-empty">
                                Không tìm thấy hồ sơ bệnh nhân phù hợp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-footer">
            <span>
                Hiển thị {{ $patients->count() }} /
                {{ $patients->total() }} bệnh nhân
            </span>

            <div class="pt-pagination">
                @if($patients->onFirstPage())
                    <span class="pt-btn pt-light pt-disabled">Trước</span>
                @else
                    <a class="pt-btn pt-light"
                       href="{{ $patients->previousPageUrl() }}">
                        Trước
                    </a>
                @endif

                <span>
                    Trang {{ $patients->currentPage() }} /
                    {{ $patients->lastPage() }}
                </span>

                @if($patients->hasMorePages())
                    <a class="pt-btn pt-light"
                       href="{{ $patients->nextPageUrl() }}">
                        Sau
                    </a>
                @else
                    <span class="pt-btn pt-light pt-disabled">Sau</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.patients-page{min-width:0}
.patients-heading{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:22px}
.patients-heading .page-description{margin:7px 0 0;color:#64748b}
.pt-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:22px;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.pt-filters{display:flex;align-items:end;flex-wrap:wrap;gap:12px;margin-bottom:22px}
.pt-field{display:flex;flex-direction:column;gap:7px;min-width:170px}
.pt-search{flex:1;min-width:220px}
.pt-field label{font-size:12px;font-weight:700;color:#475569}
.pt-field input,.pt-field select{width:100%;min-height:41px;border:1px solid #cbd5e1;border-radius:9px;background:#fff;padding:10px 12px;outline:none;color:#0f172a}
.pt-field input:focus,.pt-field select:focus{border-color:#2563eb;box-shadow:0 0 0 3px #dbeafe}
.pt-btn{display:inline-flex;align-items:center;justify-content:center;min-height:41px;padding:10px 15px;border:1px solid transparent;border-radius:9px;text-decoration:none;font-family:inherit;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap}
.pt-primary{background:#2563eb;color:#fff}
.pt-light{background:#fff;border-color:#cbd5e1;color:#334155}
.pt-table-wrap{overflow-x:auto}
.pt-table{min-width:940px;width:100%;border-collapse:collapse;text-align:left}
.pt-table th{background:#f8fafc;padding:14px 12px;font-size:12px;font-weight:700;color:#64748b;white-space:nowrap}
.pt-table td{padding:15px 12px;font-size:13px;color:#334155;border-bottom:1px solid #edf2f7}
.pt-table tbody tr:hover{background:#fafcff}
.pt-table tbody tr:last-child td{border-bottom:0}
.pt-badge{display:inline-block;padding:6px 10px;border-radius:30px;font-size:11px;font-weight:700;white-space:nowrap}
.pt-green{background:#dcfce7;color:#166534}
.pt-blue{background:#dbeafe;color:#1d4ed8}
.pt-neutral{background:#f1f5f9;color:#475569}
.pt-actions{display:flex;gap:7px}
.pt-action{text-decoration:none;padding:7px 10px;border-radius:7px;font-size:12px;font-weight:700}
.pt-view{background:#eff6ff;color:#1d4ed8}
.pt-edit{background:#f1f5f9;color:#334155}
.pt-empty{text-align:center;padding:38px!important;color:#64748b!important}
.pt-footer{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-top:20px;font-size:12px;color:#64748b}
.pt-pagination{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.pt-disabled{opacity:.45;cursor:default}
.pt-alert{padding:13px 15px;border-radius:9px;margin-bottom:17px;font-size:13px}
.pt-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
.pt-danger{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
@media(max-width:680px){.pt-card{padding:14px}.pt-filters{flex-direction:column;align-items:stretch}.pt-field{width:100%}}
</style>
@endpush