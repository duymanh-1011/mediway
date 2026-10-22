
@extends('admin.layouts.app')
@section('admin-page', 'doctors-edit')

@section('title', 'Chỉnh sửa bác sĩ - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Bác sĩ</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Chỉnh sửa</strong>
@endsection

@section('content')
<div class="dr-page">
    <div class="dr-heading">
        <div>
            <h1 class="page-title">Chỉnh sửa bác sĩ</h1>
            <p class="dr-description">
                {{ $doctor->doctor_code }} — {{ $doctor->full_name }}
            </p>
        </div>

        <a href="{{ route('admin.doctors.show', $doctor) }}"
           class="dr-btn dr-light">
            ← Chi tiết bác sĩ
        </a>
    </div>

    <div class="dr-card">
        @if($errors->any())
            <div class="dr-alert dr-alert-error">
                <strong>Vui lòng kiểm tra các thông tin sau:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ \App\Models\AdminDisplay::text($error) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form novalidate method="POST"
              action="{{ route('admin.doctors.update', $doctor) }}"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('admin.doctors._form')

            <div class="dr-info">
                Ảnh và tài liệu hiện tại được giữ nguyên nếu không chọn
                tệp mới. Việc chỉnh sửa hồ sơ không thay đổi mã bác sĩ
                hoặc tài khoản liên kết. Trạng thái hồ sơ bác sĩ hiện
                chưa tự đồng bộ với trạng thái khóa tài khoản.
            </div>

            <div class="dr-actions">
                <a href="{{ route('admin.doctors.show', $doctor) }}"
                   class="dr-btn dr-light">
                    Hủy bỏ
                </a>

                <button type="submit"
                        class="dr-btn dr-primary"
                        onclick="return confirm('Xác nhận lưu thay đổi hồ sơ bác sĩ?');">
                    Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
