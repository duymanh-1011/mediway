@extends('admin.layouts.app')

@section('title', 'Thêm tài khoản - Mediway')

@section('breadcrumb')
    Admin / Hệ thống / Tài khoản / Thêm mới
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
        <a href="{{ route('admin.users.index') }}" class="account-btn account-btn-light">
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

        <form action="{{ route('admin.users.store') }}" method="POST">
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
                        <div class="account-error">{{ $message }}</div>
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
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <div class="account-error">{{ $message }}</div>
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
                        <div class="account-error">{{ $message }}</div>
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
                        <div class="account-error">{{ $message }}</div>
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
                   class="account-btn account-btn-light">
                    Hủy
                </a>
                <button type="submit" class="account-btn account-btn-primary">
                    Tạo tài khoản
                </button>
</div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.account-form-page{max-width:960px;margin:0 auto}
.account-form-header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:23px}
.account-form-header .page-description{margin:7px 0 0;color:#64748b}
.account-form-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:28px;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.account-card-title{border-bottom:1px solid #edf2f7;padding-bottom:20px;margin-bottom:22px}
.account-card-title h2{margin:0 0 8px;font-size:18px;color:#0f172a}
.account-card-title p{margin:0;color:#64748b;font-size:13px}
.account-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
.account-field{min-width:0}
.account-full-width{grid-column:1/-1}
.account-field label{display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:9px}
.account-field label span{color:#dc2626}
.account-field input,.account-field select{display:block;width:100%;min-height:43px;background:#fff;border:1px solid #cbd5e1;border-radius:9px;padding:11px 13px;color:#0f172a;outline:none}
.account-field input:focus,.account-field select:focus{border-color:#2563eb;box-shadow:0 0 0 3px #dbeafe}
.account-field .account-invalid{border-color:#ef4444}
.account-error{color:#dc2626;font-size:12px;margin-top:7px}
.account-error-summary{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;padding:13px 15px;border-radius:9px;margin-bottom:20px;font-size:13px}
.account-note{background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:15px 17px;margin-top:24px;color:#1e40af;font-size:13px;line-height:1.7}
.account-form-actions{display:flex;justify-content:flex-end;gap:12px;border-top:1px solid #edf2f7;padding-top:23px;margin-top:26px}
.account-btn{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;border:1px solid transparent;border-radius:9px;padding:11px 17px;min-height:42px;font-family:inherit;font-size:13px;font-weight:700;cursor:pointer}
.account-btn-light{border-color:#dbe2ea;background:#fff;color:#334155}
.account-btn-primary{background:#2563eb;color:#fff}
.account-btn-primary:hover{background:#1d4ed8;color:#fff}
@media(max-width:650px){.account-form-card{padding:18px}.account-form-grid{grid-template-columns:1fr}.account-full-width{grid-column:auto}}
</style>
@endpush