
@extends('admin.layouts.app')
@section('admin-page', 'employees-edit')

@section('title', 'Chỉnh sửa nhân viên - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Nhân viên</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Chỉnh sửa</strong>
@endsection

@section('content')
<div class="em-page">
    <div class="em-heading">
        <div>
            <h1 class="page-title">Chỉnh sửa nhân viên</h1>
            <p class="em-description">
                {{ $employee->employee_code }} — {{ $employee->full_name }}
            </p>
        </div>

        <a href="{{ route('admin.employees.show', $employee) }}"
           class="admin-btn admin-btn-secondary">← Quay lại</a>
    </div>

    <div class="em-card">
        @if($errors->any())
            <div class="em-alert em-alert-error">
                <strong>Vui lòng kiểm tra thông tin:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ \App\Models\AdminDisplay::text($error) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form novalidate
            method="POST"
            action="{{ route('admin.employees.update', $employee) }}"
            enctype="multipart/form-data"
        >
            @csrf
            @method('PUT')

            @include('admin.employees._form')

            <div class="em-note">
                Khi chỉnh sửa, mã nhân viên và tài khoản liên kết
                được giữ nguyên. Nếu không chọn tài liệu mới,
                hệ thống sẽ giữ tài liệu hiện tại.
            </div>

            <div class="em-actions">
                <a href="{{ route('admin.employees.show', $employee) }}"
                   class="admin-btn admin-btn-secondary">Hủy</a>

                <button
                    type="submit"
                    class="admin-btn admin-btn-primary"
                    onclick="return confirm('Xác nhận lưu thay đổi hồ sơ nhân viên?');"
                >Cập nhật</button>
            </div>
        </form>
    </div>
</div>
@endsection
