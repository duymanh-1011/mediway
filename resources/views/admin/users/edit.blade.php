@extends('admin.layouts.app')
@section('admin-page', 'users-edit')

@section('title', 'Chỉnh sửa tài khoản - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Hệ thống</span>
    <span aria-hidden="true">/</span>
    <span>Tài khoản</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Chỉnh sửa</strong>
@endsection

@section('content')
<div class="account-edit-page">
    <div class="account-edit-heading">
        <div>
            <h1 class="page-title">Chỉnh sửa tài khoản</h1>
            <p class="page-description">
                Cập nhật thông tin đăng nhập của {{ $user->business_code }}.
            </p>
        </div>
        <a href="{{ route('admin.users.show', $user) }}"
           class="admin-btn admin-btn-secondary">← Quay lại</a>
    </div>

    <div class="account-edit-card">
        <div class="account-edit-card-heading">
            <h2>Thông tin tài khoản</h2>
            <p>Mã tài khoản và vai trò không thể thay đổi tại màn hình này.</p>
        </div>

        @if($errors->any())
            <div class="account-edit-error-box">
                Vui lòng kiểm tra lại thông tin trước khi lưu.
            </div>
        @endif

        <form novalidate method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="account-edit-field">
                <label for="business_code">Mã tài khoản</label>
                <input
                    id="business_code"
                    type="text"
                    value="{{ $user->business_code }}"
                    disabled
                >
            </div>

            <div class="account-edit-field">
                <label for="role_name">Vai trò</label>
                <input
                    id="role_name"
                    type="text"
                    value="{{ $user->role?->name ?? 'Chưa xác định' }}"
                    disabled
                >
            </div>

            <div class="account-edit-field">
                <label for="phone">Số điện thoại <span>*</span></label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="{{ old('phone', $user->phone) }}"
                    maxlength="10"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="0912345678"
                    class="{{ $errors->has('phone') ? 'account-edit-invalid' : '' }}"
                    required
                >
                @error('phone')
                    <small class="account-edit-field-error">{{ \App\Models\AdminDisplay::text($message) }}</small>
                @enderror
            </div>

            <div class="account-edit-note">
                Để khóa, mở khóa hoặc đặt lại mật khẩu, hãy quay lại
                trang chi tiết tài khoản. Các thao tác đó được quản lý riêng.
            </div>

            <div class="account-edit-actions">
                <a href="{{ route('admin.users.show', $user) }}"
class="admin-btn admin-btn-secondary">Hủy</a>
                <button type="submit" class="admin-btn admin-btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>
@endsection
