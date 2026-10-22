@extends('admin.layouts.app')
@section('admin-page', 'users-create')

@section('title', 'Thêm tài khoản - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Hệ thống</span>
    <span aria-hidden="true">/</span>
    <span>Tài khoản</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm mới</strong>
@endsection

@section('content')
<div class="account-form-page">
    <div class="account-form-header">
        <div>
            <h1 class="page-title">Thêm tài khoản</h1>
            <p class="page-description">
                Tạo tài khoản đăng nhập cho bệnh nhân, bác sĩ hoặc nhân viên.
            </p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn-secondary account-btn account-btn-light">
            ← Danh sách tài khoản
        </a>
    </div>

    <div class="account-form-card">
        <div class="account-card-title">
            <h2>Thông tin tài khoản</h2>
            <p>Mã tài khoản sẽ được hệ thống tự động tạo theo vai trò.</p>
        </div>

        @if($errors->any())
            <div class="account-error-summary">
                Dữ liệu chưa hợp lệ. Vui lòng kiểm tra các trường được đánh dấu.
            </div>
        @endif

        <form novalidate action="{{ route('admin.users.store') }}" method="POST">
            @csrf

            <div class="account-form-grid">
                <div class="account-field">
                    <label for="phone">Số điện thoại <span>*</span></label>
                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="{{ old('phone') }}"
                        maxlength="10"
                        inputmode="numeric"
                        autocomplete="off"
                        placeholder="Ví dụ: 0912345678"
                        class="{{ $errors->has('phone') ? 'account-invalid' : '' }}"
                        required
                    >
                    @error('phone')
                        <div class="account-error">{{ \App\Models\AdminDisplay::text($message) }}</div>
                    @enderror
                </div>

                <div class="account-field">
                    <label for="role_id">Vai trò <span>*</span></label>
                    <select id="role_id" name="role_id" required
                            class="{{ $errors->has('role_id') ? 'account-invalid' : '' }}">
                        <option value="">-- Chọn vai trò --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}"
                                {{ (string) old('role_id') === (string) $role->id ? 'selected' : '' }}>
                                {{ \App\Models\AdminDisplay::label($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <div class="account-error">{{ \App\Models\AdminDisplay::text($message) }}</div>
                    @enderror
                </div>

                <div class="account-field">
<label for="password">Mật khẩu <span>*</span></label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        placeholder="Ít nhất 8 ký tự"
                        class="{{ $errors->has('password') ? 'account-invalid' : '' }}"
                        required
                    >
                    @error('password')
                        <div class="account-error">{{ \App\Models\AdminDisplay::text($message) }}</div>
                    @enderror
                </div>

                <div class="account-field">
                    <label for="password_confirmation">
                        Xác nhận mật khẩu <span>*</span>
                    </label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        minlength="8"
                        maxlength="72"
                        autocomplete="new-password"
                        placeholder="Nhập lại mật khẩu"
                        required
                    >
@error('password_confirmation')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="account-field account-full-width">
                    <label for="status">Trạng thái ban đầu <span>*</span></label>
                    <select id="status" name="status" required
                            class="{{ $errors->has('status') ? 'account-invalid' : '' }}">
                        <option value="ACTIVE"
                            {{ old('status', 'ACTIVE') === 'ACTIVE' ? 'selected' : '' }}>
                            Hoạt động
                        </option>
                        <option value="LOCKED"
                            {{ old('status') === 'LOCKED' ? 'selected' : '' }}>
                            Đã khóa
                        </option>
                    </select>
                    @error('status')
                        <div class="account-error">{{ \App\Models\AdminDisplay::text($message) }}</div>
                    @enderror
                </div>
            </div>

            <div class="account-note">
                <strong>Lưu ý:</strong>
                Trang này chỉ tạo tài khoản đăng nhập. Hồ sơ bệnh nhân,
                bác sĩ hoặc nhân viên sẽ được liên kết tại module quản lý
                hồ sơ tương ứng. Tài khoản quản trị viên cần được tạo
                trong mục Quản trị viên.
            </div>

            <div class="account-form-actions">
                <a href="{{ route('admin.users.index') }}"
                   class="account-btn account-btn-light">Hủy</a>
                <button type="submit" class="account-btn account-btn-primary">
                    Tạo tài khoản
                </button>
</div>
        </form>
    </div>
</div>
@endsection
