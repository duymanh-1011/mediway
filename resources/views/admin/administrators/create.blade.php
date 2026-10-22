@extends('admin.layouts.app')
@section('admin-page', 'administrators-create')

@section('title', 'Thêm quản trị viên - Mediway')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Bảng điều khiển</a>
    <span aria-hidden="true">/</span>
    <span>Hệ thống</span>
    <span aria-hidden="true">/</span>
    <span>Quản trị viên</span>
    <span aria-hidden="true">/</span>
    <strong aria-current="page">Thêm mới</strong>
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

    <form novalidate action="{{ route('admin.administrators.store') }}" method="POST">
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
                    @error('phone')<small class="asf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
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
                    @error('status')<small class="asf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
                </div>

                <div class="asf-field">
                    <label for="password">Mật khẩu <span>*</span></label>
                    <input type="password" id="password" name="password"
                           minlength="8" maxlength="72"
                           autocomplete="new-password" required>
                    @error('password')<small class="asf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
                </div>

                <div class="asf-field">
                    <label for="password_confirmation">
                        Xác nhận mật khẩu <span>*</span>
                    </label>
<input type="password" id="password_confirmation"
                           name="password_confirmation"
                           minlength="8" maxlength="72"
                           autocomplete="new-password" required>
@error('password_confirmation')<div class="field-error">{{ $message }}</div>@enderror
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
                    @error('full_name')<small class="asf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
                </div>

                <div class="asf-field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email') }}"
                           maxlength="150" placeholder="admin@example.com">
                    @error('email')<small class="asf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
                </div>

                <div class="asf-field">
                    <label for="date_of_birth">Ngày sinh</label>
                    <input type="date" id="date_of_birth"
                           name="date_of_birth"
                           value="{{ old('date_of_birth') }}"
                           max="{{ now()->subDay()->toDateString() }}">
                    @error('date_of_birth')<small class="asf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
                </div>

                <div class="asf-field">
                    <label for="gender">Giới tính</label>
                    <select id="gender" name="gender">
                        <option value="">-- Chọn giới tính --</option>
                        <option value="MALE" @selected(old('gender') === 'MALE')>Nam</option>
                        <option value="FEMALE" @selected(old('gender') === 'FEMALE')>Nữ</option>
                        <option value="OTHER" @selected(old('gender') === 'OTHER')>Khác</option>
                    </select>
                    @error('gender')<small class="asf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
                </div>

                <div class="asf-field asf-full">
                    <label for="address">Địa chỉ</label>
                    <input type="text" id="address" name="address"
                           value="{{ old('address') }}"
                           maxlength="255" placeholder="Địa chỉ liên hệ">
                    @error('address')<small class="asf-error">{{ \App\Models\AdminDisplay::text($message) }}</small>@enderror
                </div>
            </div>
<div class="asf-note">
                Khi lưu, Mediway sẽ tạo đồng thời tài khoản và hồ sơ quản trị viên.
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
