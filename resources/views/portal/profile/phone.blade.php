@extends('portal.layouts.app')
@section('title', 'Đổi số điện thoại - Mediway')
@section('content')
<link rel="stylesheet" href="{{ asset('css/patient-profile.css') }}">
<section class="profile-page"><div class="portal-shell profile-edit-shell">
    <a class="profile-back" href="{{ route('patient.profile.show') }}">← Quay lại hồ sơ cá nhân</a>
    <header class="profile-heading"><p class="portal-eyebrow">TÀI KHOẢN BỆNH NHÂN</p><h1>Đổi số điện thoại</h1><p>Bảo vệ thông tin đăng nhập của bạn.</p></header>
    <section class="profile-card"><div class="profile-card__head"><span class="profile-card__icon" aria-hidden="true">✦</span><div><h2>Số điện thoại đăng nhập</h2><p>Số đang dùng để đăng nhập tài khoản</p></div></div>
        <dl class="profile-details"><div><dt>Số điện thoại hiện tại</dt><dd>{{ $user->phone }}</dd></div></dl>
        <p class="profile-card__note">Số mới sẽ dùng để đăng nhập. Vui lòng kiểm tra kỹ vì hệ thống không gửi mã SMS xác minh.</p>
    </section>
    <form method="post" action="{{ route('patient.profile.phone.update') }}" class="profile-edit profile-phone-form">@csrf @method('PUT')
        <section class="profile-card">
            <div class="profile-card__head"><span class="profile-card__icon" aria-hidden="true">✎</span><div><h2>Thông tin thay đổi</h2><p>Nhập số mới và mật khẩu hiện tại để xác nhận.</p></div></div>
            <div class="profile-form-fields">
                <div class="profile-form-field"><label for="phone">Số điện thoại mới <span aria-hidden="true">*</span></label><input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" maxlength="10" value="{{ old('phone') }}" required aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}" @error('phone') aria-describedby="phone-error" @enderror>@error('phone')<p id="phone-error" class="portal-field-error">{{ $message }}</p>@enderror</div>
                <div class="profile-form-field"><label for="phone_confirmation">Nhập lại số điện thoại mới <span aria-hidden="true">*</span></label><input id="phone_confirmation" name="phone_confirmation" type="tel" inputmode="numeric" autocomplete="off" maxlength="10" value="{{ old('phone_confirmation') }}" required aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}"></div>
                <div class="profile-form-field"><label for="current_password">Mật khẩu hiện tại <span aria-hidden="true">*</span></label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required aria-invalid="{{ $errors->has('current_password') ? 'true' : 'false' }}" @error('current_password') aria-describedby="password-error" @enderror>@error('current_password')<p id="password-error" class="portal-field-error">{{ $message }}</p>@enderror</div>
            </div>
        </section>
        <div class="profile-form-actions"><p>Các trường có dấu <span aria-hidden="true">*</span> là bắt buộc.</p><div><x-portal.button variant="secondary" :href="route('patient.profile.show')">Hủy</x-portal.button><x-portal.button type="submit">Lưu số điện thoại</x-portal.button></div></div>
    </form>
</div></section>
@endsection
