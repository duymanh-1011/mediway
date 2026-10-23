@extends('admin.layouts.app')
@section('admin-page', 'patients-create')

@section('title', 'Thêm bệnh nhân - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm mới</strong>
@endsection

@section('content')
<div class="pf-page">
    <div class="pf-heading">
        <div>
            <h1 class="page-title">Thêm bệnh nhân</h1>
            <p class="page-description">
                Tạo hồ sơ bệnh nhân, thông tin cá nhân và tài liệu đính kèm.
            </p>
        </div>

        <a href="{{ route('admin.patients.index') }}" class="admin-btn admin-btn-secondary">← Quay lại</a>
    </div>

    <div class="pf-card">
        @if($errors->any())
            <div class="pf-alert">
                <strong>Vui lòng kiểm tra lại dữ liệu:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ \App\Models\AdminDisplay::text($error) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form novalidate
            method="POST"
            action="{{ route('admin.patients.store') }}"
            enctype="multipart/form-data"
        >
            @csrf

            @include('admin.patients._form')

            <div class="pf-note">
                Mã bệnh nhân được tự động tạo khi lưu.
                Nếu chọn tài khoản Patient có sẵn, hệ thống sử dụng
                mã tài khoản đó làm mã bệnh nhân.

                Thông tin và đường dẫn tài liệu được lưu trong database,
                còn các tệp được lưu trong private storage.
            </div>

            <div class="pf-actions">
                <a href="{{ route('admin.patients.index') }}"
                   class="admin-btn admin-btn-secondary">Hủy</a>

                <button type="submit" class="admin-btn admin-btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>
@endsection
