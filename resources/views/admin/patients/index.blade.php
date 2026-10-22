@extends('admin.layouts.app')
@section('admin-page', 'patients-index')

@section('title', 'Quản lý bệnh nhân - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Bệnh nhân</strong>
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

        <a href="{{ route('admin.patients.create') }}" class="admin-btn pt-btn pt-primary admin-btn-primary">
            + Thêm bệnh nhân
        </a>
    </div>

    @if(session('success'))
        <div class="pt-alert pt-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="pt-alert pt-danger">{{ session('error') }}</div>
    @endif

    @component('admin.layouts.partials.filter-panel', [
        'title' => 'Bộ lọc bệnh nhân',
        'description' => 'Lọc theo từ khóa và trạng thái hồ sơ.',
    ])
        <form method="GET"
              action="{{ route('admin.patients.index') }}"
              class="pt-filters admin-filter-form">

            <div class="pt-field pt-search admin-filter-field">
                <label for="search">Tìm kiếm</label>
                <input type="text"
                       id="search"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Mã BN, họ tên, SĐT hoặc CCCD...">
@error('search')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="pt-field admin-filter-field">
                <label for="status">Trạng thái hồ sơ</label>
                <select id="status" name="status">
                    <option value="">Tất cả</option>
                    <option value="ACTIVE" @selected($status === 'ACTIVE')>
                        Hoạt động
                    </option>
                </select>
@error('status')<div class="field-error">{{ $message }}</div>@enderror
            </div>





@include('admin.layouts.partials.filter-actions', ['resetUrl' => route('admin.patients.index'), 'searchOnly' => false])
        </form>
    @endcomponent

    <div class="pt-card">

        <div class="pt-table-wrap">
            <table class="pt-table">
                <thead>
                    <tr>
                        <th scope="col" class="admin-row-number">STT</th>
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
                            @include('admin.layouts.partials.row-number', ['rows' => $patients, 'index' => $loop->index])
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
                                        {{ \App\Models\AdminDisplay::label($patient->status) }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="pt-actions">
                                    <a href="{{ route('admin.patients.show', $patient) }}"
                                       class="admin-btn admin-btn-detail pt-action pt-view">Chi tiết</a>

                                    <a href="{{ route('admin.patients.edit', $patient) }}"
                                       class="admin-btn admin-btn-secondary pt-action pt-edit">Sửa</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="pt-empty">
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
