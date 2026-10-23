
@extends('admin.layouts.app')
@section('admin-page', 'doctors-index')

@section('title', 'Quản lý bác sĩ - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Bác sĩ</strong>
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

        <a href="{{ route('admin.doctors.create') }}" class="admin-btn admin-btn-primary">Thêm bác sĩ</a>
    </div>

    @if(session('success'))
        <div class="dx-alert">{{ session('success') }}</div>
    @endif

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc bác sĩ',
        'description' => 'Lọc theo từ khóa và trạng thái.',
    ])
        <form method="GET" action="{{ route('admin.doctors.index') }}"
              class="dx-filters admin-filter-form">
            <div class="dx-field dx-search admin-filter-field">
                <label for="search">Tìm kiếm</label>
                <input type="text" id="search" name="search"
                       value="{{ $search }}"
                       placeholder="Mã BS, họ tên, SĐT, giấy phép...">
@error('search')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="dx-field admin-filter-field">
                <label for="status">Trạng thái</label>
                <select id="status" name="status">
                    <option value="">Tất cả</option>
                    <option value="ACTIVE" @selected($status === 'ACTIVE')>Đang hoạt động</option>
                    <option value="INACTIVE" @selected($status === 'INACTIVE')>Ngừng hoạt động</option>
                </select>
@error('status')<div class="field-error">{{ $message }}</div>@enderror
            </div>




@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.doctors.index'), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="dx-card">
        @include('admin.layouts.partials.list-heading', ['listTitle' => 'Danh sách bác sĩ'])


        @if($errors->any())
            <div class="dx-alert dx-alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ \App\Models\AdminDisplay::text($error) }}</div>
                @endforeach
            </div>
        @endif

        <div class="dx-table-wrap">
            <table class="dx-table">
                <thead>
                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
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
                            @include('admin.layouts.partials.row-number', ['rows' => $doctors, 'index' => $loop->index])
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
                                @include('admin.layouts.partials.status-badge', ['badgeCode' => $doctor->status])
                            </td>
                            <td><div class="admin-actions admin-table-actions">
                                <div class="dx-actions">
                                    <a href="{{ route('admin.doctors.show', $doctor) }}"
                                       class="admin-btn admin-btn-detail">Chi tiết</a>

                                    <a href="{{ route('admin.doctors.edit', $doctor) }}"
                                       class="admin-btn admin-btn-secondary">Sửa</a>
                                </div>
                            </div></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="dx-empty">
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
                    <span class="admin-btn admin-btn-secondary" aria-disabled="true">Trước</span>
                @else
                    <a href="{{ $doctors->previousPageUrl() }}"
                       class="admin-btn admin-btn-secondary">Trước</a>
                @endif

                <span>
                    Trang {{ $doctors->currentPage() }} / {{ $doctors->lastPage() }}
                </span>

                @if($doctors->hasMorePages())
                    <a href="{{ $doctors->nextPageUrl() }}"
                       class="admin-btn admin-btn-secondary">Sau</a>
                @else
                    <span class="admin-btn admin-btn-secondary" aria-disabled="true">Sau</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
