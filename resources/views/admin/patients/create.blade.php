@extends('admin.layouts.app')

@section('title', 'Thêm bệnh nhân - Mediway')

@section('breadcrumb')
    Admin / Bệnh nhân / Thêm mới
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

        <a href="{{ route('admin.patients.index') }}" class="pf-btn pf-light">
            ← Danh sách
        </a>
    </div>

    <div class="pf-card">
        @if($errors->any())
            <div class="pf-alert">
                <strong>Vui lòng kiểm tra lại dữ liệu:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
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
                   class="pf-btn pf-light">
                    Hủy
                </a>

                <button type="submit" class="pf-btn pf-primary">
                    Tạo bệnh nhân
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
@include('admin.patients._styles')
@endpush