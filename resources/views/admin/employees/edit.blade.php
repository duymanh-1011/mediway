
@extends('admin.layouts.app')

@section('title', 'Chỉnh sửa nhân viên - Mediway')

@section('breadcrumb')
    Admin / Nhân sự & bệnh nhân / Nhân viên / Chỉnh sửa
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
           class="em-btn em-light">
            ← Chi tiết nhân viên
        </a>
    </div>

    <div class="em-card">
        @if($errors->any())
            <div class="em-alert em-alert-error">
                <strong>Vui lòng kiểm tra thông tin:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
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
                   class="em-btn em-light">
                    Hủy bỏ
                </a>

                <button
                    type="submit"
                    class="em-btn em-primary"
                    onclick="return confirm('Xác nhận lưu thay đổi hồ sơ nhân viên?');"
                >
                    Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
    @include('admin.employees._styles')
@endpush
