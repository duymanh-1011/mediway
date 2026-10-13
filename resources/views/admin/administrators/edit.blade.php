@extends('admin.layouts.app')

@section('title', 'Sửa quản trị viên - Mediway')

@section('breadcrumb')
    Admin / Hệ thống / Quản trị viên / Chỉnh sửa
@endsection

@section('content')
<div class="ae-page">
    <div class="ae-heading">
        <div>
            <h1 class="page-title">Chỉnh sửa quản trị viên</h1>
            <p class="page-description">
                {{ $administrator->full_name }} —
                {{ $administrator->user?->business_code }}
            </p>
        </div>

        <a href="{{ route('admin.administrators.show', $administrator) }}"
           class="ae-btn ae-light">
            ← Quay lại chi tiết
        </a>
    </div>

    <div class="ae-card">
        <div class="ae-section-title">
            <h2>Thông tin quản trị viên</h2>
            <p>
                Mã tài khoản và vai trò Administrator được giữ nguyên.
                Trạng thái được quản lý tại trang chi tiết.
            </p>
        </div>

        @if($errors->any())
            <div class="ae-alert">
                Vui lòng kiểm tra dữ liệu trước khi lưu.
            </div>
        @endif

        <form method="POST"
              action="{{ route('admin.administrators.update', $administrator) }}">
            @csrf
            @method('PUT')

            <div class="ae-grid">
                <div class="ae-field">
                    <label for="business_code">Mã tài khoản</label>
                    <input id="business_code" type="text"
                           value="{{ $administrator->user?->business_code }}"
                           disabled>
                </div>

                <div class="ae-field">
                    <label for="role">Vai trò</label>
                    <input id="role" type="text" value="Administrator" disabled>
                </div>

                <div class="ae-field">
                    <label for="full_name">Họ và tên <span>*</span></label>
                    <input id="full_name" type="text" name="full_name"
                           value="{{ old('full_name', $administrator->full_name) }}"
                           maxlength="120" required>
                    @error('full_name')
                        <small class="ae-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="ae-field">
                    <label for="phone">Số điện thoại <span>*</span></label>
                    <input id="phone" type="text" name="phone"
                           value="{{ old('phone', $administrator->user?->phone) }}"
                           maxlength="10" inputmode="numeric" required>
                    @error('phone')
                        <small class="ae-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="ae-field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email"
value="{{ old('email', $administrator->email) }}"
                           maxlength="150">
                    @error('email')
                        <small class="ae-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="ae-field">
                    <label for="date_of_birth">Ngày sinh</label>
                    <input id="date_of_birth" type="date" name="date_of_birth"
                           value="{{ old('date_of_birth', $administrator->date_of_birth) }}"
                           max="{{ now()->subDay()->toDateString() }}">
                    @error('date_of_birth')
                        <small class="ae-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="ae-field">
                    <label for="gender">Giới tính</label>
                    <select id="gender" name="gender">
                        <option value="">-- Chọn giới tính --</option>
                        <option value="MALE"
                            @selected(old('gender', $administrator->gender) === 'MALE')>
                            Nam
                        </option>
                        <option value="FEMALE"
                            @selected(old('gender', $administrator->gender) === 'FEMALE')>
                            Nữ
                        </option>
                        <option value="OTHER"
                            @selected(old('gender', $administrator->gender) === 'OTHER')>
                            Khác
                        </option>
                    </select>
                    @error('gender')
                        <small class="ae-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="ae-field ae-full">
                    <label for="address">Địa chỉ</label>
                    <input id="address" type="text" name="address"
                           value="{{ old('address', $administrator->address) }}"
                           maxlength="255">
                    @error('address')
                        <small class="ae-error">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            <div class="ae-note">
                Khi cập nhật, thay đổi ở <strong>users</strong> và
                <strong>administrators</strong> được lưu trong cùng một transaction.
                Không thay đổi mật khẩu tại màn hình này.
            </div>

            <div class="ae-actions">
                <a href="{{ route('admin.administrators.show', $administrator) }}"
                   class="ae-btn ae-light">
                    Hủy bỏ
                </a>

                <button type="submit" class="ae-btn ae-primary">
                    Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.ae-page{max-width:980px;margin:0 auto}
.ae-heading{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;margin-bottom:22px}
.ae-heading .page-description{margin:7px 0 0;color:#64748b}
.ae-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:28px;box-shadow:0 4px 20px rgba(15,23,42,.035)}
.ae-section-title{border-bottom:1px solid #edf2f7;padding-bottom:18px;margin-bottom:24px}
.ae-section-title h2{margin:0 0 8px;color:#0f172a;font-size:18px}
.ae-section-title p{margin:0;color:#64748b;font-size:13px;line-height:1.6}
.ae-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:21px}
.ae-field{min-width:0}
.ae-full{grid-column:1/-1}
.ae-field label{display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:8px}
.ae-field label span{color:#dc2626}
.ae-field input,.ae-field select{display:block;width:100%;min-height:43px;border:1px solid #cbd5e1;border-radius:9px;padding:11px 13px;outline:none;background:#fff;color:#0f172a}
.ae-field input:disabled{background:#f1f5f9;color:#64748b}
.ae-field input:focus,.ae-field select:focus{border-color:#2563eb;box-shadow:0 0 0 3px #dbeafe}
.ae-error{display:block;color:#dc2626;font-size:12px;margin-top:6px}
.ae-note{background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe;border-radius:9px;padding:15px;margin-top:24px;font-size:13px;line-height:1.7}
.ae-actions{display:flex;justify-content:flex-end;gap:12px;border-top:1px solid #edf2f7;padding-top:22px;margin-top:25px}
.ae-btn{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;min-height:41px;padding:10px 16px;font-size:13px;font-weight:700;border:1px solid transparent;border-radius:9px;font-family:inherit;cursor:pointer}
.ae-primary{background:#2563eb;color:#fff}
.ae-light{background:#fff;color:#334155;border-color:#cbd5e1}
.ae-alert{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:13px 15px;border-radius:9px;margin-bottom:20px;font-size:13px}
@media(max-width:650px){.ae-card{padding:18px}.ae-grid{grid-template-columns:1fr}.ae-full{grid-column:auto}}
</style>
@endpush
