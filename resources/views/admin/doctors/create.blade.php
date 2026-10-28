
@extends('admin.layouts.app')
@section('admin-page', 'doctors-create')

@section('title', 'Thêm bác sĩ - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Bác sĩ</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm mới</strong>
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

        <a href="{{ route('admin.doctors.index') }}" class="admin-btn admin-btn-secondary">← Quay lại</a>
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
              action="{{ route('admin.doctors.store') }}"
              enctype="multipart/form-data">
            @csrf

            @include('admin.doctors._form')

            <div class="dr-info">
                Nếu chưa có tài khoản bác sĩ, hệ thống sẽ tạo tài khoản mới
                và cấp mã bác sĩ tự động. Tài khoản và hồ sơ được lưu đồng thời.
            </div>

            <div class="dr-actions">
                <a href="{{ route('admin.doctors.index') }}"
                   class="admin-btn admin-btn-secondary">
                    Hủy
                </a>

                <button type="submit" class="admin-btn admin-btn-primary">
                    Lưu
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
