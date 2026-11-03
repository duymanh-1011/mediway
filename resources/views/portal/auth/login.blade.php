@extends('portal.auth.layout')
@section('title', 'Đăng nhập - Mediway')
@section('auth-content')
<p class="portal-eyebrow">MEDIWAY</p><h1>Chào mừng bạn trở lại</h1><p>Đăng nhập để tiếp tục sử dụng Mediway.</p>
<form method="post" action="{{ route('patient.login.store') }}" class="portal-form" novalidate>@csrf
<label for="phone">Số điện thoại</label><input id="phone" name="phone" inputmode="numeric" autocomplete="tel" value="{{ old('phone') }}" required aria-invalid="@error('phone') true @else false @enderror">@error('phone')<p class="portal-field-error">{{ $message }}</p>@enderror
<label for="password">Mật khẩu</label><div class="portal-password"><input id="password" name="password" type="password" autocomplete="current-password" required><button type="button" data-password-toggle aria-controls="password">Hiện</button></div>@error('password')<p class="portal-field-error">{{ $message }}</p>@enderror
<x-portal.button type="submit" class="portal-btn--block">Đăng nhập</x-portal.button></form>
<div class="portal-auth__links"><a href="{{ route('patient.password.request') }}">Quên mật khẩu?</a><span>Chưa có tài khoản? <a href="{{ route('patient.register') }}">Đăng ký ngay</a></span></div>
@endsection
