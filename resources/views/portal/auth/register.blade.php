@extends('portal.auth.layout')
@section('title', 'Đăng ký bệnh nhân - Mediway')
@section('auth-content')
<p class="portal-eyebrow">TẠO TÀI KHOẢN</p><h1>Đăng ký tài khoản bệnh nhân</h1><p>Thông tin này tạo một hồ sơ tài khoản mới, không liên kết với hồ sơ cũ.</p>
<form method="post" action="{{ route('patient.register.store') }}" class="portal-form" novalidate>@csrf
<label for="full_name">Họ và tên</label><input id="full_name" name="full_name" autocomplete="name" value="{{ old('full_name') }}" required aria-invalid="@error('full_name') true @else false @enderror">@error('full_name')<p class="portal-field-error">{{ $message }}</p>@enderror
<label for="phone">Số điện thoại</label><input id="phone" name="phone" inputmode="numeric" autocomplete="tel" value="{{ old('phone') }}" required aria-invalid="@error('phone') true @else false @enderror">@error('phone')<p class="portal-field-error">{{ $message }}</p>@enderror
<label for="password">Mật khẩu</label><div class="portal-password"><input id="password" name="password" type="password" autocomplete="new-password" required><button type="button" data-password-toggle aria-controls="password">Hiện</button></div>@error('password')<p class="portal-field-error">{{ $message }}</p>@enderror
<label for="password_confirmation">Xác nhận mật khẩu</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
<x-portal.button type="submit" class="portal-btn--block">Tạo tài khoản</x-portal.button></form>
<p class="portal-auth__links">Đã có tài khoản? <a href="{{ route('patient.login') }}">Đăng nhập</a></p>
@endsection
