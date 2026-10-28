
@extends('admin.layouts.app')
@section('admin-page', 'employees-index')

@section('title', 'Quản lý nhân viên - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Nhân viên</strong>
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
        <a href="{{ route('admin.employees.create') }}" class="admin-btn admin-btn-primary">Thêm nhân viên</a>
    </div>

    @if(session('success'))
        <div class="ei-alert ei-success">{{ session('success') }}</div>
    @endif

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc nhân viên',
        'description' => 'Lọc theo từ khóa, phòng ban, loại nhân viên và trạng thái.',
    ])
        <form data-filter-fields="4" action="{{ route('admin.employees.index') }}" method="GET" class="ei-filters admin-filter-form">
            <div class="ei-field ei-search admin-filter-field">
                <label for="search">Tìm kiếm</label>
                <input type="text" id="search" name="search"
                       maxlength="150" value="{{ $search }}"
                       placeholder="Mã nhân viên, họ tên, SĐT, chức vụ...">
@error('search')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="ei-field admin-filter-field">
                <label for="department">Phòng ban</label>
                <select id="department" name="department">
                    <option value="">Tất cả phòng ban</option>
                    @foreach($departments as $option)
                        <option value="{{ $option }}" @selected($department === $option)>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
@error('department')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="ei-field admin-filter-field">
                <label for="employee_type">Loại nhân viên</label>
                <select id="employee_type" name="employee_type">
                    <option value="">Tất cả loại</option>
                    @foreach($employeeTypes as $option)
                        <option value="{{ $option->code }}" @selected($employeeType === $option->code)>
                            {{ $option->name }}
                        </option>
                    @endforeach
                </select>
@error('employee_type')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="ei-field admin-filter-field">
                <label for="status">Trạng thái</label>
                <select id="status" name="status">
                    <option value="">Tất cả</option>
                    <option value="ACTIVE" @selected($status === 'ACTIVE')>Đang hoạt động</option>
                    <option value="INACTIVE" @selected($status === 'INACTIVE')>Ngừng hoạt động</option>
                </select>
@error('status')<div class="field-error">{{ $message }}</div>@enderror
            </div>




@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.employees.index'), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="ei-card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách nhân viên'])


        @if($errors->any())
            <div class="ei-alert ei-error">
                @foreach($errors->all() as $error)
                    <div>{{ \App\Models\AdminDisplay::text($error) }}</div>
                @endforeach
            </div>
        @endif

        <div class="ei-table-wrap">
            <table class="ei-table">
                <thead>
                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
                        <th>Mã nhân viên</th>
                        <th>Họ và tên</th>
                        <th>Số điện thoại</th>
                        <th>Phòng ban</th>
                        <th>Loại nhân viên</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            @include('admin.layouts.partials.row-number', ['rows' => $employees, 'index' => $loop->index])
                            <td><strong>@include('admin.layouts.partials.record-code', ['code' => $employee->employee_code])</strong></td>
                            <td>{{ $employee->full_name }}</td>
                            <td>{{ $employee->user?->phone ?? '-' }}</td>
                            <td>{{ $employee->detail?->department ?: 'Chưa cập nhật' }}</td>
                            <td>{{ \App\Models\AdminDisplay::label($employee->detail?->employee_type) }}</td>
                            <td>
                                @include('admin.layouts.partials.status-badge', ['badgeCode' => $employee->status])
                            </td>
                            <td><div class="admin-actions admin-table-actions">
                                <div class="ei-row-actions">
                                    <a href="{{ route('admin.employees.show', $employee) }}"
                                       class="admin-btn admin-btn-detail">Chi tiết</a>
                                    <a href="{{ route('admin.employees.edit', $employee) }}"
                                       class="admin-btn admin-btn-secondary">Sửa</a>
                                </div>
                            </div></td>
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
                    <span class="admin-btn admin-btn-secondary" aria-disabled="true">Trước</span>
                @else
                    <a href="{{ $employees->previousPageUrl() }}"
                       class="admin-btn admin-btn-secondary">Trước</a>
                @endif

                <span>Trang {{ $employees->currentPage() }} / {{ $employees->lastPage() }}</span>

                @if($employees->hasMorePages())
                    <a href="{{ $employees->nextPageUrl() }}"
                       class="admin-btn admin-btn-secondary">Sau</a>
                @else
                    <span class="admin-btn admin-btn-secondary" aria-disabled="true">Sau</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
