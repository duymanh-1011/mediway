@extends('admin.layouts.app')
@section('admin-page', 'patients-edit')

@section('title', 'Chỉnh sửa bệnh nhân - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Nhân sự & bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <span>Bệnh nhân</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Chỉnh sửa</strong>
@endsection

@section('content')
<div class="pf-page">
    <div class="pf-heading">
        <div>
            <h1 class="page-title">Chỉnh sửa bệnh nhân</h1>
            <p class="page-description">
                {{ $patient->patient_code }} — {{ $patient->full_name }}
            </p>
        </div>

        <a href="{{ route('admin.patients.show', $patient) }}"
           class="admin-btn admin-btn-secondary">← Quay lại</a>
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
            action="{{ route('admin.patients.update', $patient) }}"
            enctype="multipart/form-data"
        >
            @csrf
            @method('PUT')

            @include('admin.patients._form')

            <div class="pf-note">
                Mã bệnh nhân được giữ nguyên để bảo toàn dữ liệu liên kết.
                Nếu không chọn tệp mới, tài liệu hiện tại sẽ được giữ lại.

                Khi chọn tệp mới và cập nhật thành công, hệ thống sẽ
                thay thế đường dẫn trong hồ sơ và dọn tệp cũ.
            </div>

            <div class="pf-actions">
                <a href="{{ route('admin.patients.show', $patient) }}"
                   class="admin-btn admin-btn-secondary">Hủy</a>

                <button type="submit" class="admin-btn admin-btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>
@endsection
