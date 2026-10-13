@extends('admin.layouts.app')

@section('title', 'Thêm quản trị viên - Mediway')

@section('breadcrumb')
    Admin / Hệ thống / Quản trị viên / Thêm mới
@endsection

@section('content')
<div class="as-form-page">
    <div class="as-form-heading">
        <div>
            <h1 class="page-title">Thêm quản trị viên</h1>
            <p class="page-description">
                Tạo tài khoản và hồ sơ quản trị viên trong cùng một thao tác.
            </p>
        </div>
        <a href="{{ route('admin.administrators.index') }}" class="asf-btn asf-light">
            ← Quay lại
        </a>
    </div>

    <form action="{{ route('admin.administrators.store') }}" method="POST">
        @csrf

        <div class="asf-card">
            <div class="asf-section">
                <h2>Thông tin tài khoản</h2>
                <p>Hệ thống sẽ tự cấp mã quản trị QT tiếp theo.</p>
            </div>

            @if($errors->any())
                <div class="asf-error-summary">
                    Vui lòng kiểm tra các trường có lỗi trước khi lưu.
                </div>
            @endif

            <div class="asf-grid">
                <div class="asf-field">
                    <label for="phone">Số điện thoại <span>*</span></label>
                    <input type="text" id="phone" name="phone"
                           value="{{ old('phone') }}"
                           maxlength="10" inputmode="numeric"
                           autocomplete="off"
                           placeholder="0912345678" required>
                    @error('phone')<small class="asf-error">{{ $message }}</small>@enderror
                </div>

                <div class="asf-field">
                    <label for="status">Trạng thái ban đầu <span>*</span></label>
                    <select id="status" name="status" required>
                        <option value="ACTIVE" @selected(old('status', 'ACTIVE') === 'ACTIVE')>
                            Hoạt động
                        </option>
                        <option value="LOCKED" @selected(old('status') === 'LOCKED')>
                            Khóa tài khoản
                        </option>
                    </select>
                    @error('status')<small class="asf-error">{{ $message }}</small>@enderror
                </div>

                <div class="asf-field">
                    <label for="password">Mật khẩu <span>*</span></label>
                    <input type="password" id="password" name="password"
                           minlength="8" maxlength="72"
                           autocomplete="new-password" required>
                    @error('password')<small class="asf-error">{{ $message }}</small>@enderror
                </div>

                <div class="asf-field">
                    <label for="password_confirmation">
                        Xác nhận mật khẩu <span>*</span>
                    </label>
<input type="password" id="password_confirmation"
                           name="password_confirmation"
                           minlength="8" maxlength="72"
                           autocomplete="new-password" required>
                </div>
            </div>

            <div class="asf-section asf-section-second">
                <h2>Hồ sơ quản trị viên</h2>
                <p>Thông tin cá nhân dùng để nhận diện và quản lý người quản trị.</p>
            </div>

            <div class="asf-grid">
                <div class="asf-field">
                    <label for="full_name">Họ và tên <span>*</span></label>
                    <input type="text" id="full_name" name="full_name"
                           value="{{ old('full_name') }}"
                           maxlength="120" placeholder="Họ tên quản trị viên" required>
                    @error('full_name')<small class="asf-error">{{ $message }}</small>@enderror
                </div>

                <div class="asf-field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email') }}"
                           maxlength="150" placeholder="admin@example.com">
                    @error('email')<small class="asf-error">{{ $message }}</small>@enderror
                </div>

                <div class="asf-field">
                    <label for="date_of_birth">Ngày sinh</label>
                    <input type="date" id="date_of_birth"
                           name="date_of_birth"
                           value="{{ old('date_of_birth') }}"
                           max="{{ now()->subDay()->toDateString() }}">
                    @error('date_of_birth')<small class="asf-error">{{ $message }}</small>@enderror
                </div>

                <div class="asf-field">
                    <label for="gender">Giới tính</label>
                    <select id="gender" name="gender">
                        <option value="">-- Chọn giới tính --</option>
                        <option value="MALE" @selected(old('gender') === 'MALE')>Nam</option>
                        <option value="FEMALE" @selected(old('gender') === 'FEMALE')>Nữ</option>
                        <option value="OTHER" @selected(old('gender') === 'OTHER')>Khác</option>
                    </select>
                    @error('gender')<small class="asf-error">{{ $message }}</small>@enderror
                </div>

                <div class="asf-field asf-full">
                    <label for="address">Địa chỉ</label>
                    <input type="text" id="address" name="address"
                           value="{{ old('address') }}"
                           maxlength="255" placeholder="Địa chỉ liên hệ">
                    @error('address')<small class="asf-error">{{ $message }}</small>@enderror
                </div>
            </div>
<div class="asf-note">
                Khi lưu, Mediway sẽ tạo đồng thời bản ghi trong bảng
                <strong>users</strong> và <strong>administrators</strong>.
                Nếu một trong hai thao tác thất bại, cả giao dịch sẽ được hoàn tác.
            </div>

            <div class="asf-actions">
                <a href="{{ route('admin.administrators.index') }}"
                   class="asf-btn asf-light">Hủy bỏ</a>
                <button type="submit" class="asf-btn asf-primary">
                    Tạo quản trị viên
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
.as-form-page{max-width:980px;margin:0 auto}
.as-form-heading{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:22px}
.as-form-heading .page-description{margin:7px 0 0;color:#64748b}
.asf-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:28px;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.asf-section{border-bottom:1px solid #edf2f7;padding-bottom:17px;margin-bottom:22px}
.asf-section h2{font-size:17px;margin:0 0 7px;color:#0f172a}
.asf-section p{font-size:13px;color:#64748b;margin:0}
.asf-section-second{margin-top:30px}
.asf-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
.asf-field{min-width:0}
.asf-full{grid-column:1/-1}
.asf-field label{display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:8px}
.asf-field label span{color:#dc2626}
.asf-field input,.asf-field select{display:block;width:100%;min-height:43px;border:1px solid #cbd5e1;border-radius:9px;padding:11px 13px;background:#fff;color:#0f172a;outline:none}
.asf-field input:focus,.asf-field select:focus{border-color:#2563eb;box-shadow:0 0 0 3px #dbeafe}
.asf-error{display:block;font-size:12px;color:#dc2626;margin-top:6px}
.asf-error-summary{padding:13px 15px;margin-bottom:20px;border-radius:9px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;font-size:13px}
.asf-note{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;border-radius:9px;padding:15px;margin-top:25px;font-size:13px;line-height:1.7}
.asf-actions{display:flex;justify-content:flex-end;gap:12px;margin-top:26px;padding-top:22px;border-top:1px solid #edf2f7}
.asf-btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:10px 16px;border-radius:9px;border:1px solid transparent;font-size:13px;font-weight:700;text-decoration:none;cursor:pointer;font-family:inherit}
.asf-primary{background:#2563eb;color:#fff}
.asf-primary:hover{background:#1d4ed8;color:#fff}
.asf-light{background:#fff;color:#334155;border-color:#cbd5e1}
@media(max-width:650px){.asf-card{padding:18px}.asf-grid{grid-template-columns:1fr}.asf-full{grid-column:auto}}
</style>
@endpush
