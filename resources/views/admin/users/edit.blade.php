@extends('admin.layouts.app')

@section('title', 'Chỉnh sửa tài khoản - Mediway')

@section('breadcrumb')
    Admin / Hệ thống / Tài khoản / Chỉnh sửa
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
           class="account-edit-btn account-edit-light">
            ← Quay lại chi tiết
        </a>
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

        <form method="POST" action="{{ route('admin.users.update', $user) }}">
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
                    <small class="account-edit-field-error">{{ $message }}</small>
                @enderror
            </div>

            <div class="account-edit-note">
                Để khóa, mở khóa hoặc đặt lại mật khẩu, hãy quay lại
                trang chi tiết tài khoản. Các thao tác đó được quản lý riêng.
            </div>

            <div class="account-edit-actions">
                <a href="{{ route('admin.users.show', $user) }}"
class="account-edit-btn account-edit-light">
                    Hủy bỏ
                </a>
                <button type="submit" class="account-edit-btn account-edit-primary">
                    Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.account-edit-page{max-width:860px;margin:0 auto}
.account-edit-heading{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;margin-bottom:22px}
.account-edit-heading .page-description{margin:7px 0 0;color:#64748b}
.account-edit-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:27px;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.account-edit-card-heading{border-bottom:1px solid #edf2f7;padding-bottom:19px;margin-bottom:23px}
.account-edit-card-heading h2{font-size:18px;color:#0f172a;margin:0 0 8px}
.account-edit-card-heading p{color:#64748b;font-size:13px;margin:0}
.account-edit-field{margin-bottom:21px}
.account-edit-field label{display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:9px}
.account-edit-field label span{color:#dc2626}
.account-edit-field input{display:block;width:100%;min-height:44px;padding:11px 13px;border:1px solid #cbd5e1;border-radius:9px;background:#fff;color:#0f172a;outline:none}
.account-edit-field input:disabled{background:#f1f5f9;color:#64748b;cursor:not-allowed}
.account-edit-field input:focus{border-color:#2563eb;box-shadow:0 0 0 3px #dbeafe}
.account-edit-field .account-edit-invalid{border-color:#dc2626}
.account-edit-field-error{display:block;color:#dc2626;font-size:12px;margin-top:7px}
.account-edit-note{border:1px solid #bfdbfe;background:#eff6ff;color:#1e40af;padding:15px;border-radius:9px;font-size:13px;line-height:1.7;margin-top:26px}
.account-edit-actions{display:flex;justify-content:flex-end;gap:11px;margin-top:25px;padding-top:22px;border-top:1px solid #edf2f7}
.account-edit-btn{display:inline-flex;align-items:center;justify-content:center;min-height:41px;padding:10px 16px;border-radius:9px;font-size:13px;font-weight:700;border:1px solid transparent;font-family:inherit;cursor:pointer;text-decoration:none}
.account-edit-light{border-color:#cbd5e1;background:#fff;color:#334155}
.account-edit-primary{background:#2563eb;color:#fff}
.account-edit-error-box{padding:13px 15px;background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;border-radius:9px;margin-bottom:20px;font-size:13px}
@media(max-width:600px){.account-edit-card{padding:17px}}
</style>
@endpush