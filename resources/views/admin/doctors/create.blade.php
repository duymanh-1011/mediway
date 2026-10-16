
@extends('admin.layouts.app')

@section('title', 'Thêm bác sĩ - Mediway')

@section('breadcrumb')
    Admin / Nhân sự & bệnh nhân / Bác sĩ / Thêm mới
@endsection

@section('content')
<div class="dr-page">
    <div class="dr-heading">
        <div>
            <h1 class="page-title">Thêm bác sĩ</h1>
            <p class="dr-description">
                Tạo hồ sơ bác sĩ, thông tin chuyên môn và tài khoản liên kết.
            </p>
        </div>

        <a href="{{ route('admin.doctors.index') }}" class="dr-btn dr-light">
            ← Danh sách bác sĩ
        </a>
    </div>

    <div class="dr-card">
        @if($errors->any())
            <div class="dr-alert dr-alert-error">
                <strong>Vui lòng kiểm tra các thông tin sau:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ route('admin.doctors.store') }}"
              enctype="multipart/form-data">
            @csrf

            @include('admin.doctors._form')

            <div class="dr-info">
                Nếu chưa có tài khoản Doctor, hệ thống sẽ tạo tài khoản mới
                và cấp mã bác sĩ tự động. Thông tin trong ba bảng
                users, doctors và doctor_details được xử lý cùng một transaction.
            </div>

            <div class="dr-actions">
                <a href="{{ route('admin.doctors.index') }}"
                   class="dr-btn dr-light">
                    Hủy bỏ
                </a>

                <button type="submit" class="dr-btn dr-primary">
                    Lưu bác sĩ
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
@include('admin.doctors._styles')
@endpush
