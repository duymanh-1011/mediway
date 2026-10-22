
@extends('admin.layouts.app')
@section('admin-page', 'employees-create')

@section('title', 'Thêm nhân viên - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Nhân viên</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm mới</strong>
@endsection

@section('content')
<div class="em-page">
    <div class="em-heading">
        <div>
            <h1 class="page-title">Thêm nhân viên</h1>
            <p class="em-description">
                Tạo nhân viên và hồ sơ công việc trong Mediway.
            </p>
        </div>

        <a href="{{ route('admin.employees.index') }}" class="em-btn em-light">
            ← Danh sách nhân viên
        </a>
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
            action="{{ route('admin.employees.store') }}"
            enctype="multipart/form-data"
        >
            @csrf

            @include('admin.employees._form')

            <div class="em-note">
                Nếu chưa chọn tài khoản có sẵn, hệ thống sẽ tạo
                tài khoản nhân viên mới và cấp mã nhân viên tự động.
            </div>

            <div class="em-actions">
                <a href="{{ route('admin.employees.index') }}"
                   class="em-btn em-light">
                    Hủy
                </a>

                <button type="submit" class="em-btn em-primary">
                    Lưu
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
