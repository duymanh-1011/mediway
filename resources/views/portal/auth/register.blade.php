@extends('portal.auth.layout')
@section('title', 'Đăng ký bệnh nhân - Mediway')
@section('auth-content')
<p class="portal-eyebrow">TẠO TÀI KHOẢN</p><h1>Đăng ký tài khoản bệnh nhân</h1><p>Thông tin này tạo một hồ sơ tài khoản mới, không liên kết với hồ sơ cũ.</p>
<form method="post" action="{{ route('patient.register.store') }}" class="portal-form" novalidate>@csrf
<label for="full_name">Họ và tên</label><input id="full_name" name="full_name" autocomplete="name" value="{{ old('full_name') }}" required @error('full_name') aria-invalid="true" aria-describedby="full_name-error" @enderror>@error('full_name')<p id="full_name-error" class="portal-field-error">{{ $message }}</p>@enderror
<label for="phone">Số điện thoại</label><input id="phone" name="phone" inputmode="numeric" autocomplete="tel" value="{{ old('phone') }}" required @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>@error('phone')<p id="phone-error" class="portal-field-error">{{ $message }}</p>@enderror
<label for="password">Mật khẩu</label><div class="portal-password"><input id="password" name="password" type="password" autocomplete="new-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror><button type="button" data-password-toggle aria-controls="password" aria-label="Hiện mật khẩu">Hiện</button></div>@error('password')<p id="password-error" class="portal-field-error">{{ $message }}</p>@enderror
<label for="password_confirmation">Xác nhận mật khẩu</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required @error('password_confirmation') aria-invalid="true" aria-describedby="password_confirmation-error" @enderror>@error('password_confirmation')<p id="password_confirmation-error" class="portal-field-error">{{ $message }}</p>@enderror
<x-portal.button type="submit" class="portal-btn--block" data-pending-label="Đang tạo tài khoản…">Tạo tài khoản</x-portal.button></form>
<p class="portal-auth__links">Đã có tài khoản? <a href="{{ route('patient.login') }}">Đăng nhập</a></p>
@endsection
